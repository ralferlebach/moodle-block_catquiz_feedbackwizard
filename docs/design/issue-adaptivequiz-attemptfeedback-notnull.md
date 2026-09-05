# [BUG] `attemptfeedback` ist NOT NULL ohne Default, `adaptivequiz_add_instance()` setzt es nicht

## Problem

`mdl_adaptivequiz.attemptfeedback` ist in `db/install.xml` als NOT NULL ohne
Default deklariert:

```xml
<FIELD NAME="attemptfeedback" TYPE="text" NOTNULL="true" SEQUENCE="false"
       COMMENT="Feedback given to students when their attempt has been completed."/>
<FIELD NAME="attemptfeedbackformat" TYPE="int" LENGTH="2" NOTNULL="true" DEFAULT="0"
       SEQUENCE="false" COMMENT="Format of attempt feedback"/>
```

Ein Default ist bei `TYPE="text"` in XMLDB nicht möglich, die Spalte muss also
vom Code befüllt werden. `adaptivequiz_add_instance()` in `lib.php:106` tut das
für das Begleitfeld, aber nicht für das Feld selbst:

```php
$adaptivequiz->timecreated  = $time;
$adaptivequiz->timemodified = $time;
$adaptivequiz->attemptfeedbackformat = 0;   // gesetzt

$instance = $DB->insert_record('adaptivequiz', $adaptivequiz);
```

`attemptfeedback` wird ausschließlich vom Formularfeld in `mod_form.php:123`
geliefert. Fehlt das Feld im übergebenen Objekt, bricht der Insert ab:

```text
ERROR: null value in column "attemptfeedback" of relation "mdl_adaptivequiz"
violates not-null constraint
```

Über die Oberfläche ist das folgenlos: das Textarea liefert immer einen Wert,
notfalls den leeren String. Betroffen sind alle Wege, die eine Instanz
programmatisch anlegen und dabei nur die fachlich nötigen Felder setzen —
Generatoren, Seed- und Provisionierungsskripte, Webservice- und
Automatisierungscode. Genau diese Wege setzen `attemptfeedback` nicht, weil es
für einen CAT-Test ohne Bedeutung ist: bei `catmodel = catquiz` kommt das
Feedback aus `local_catquiz`, nicht aus diesem Feld.

Auffällig ist die Asymmetrie: die Funktion normalisiert `attemptfeedbackformat`
ausdrücklich, lässt das zugehörige Inhaltsfeld aber aus. `timecreated` und
`timemodified` werden ebenfalls gesetzt. `attemptfeedback` ist das einzige
NOT-NULL-Feld ohne Default, das die Funktion dem Aufrufer überlässt.

## Reproduktion

Zwei Instanzen anlegen, die sich in genau einem Feld unterscheiden:

```php
$data = (object)[
    'course' => $course->id, 'modulename' => 'adaptivequiz', 'module' => $moduleid,
    'name' => 'Proof', 'intro' => '', 'introformat' => FORMAT_HTML,
    'section' => 1, 'visible' => 1, 'cmidnumber' => '',
    'startinglevel' => 5, 'minimumquestions' => 5, 'maximumquestions' => 20,
    'standarderror' => 5, 'lowestlevel' => 1, 'highestlevel' => 100,
    'grademethod' => 1, 'attempts' => 0, 'showabilitymeasure' => 0,
];
add_moduleinfo($data, $course);                   // A
$data->attemptfeedback = '';
add_moduleinfo($data, $course);                   // B
```

Nachgemessen gegen `mod_adaptivequiz` 3.0.0 (2026082705), Moodle 4.5.13+
(Build 20260903), PHP 8.3.6, PostgreSQL 16.15:

```text
A: FAILED -> Error writing to database
   ERROR: null value in column "attemptfeedback" ... violates not-null constraint
B: created instance 15
   stored attemptfeedback='' format='0'
```

Der Abbruch erfolgt innerhalb der Transaktion in `add_moduleinfo()`, das
Kursmodul wird also mit zurückgerollt. Die sichtbare Meldung lautet nur
„Error writing to database"; die Ursache steht erst in der Debug-Info und ist
ohne `DEBUG_DEVELOPER` nicht zu sehen.

## Vorschlag

In `adaptivequiz_add_instance()` neben dem Format auch das Feld normalisieren:

```php
$adaptivequiz->attemptfeedback       = $adaptivequiz->attemptfeedback ?? '';
$adaptivequiz->attemptfeedbackformat = 0;
```

Damit verhält sich das Feld wie sein Begleitfeld, und der Weg über die
Oberfläche ändert sich nicht: dort ist der Wert ohnehin immer gesetzt.

Alternative, falls das Feld künftig ohnehin optional sein soll: die Spalte in
einem Upgrade-Schritt auf `NOTNULL="false"` ändern. Das ist die größere
Änderung und berührt Backup/Restore, deshalb ist die Normalisierung im Code
hier der kleinere Eingriff.

`adaptivequiz_update_instance()` braucht keine Anpassung: dort kommt der Wert
aus dem geladenen Datensatz, ist also nie NULL.

## Akzeptanzkriterien

- [ ] `adaptivequiz_add_instance()` legt eine Instanz an, wenn `attemptfeedback`
      im übergebenen Objekt fehlt.
- [ ] Ein über die Oberfläche eingegebener Feedbacktext wird unverändert
      gespeichert.
- [ ] Ein PHPUnit-Test deckt den Aufruf ohne `attemptfeedback` ab.

## Verwandt

Aufgefallen beim Aufsetzen einer Testinstanz für
`block_catquiz_feedbackwizard`: das Seed-Skript legt eine `mod_adaptivequiz`-
Instanz mit `catmodel = catquiz` an, um einen CAT-Test zu erzeugen, und setzt
`attemptfeedback` nicht, weil das Feedback bei diesem Catmodel aus
`local_catquiz` kommt.

Der Punkt ist in `local_catquizlab` bereits als „NOT-NULL-Spalte der
Trägeraktivität" in der Liste der Defekte vermerkt, die erst ein Lauf gegen die
echte Engine sichtbar macht. Dies ist die ausformulierte Fassung dazu.

**Repository:** Das Issue gehört zu `mod_adaptivequiz`, nicht zu
`local_catquiz`. Die fehlgeschlagene Anweisung steht in
`mod/adaptivequiz/lib.php`, die Spalte in `mod/adaptivequiz/db/install.xml`;
`local_catquiz` ist an diesem Pfad nicht beteiligt.
