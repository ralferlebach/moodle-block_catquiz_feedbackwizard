# [BUG] `contextid` folgt keinem Skalenwechsel: die Bedingung vergleicht einen bereits überschriebenen Wert

## Problem

`testenvironment::update_object()` will die `contextid` einer Testumgebung neu
setzen, wenn sich die Hauptskala ändert. Der Kommentar sagt das ausdrücklich:

```php
// Set the contextid only if this is a new test OR the scale was changed.
// New test: $record->contextid is empty. Scale changed: $record->contextid != $this->contextid.
if (
    !property_exists($record, 'contextid')
    || !$record->contextid
    || ($this->catscaleid && $record->catscaleid && $this->catscaleid != $record->catscaleid)
) {
    $record->contextid = $DB->get_field('local_catquiz_catscales', 'contextid', ['id' => $record->catscaleid]);
}
```

Der dritte Zweig kann nie wahr werden. Zwanzig Zeilen weiter oben, in derselben
Methode, wird genau dieses Feld überschrieben:

```php
$record->catscaleid = $this->catscaleid ?? $record->catscaleid ?? 0;
```

Danach gilt `$record->catscaleid === $this->catscaleid`, sofern `$this->catscaleid`
gesetzt ist — und wenn es nicht gesetzt ist, scheitert die Bedingung an ihrem
ersten Operanden. `$this->catscaleid != $record->catscaleid` ist in beiden
Fällen `false`.

Übrig bleibt: Die `contextid` wird ausschließlich bei einer **neuen**
Testumgebung gesetzt (`!$record->contextid`). Bei jedem späteren Wechsel der
Hauptskala bleibt sie auf dem Kontext der alten Skala stehen.

Der Kommentar nennt zudem einen anderen Vergleich als der Code: dort steht
`$record->contextid != $this->contextid`, implementiert ist
`$this->catscaleid != $record->catscaleid`. Vermutlich ist beim Umbau der
Vergleichsgegenstand gewechselt worden, ohne die Reihenfolge anzupassen.

## Reproduktion

Nachgemessen gegen `local_catquiz` 1.2.1 (2026092616) auf Moodle 4.5.14+
(Build 20260916), PHP 8.3.6, PostgreSQL 16.15. Dasselbe Verhalten in 1.3.0
(2026092801) auf Moodle 5.1.7+, der Code ist dort unverändert.

```php
// Zwei Skalen in unterschiedlichen Kontexten.
$first  = dataapi::create_catscale(...);   // contextid 111000
$second = dataapi::create_catscale(...);   // contextid 111001

$testid = $DB->insert_record('local_catquiz_tests', (object)[
    'catscaleid' => $first->id,
    'contextid'  => $first->contextid,
    // ...
]);

$test = new testenvironment((object)[
    'id'         => $testid,
    'catscaleid' => $second->id,
    'json'       => json_encode(['catquiz_catscales' => $second->id]),
]);
$test->save_or_update();

$stored = $DB->get_record('local_catquiz_tests', ['id' => $testid]);
```

Ergebnis:

```text
catscaleid: 111... -> auf die zweite Skala aktualisiert
contextid:  111000 -> unverändert, erwartet wäre 111001
```

Die Skala wandert, der Kontext nicht. Die Testumgebung zeigt danach auf einen
Kontext, der nicht zu ihrer Skala gehört.

## Vorschlag

Den ursprünglichen Wert sichern, bevor er überschrieben wird:

```php
$previousscaleid = $record->catscaleid ?? 0;

$record->catscaleid = $this->catscaleid ?? $record->catscaleid ?? 0;
// ...

if (
    !property_exists($record, 'contextid')
    || !$record->contextid
    || ($record->catscaleid && $previousscaleid && $record->catscaleid != $previousscaleid)
) {
    $record->contextid = $DB->get_field('local_catquiz_catscales', 'contextid', ['id' => $record->catscaleid]);
}
```

Damit tut die Bedingung, was der Kommentar beschreibt, und das Verhalten für
neue Testumgebungen bleibt unverändert.

## Akzeptanzkriterien

- [ ] Ein Wechsel der Hauptskala setzt `contextid` auf den Kontext der neuen
      Skala.
- [ ] Eine neue Testumgebung bekommt weiterhin den Kontext ihrer Skala.
- [ ] Ein Speichern ohne Skalenwechsel lässt `contextid` unverändert.
- [ ] Ein PHPUnit-Test deckt den Wechsel ab.

## Verwandt

Aufgefallen beim Absichern von `block_catquiz_feedbackwizard`. Der Block
schreibt ausschließlich über `testenvironment::save_or_update()`, unter anderem
mit der Begründung, dass die Engine dabei die `contextid` nachführt. Ein Test,
der diese Wirkung wirklich misst statt sie zu behaupten, hat gezeigt, dass sie
ausbleibt — die Begründung war also falsch, und die Dokumentation des Blocks ist
entsprechend korrigiert worden.

Der Block hält den Ist-Zustand in
`tests/local_catquiz_adapter_test.php::test_scale_change_does_not_move_the_context_engine_defect`
fest. Dieser Test schlägt fehl, sobald der Punkt behoben ist — beabsichtigt,
damit eine Umgehung ihre Ursache nicht überlebt.
