# Moodle Plugin Coding Styles — `block_catquiz_feedbackwizard`

Diese Regeln gelten als projektspezifischer Standard für die Weiterentwicklung des CATQuiz Settings Wizard.

## 1. Grundprinzipien

- Moodle-konform vor „clever“.
- Lesbarkeit vor Abkürzung.
- Fachlogik nicht in Block-Klasse oder Mustache-Template verstecken.
- Keine Parallelimplementierung vorhandener `local_catquiz`-Fachlogik.
- Alle optionalen Automatisierungsfunktionen werden defensiv implementiert.

## 2. Terminologie

- **UI-/Doku-Begriff:** `CATQuiz Settings Wizard`
- **Technischer Komponentenname:** `block_catquiz_feedbackwizard`
- In neuen Nutzertexten kein „Feedback Wizard“ mehr verwenden.
- In Code-Kommentaren nur dann „feedback wizard“, wenn ausdrücklich historischer Ist-Stand beschrieben wird.

## 3. PHP-Regeln

- Jeder PHP-Datei voranstellen: GPL-Boilerplate + File-Docblock.
- In nicht-direkten Einstiegspunkten unmittelbar danach `defined('MOODLE_INTERNAL') || die();`.
- Eine Klasse pro Datei.
- Dateiname = Klassenname.
- Namespace = Pfad unter `classes/`.
- Short array syntax `[]`.
- Eine Argumentzeile pro Zeile in Multi-Line-Calls.
- Keine Leerzeile direkt nach Klassen-`{`.
- Keine Leerzeile vor schließender Klassenklammer.
- Lokale Variablen in `snake_case`.
- Properties ohne führende Unterstriche.
- Docblocks für Datei, Klasse, Properties, Methoden, Konstanten.

## 4. Architekturregeln

### 4.1 Thin Block Principle
- `block_catquiz_feedbackwizard.php` enthält nur Einstieg, Capability-Prüfung und Rendering.
- Fachliche Verarbeitung gehört in `classes/local/service/*`.

### 4.2 Form-Regeln
- `classes/form/wizard.php` darf Wizard-Fluss steuern, aber keine breite Business-Logik enthalten.
- Schrittlogik wenn möglich in dedizierte Step-Klassen auslagern.
- Validierung je Schritt kapseln.
- Zielmodell nicht an rohe Form-Feldnamen koppeln.

### 4.3 Adapter-Regeln
- Kommunikation mit `local_catquiz`, `mod_adaptivequiz`, Kurs-/Gruppenlogik und KI nur über Services/Adapter.
- Keine Provider-spezifischen oder SQL-lastigen Direktzugriffe in Templates, AMD oder Block-Klasse.

## 5. Datenschutzregeln

- Keine unnötige plugin-spezifische Speicherung personenbezogener Daten.
- Draft-Daten nur minimal und kurzlebig halten.
- KI-Nutzlasten datensparsam gestalten.
- Privacy-Provider nur das dokumentieren, was tatsächlich nötig und fachlich gewollt ist.
- Vor jeder neuen Persistenz prüfen: „Brauchen wir diese Daten wirklich dauerhaft?“

## 6. Settings-Regeln

- Risikofunktionen nur per Admin-Opt-in.
- Default für Kursanlage, Gruppenanlage und KI-Glättung: `aus`.
- UI muss deaktivierte Features klar ausblenden oder als nicht verfügbar markieren.

## 7. Mustache-Regeln

- Template-Datei und Render-Vertrag sauber abstimmen.
- Keine versteckte Business-Logik im Template.
- `{{variable}}` statt `{{{variable}}}`, außer kontrolliert vertrauenswürdiges HTML.
- JavaScript nur in `{{#js}}` oder AMD.
- stabile Root-Selectoren / `data-region` verwenden.

## 8. AMD-/JS-Regeln

- Keine `console.log()` in finalem Code.
- Funktionen vor Verwendung definieren.
- Kein stilles Durchfallen bei Fehlern; Nutzerfeedback über Moodle-Notifications.
- Keine direkte Fachentscheidung nur im Frontend.
- Back-/Forward-/Submit-Verhalten des Wizards immer serverseitig absichern.

## 9. Testing-Regeln

- PHPUnit für Services, Privacy, Mapping und Persistenz.
- Behat für Wizard-Flow, Rollen-/Capability-Verhalten und UI-Gating.
- Jede datenschutzrelevante Persistenz bekommt Tests.
- Import-/Export-Formate mit Roundtrip-Tests absichern.

## 10. Review-Checkliste

Vor jeder Auslieferung prüfen:

- Wurde „Settings Wizard“ in neuen UI-Strings korrekt verwendet?
- Liegt Fachlogik außerhalb von Block-Klasse und Template?
- Sind optionale Features durch Settings geschützt?
- Speichern wir neue personenbezogene Daten? Falls ja: warum?
- Ist der Wizard-State vom Zielmodell sauber getrennt?
- Gibt es klare Fehler- und Validierungsmeldungen?
- Sind Version, Lang-Strings, Privacy und Tests konsistent?

