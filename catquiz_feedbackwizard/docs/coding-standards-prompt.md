# KI-Systemprompt — Entwicklung für `block_catquiz_feedbackwizard`

Nutze diesen Prompt als Baseline für jede KI-gestützte Entwicklungsarbeit am Plugin.

---

## Projektkontext

Du arbeitest an einem Moodle-Block-Plugin mit dem technischen Namen **`block_catquiz_feedbackwizard`**. Fachlich ist das Produkt der **CATQuiz Settings Wizard**.

Das Plugin ist kein eigenständiges CAT-Fachsystem, sondern eine **Wizard-Oberfläche** für die Konfiguration von CAT-Tests auf Basis von:

- `local_catquiz`
- `mod_adaptivequiz`
- Moodle-Core-APIs für Kurse, Gruppen, Dateien und Rechte
- optionalen KI-Adaptern

---

## Verbindliche Projektregeln

1. Verwende in neuen UI-Strings und Dokumentation konsequent **„Settings Wizard“** statt „Feedback Wizard“.
2. Belasse den technischen Komponenten-Namen vorerst bei `block_catquiz_feedbackwizard`, sofern keine explizite Migrationsaufgabe vorliegt.
3. Halte das Block-Plugin **dünn**: Einstieg, Rendering, Wizard-Orchestrierung.
4. Dupliziere keine bestehende Fachlogik aus `local_catquiz`.
5. Lagere Fachverarbeitung in Services, Adapter und DTOs aus.
6. Riskante Funktionen sind standardmäßig **deaktiviert** und nur per Admin-Setting verfügbar:
   - Kursanlage / Kursantrag
   - Gruppenanlage
   - KI-Glättung / Textvariation
7. Speichere keine unnötigen plugin-spezifischen personenbezogenen Daten.
8. Behandle Draft-Persistenz als datensensible Übergangskomponente; bevorzuge kurzlebige oder TTL-basierte Lösungen.

---

## Coding-Regeln

### PHP / Moodle
- GPL-Boilerplate und File-Docblock in jeder PHP-Datei.
- `defined('MOODLE_INTERNAL') || die();` in nicht-direkten Einstiegspunkten.
- Eine Klasse pro Datei.
- Dateiname = Klassenname.
- Namespace = Pfad unter `classes/`.
- Short arrays `[]`.
- Eine Argumentzeile pro Zeile in Multi-Line-Calls.
- Keine Leerzeile nach Klassenöffnung.
- Keine Leerzeile vor finaler Klassenklammer.
- Docblocks für Datei, Klasse, Methoden, Properties, Konstanten.

### Architektur
- Keine Business-Logik in `block_catquiz_feedbackwizard.php`.
- Keine große Business-Logik in `wizard.php`; Schritte/Validierung möglichst in dedizierte Klassen auslagern.
- Kommunikation mit `local_catquiz`, `mod_adaptivequiz`, Kursen, Gruppen und KI nur über klar benannte Service-/Adapter-Klassen.
- Keine SQL-lastige Direktlogik in AMD, Template oder Block-Klasse.

### Datenschutz
- Prüfe jede neue Persistenz auf Datenminimierung.
- Keine personenbezogene Shadow-Historie.
- KI-Nutzlasten minimieren.
- Privacy-Provider und tatsächliche Speicherung müssen übereinstimmen.

### UI / Mustache / AMD
- Template-Verträge exakt einhalten.
- Kein Inline-Script außerhalb von Moodle-Konventionen.
- Keine versteckte Fachlogik im Frontend.
- Schrittübergänge serverseitig validieren.

### Tests
- PHPUnit für Services, Persistenz, Privacy, Im-/Export, Mapping.
- Behat für Wizard-Flow, Feature-Gating, Rollen und Review.
- Import-/Export-Roundtrip testen.

---

## Projektpräferenzen für Änderungen

Wenn du neue Funktionalität implementierst:

1. Beginne mit einer kleinen, klaren Service-Schnittstelle.
2. Halte Form-Feldnamen und internes DTO-Modell getrennt.
3. Implementiere Settings-Gating früh.
4. Dokumentiere bewusst, wenn ein Schritt nur Übergangslösung ist.
5. Weiche nicht vom Thin-Block-Prinzip ab.

Wenn du bestehende Stellen anfasst:

1. Benenne historisch irreführende Begriffe schrittweise in „Settings Wizard“ um.
2. Bewahre API-/Komponenten-Kompatibilität, solange keine Migration beschlossen wurde.
3. Markiere jede Stelle, an der aktueller Prototyp und Zielarchitektur auseinanderfallen.

