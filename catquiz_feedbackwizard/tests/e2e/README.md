# Interaktive Prüfung mit Playwright

Diese Skripte fahren den Wizard einmal komplett durch — als Lehrkraft, im
Browser, über alle sechs Schritte bis zum Schreibvorgang. Sie sind **kein
sechstes Gate**: sie laufen nicht in der CI, brauchen eine installierte
Instanz mit Testdaten und sind dafür gedacht, vor einer Auslieferung einmal
von Hand zu bestätigen, dass der Ablauf tatsächlich funktioniert.

Behat deckt diesen Weg nicht ab. Die Szenarien in `tests/behat/` tragen kein
`@javascript` und prüfen nur, ob der Einstiegspunkt erscheint. Der Wizard
selbst läuft über `core_form/modalform`, also über JavaScript, und ist ohne
echten Browser nicht erreichbar.

## Warum das nötig ist

Zwei Fehler haben alle fünf Gates überlebt und sind erst hier aufgefallen:

- `add_matching_step()` fehlte, obwohl `definition()` sie aufrief — Schritt 5
  lief in einen Fatal Error.
- `process_dynamic_submission()` las die Test-ID aus dem abgeschickten
  Formular. Das Auswahlfeld existiert aber nur in Schritt 1, ab Schritt 2 war
  die ID also immer `0`. Folge: die bestehende Konfiguration wurde nie
  geladen, und der Schreibvorgang am Ende lief gegen Test `0`.

Beides sind Fehler im Zusammenspiel der Schritte. PHPUnit prüft Methoden
einzeln, phpcs und moodlecheck prüfen Form, das AMD-Gate prüft den Build —
keines davon klickt den Wizard durch.

## Voraussetzungen

```bash
npm install playwright
npx playwright install --with-deps chromium
```

Eine laufende Moodle-Instanz mit der Engine, siehe `docs/dev/environment-setup.md`.
Der eingebaute PHP-Server braucht mehrere Worker, sonst blockiert er bei den
parallelen Anfragen des Browsers:

```bash
PHP_CLI_SERVER_WORKERS=8 php -S 127.0.0.1:8000 -t ~/moodle
```

## Testdaten anlegen

Die drei Seed-Skripte sind idempotent und legen an, was der Wizard braucht:

```bash
cd ~/moodle
php blocks/catquiz_feedbackwizard/tests/e2e/seed_scales.php       # Kurs, Lehrkraft, Skala, Subskala
php blocks/catquiz_feedbackwizard/tests/e2e/seed_adaptivequiz.php # Trägeraktivität mit catmodel=catquiz
php blocks/catquiz_feedbackwizard/tests/e2e/seed_block.php        # Block in den Kurs
```

`seed_adaptivequiz.php` setzt `attemptfeedback` ausdrücklich auf den leeren
String. Ohne dieses Feld bricht `adaptivequiz_add_instance()` mit einer
NOT-NULL-Verletzung ab — siehe
`docs/design/issue-adaptivequiz-attemptfeedback-notnull.md`.

`seed_block.php` setzt vorher den Nutzer über
`\core\session\manager::set_user()`. Ohne angemeldeten Nutzer scheitert
`add_block_at_end_of_default_region()` an `user_can_addto()` mit der wenig
sprechenden Meldung „Cannot add block".

## Durchlauf

```bash
COURSE_ID=4 node blocks/catquiz_feedbackwizard/tests/e2e/wizard.js
```

Umgebungsvariablen: `MOODLE_URL`, `MOODLE_USER`, `MOODLE_PASS`, `COURSE_ID`,
`SHOT_DIR`. Die Kurs-ID gibt `seed_scales.php` aus.

Das Skript legt pro Schritt einen Screenshot in `SHOT_DIR` ab (Default
`/tmp/catquiz-wizard-shots`), protokolliert die gefundenen Auswahlmöglichkeiten
und die Review-Zusammenfassung und endet mit Exit-Code 1, sobald eine
PHP-Meldung, ein Serverfehler oder ein nicht erreichter Schritt auftritt.

## Was danach zu prüfen ist

Der Durchlauf endet mit dem Speichern. Dass wirklich geschrieben wurde, zeigt
erst die Datenbank:

```bash
php -r 'define("CLI_SCRIPT",true); require("config.php"); global $DB;
$r = $DB->get_record("local_catquiz_tests", ["id" => 3]);
$j = json_decode($r->json, true);
echo "timemodified: {$r->timemodified}\n";
echo "maxquestions: " . $j["maxquestionsgroup"]["catquiz_maxquestions"] . "\n";
echo "Feedback-Schlüssel: " . count(array_filter(array_keys($j),
    fn($k) => str_starts_with($k, "feedback_scaleid_"))) . "\n";'
```

Die Feedbackbereiche landen im Schema der Engine
(`feedback_scaleid_limit_lower_<scale>_<n>`, `feedbackeditor_scaleid_<scale>_<n>`),
die Fragenzahl in `maxquestionsgroup.catquiz_maxquestions` — nicht als flacher
Schlüssel. Wer an der falschen Stelle sucht, hält einen erfolgreichen
Schreibvorgang für gescheitert.
