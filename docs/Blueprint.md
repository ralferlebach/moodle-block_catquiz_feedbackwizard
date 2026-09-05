# Blueprint — Zielarchitektur für `block_catquiz_feedbackwizard`

## 1. Architekturrolle des Plugins

`block_catquiz_feedbackwizard` ist **kein zweites `local_catquiz`**. Das Plugin ist eine **fachlich geführte Wizard-Oberfläche** mit Orchestrierungslogik.

### Leitprinzip
- **Block:** UI, Wizard-State, Validierung, Orchestrierung
- **local_catquiz:** Fachmodell, Testumgebungen, Skalen, Kontexte, Strategien, Feedback
- **mod_adaptivequiz:** Zielaktivität / Testinstanz
- **Moodle-Core:** Kurse, Gruppen, Dateien, Rechte
- **optionale KI-Schicht:** textuelle Überarbeitung, nie Kernlogik

## 2. Zielbild des Nutzerflusses

### Modus A — Neu anlegen
1. Kurs öffnen
2. Settings Wizard starten
3. Zweck/Szenario wählen
4. Adaptive-Quiz-/CAT-Basis festlegen
5. Feedbackquellen einlesen
6. Anschlussregeln definieren
7. optionale Automatisierung/KI anwenden
8. Review und Speichern

### Modus B — Bestehenden Test übernehmen
1. Testvorlage aus berechtigtem Bestand wählen
2. Konfiguration laden
3. Differenz / Zielkontext anpassen
4. wie Modus A fortsetzen

### Modus C — Muster importieren
1. Importdatei wählen
2. Format validieren
3. Mapping prüfen
4. fehlende Ziele / Konflikte anzeigen
5. bearbeiten und übernehmen

## 3. Fachliche Wizard-Schritte

Sechs Schritte, `MAXSTEPS = 6` in `classes/form/wizard.php`. Die Titel
entsprechen den Lang-Strings `step01:title` bis `step06:title`.

### Schritt 1 — CAT-Test wählen
- Liste der adaptiven Tests des Kurses
- Bereitschaftsanzeige je Test (fertig / mit Warnungen / unvollständig)
- kursgebunden: fremde Test-IDs werden abgewiesen

### Schritt 2 — Einrichtungsmodus wählen
- `edit` — den gewählten Test bearbeiten
- `clone` — Konfiguration eines anderen Tests desselben Kurses übernehmen
- `scenario` — mit einer Szenario-Vorlage beginnen
- `import` — Einstellungsmuster als JSON-Datei einlesen

### Schritt 3 — Testeinstellungen bearbeiten
- Hauptskala und Subskalen
- Fragenzahl, Mindestfragenzahl, Fragen je Subskala
- Präzisionsmodus, Zeitbegrenzung, Abschlussbedingung

### Schritt 4 — Feedbackbereiche konfigurieren
- bis zu zehn Bereiche mit Bezeichnung, Grenzen und Text
- Platzhalter im Text, Templateformat je Bereich
- Reporting-Strategie
- **falls freigeschaltet:** Kurs- und Gruppenaktionen je Bereich
- **falls freigeschaltet:** KI-Glättung der Texte

### Schritt 5 — Anschlussregeln (Matching)
- Zuordnungsmodus: keine, Einzelregel oder CSV
- Kursfeld, Operator, Muster, Zieltyp und Zielwert
- CSV-Variante für mehrere Regeln auf einmal

### Schritt 6 — Review und Persistenz
- Zusammenfassung aller Entscheidungen, inklusive Import- und KI-Meldungen
- Export als Einstellungsmuster
- Speichern über `local_catquiz_adapter`

### Abweichung vom ursprünglichen Entwurf

Vorgesehen war Schritt 5 als „Optionale Automatisierung / KI" und Schritt 3 als
„Feedbackquellen" mit Import aus Seriendokumenten. Umgesetzt ist:

- Die Anschlussregeln haben Schritt 5 bekommen, weil sie einen eigenen Schritt
  brauchen.
- Die optionalen Funktionen sitzen dort, wo sie wirken: Kurs- und
  Gruppenaktionen je Feedbackbereich in Schritt 4, die KI-Glättung ebenfalls in
  Schritt 4, direkt unter den Texten, die sie bearbeitet. Ein siebter Schritt
  hätte `MAXSTEPS` und die AMD-Navigation angefasst, ohne fachlich etwas zu
  gewinnen.
- Der Import aus Seriendokumenten ist nicht umgesetzt.
  `feedback_template_service` kann Platzhalter ersetzen, aber keine Datei
  einlesen. Der Musterimport in Schritt 2 ist etwas anderes: er liest eine
  vollständige Wizard-Konfiguration, keine Textquelle.

## 4. Struktur im Plugin

Der Blueprint hat ursprünglich je eine Klasse pro Wizard-Schritt und einen
Servicezuschnitt entlang der Fachfunktionen vorgesehen. Umgesetzt ist ein
anderer Schnitt: die Schritte sind Methoden in `wizard.php`, und die Services
sind entlang „lesen / normalisieren / schreiben" getrennt statt entlang der
Fachfunktion. Das ist eine bewusste Entscheidung und keine offene Baustelle —
der beschriebene Stand unten ist der verbindliche.

```text
blocks/catquiz_feedbackwizard/
├── amd/src/main.js              # Modal-Steuerung, Bundle unter amd/build/
├── classes/
│   ├── catquiz_data.php         # Lesezugriff auf die Engine-Tabellen
│   ├── form/
│   │   └── wizard.php           # dynamic_form, alle sechs Schritte als Methoden
│   ├── local/
│   │   ├── adapter/
│   │   │   ├── local_catquiz_adapter.php   # jeder Schreibzugriff auf die Engine
│   │   │   └── ai_provider_adapter.php     # core_ai, kein direkter Provider
│   │   └── service/
│   │       ├── feature_settings_service.php  # Gating aller optionalen Features
│   │       ├── test_config_normalizer.php    # Engine-JSON -> Wizard-State
│   │       ├── test_config_writer.php        # Wizard-State -> Engine-JSON
│   │       ├── scenario_preset_service.php   # Szenario-Vorlagen
│   │       ├── feedback_template_service.php # Platzhalter in Feedbacktexten
│   │       ├── matching_config_service.php   # Routing-Regeln, Regel und CSV
│   │       ├── pattern_export_service.php    # Einstellungsmuster schreiben
│   │       ├── pattern_import_service.php    # Einstellungsmuster prüfen und laden
│   │       ├── ai_feedback_service.php       # optionale Textglättung
│   │       └── draft_cleanup_service.php     # Aufbewahrung der Entwürfe
│   ├── persistent/draft.php
│   ├── privacy/provider.php
│   └── task/cleanup_drafts.php
├── db/                          # access.php, install.xml, tasks.php, upgrade.php
├── docs/dev/environment-setup.md
├── export.php                   # Download eines Einstellungsmusters
├── lang/en/
├── settings.php
├── templates/block.mustache
└── tests/                       # PHPUnit und tests/behat/
```

Nicht umgesetzt und derzeit auch nicht vorgesehen:
`course_provisioning_service` und `group_provisioning_service` — die
zugehörigen Settings und Formularfelder existieren, aber der Wizard schreibt
die Anschlussaktionen bisher nur in die Konfiguration, ohne selbst Kurse oder
Gruppen anzulegen. Ebenso fehlt ein `adaptivequiz_adapter`: der Block liest die
Aktivität nur mit, geschrieben wird ausschließlich über `local_catquiz`.

## 5. Zentrale Services

Die Namen unten sind die tatsächlichen. Der ursprüngliche Zuschnitt
(`source_clone_service`, `routing_rules_service`, `wizard_state_service`,
`finalise_wizard_service`) ist aufgegangen in einem Schnitt entlang
„lesen / normalisieren / schreiben".

### 5.1 `test_config_normalizer`
- Engine-JSON in einen flachen Wizard-State überführen,
- Vorgabewerte für Feedbackbereiche und Matching berechnen,
- Werte aus untrusted Quellen normalisieren.
Ersetzt den vorgesehenen `source_clone_service` und `wizard_state_service`.

### 5.2 `test_config_writer`
- Wizard-State auf die Engine-JSON-Struktur abbilden,
- Persistenz an `local_catquiz_adapter` übergeben.
Ersetzt den vorgesehenen `finalise_wizard_service`.

### 5.3 `feature_settings_service`
- einziger Lesepunkt für die Admin-Settings,
- serverseitiges Entfernen von State zu abgeschalteten Features
  (`sanitise_wizard_state()`).
Im ursprünglichen Blueprint nicht vorgesehen; ohne diese Bündelung würde jede
Schrittmethode ihre eigene Gating-Prüfung mitbringen.

### 5.4 `feedback_template_service`
- Platzhalter in Feedbacktexten erkennen und ersetzen,
- Templateformat normalisieren.
Der im Blueprint genannte Import aus Serienquellen ist nicht umgesetzt.

### 5.5 `matching_config_service`
- Routing-Regeln als Einzelregel oder CSV modellieren,
- Regeln normalisieren und Unvollständiges verwerfen.
Ersetzt den vorgesehenen `routing_rules_service`.

### 5.6 `scenario_preset_service`
- Szenario-Vorlagen für den Startmodus liefern.

### 5.7 `pattern_export_service` / `pattern_import_service`
- versioniertes Muster schreiben, ohne Instanz- oder Personenbezug,
- Muster prüfen, Skalenreferenzen gegen die lokale Site auflösen,
- Abweichungen als Warnungen zurückgeben statt still zu korrigieren.

### 5.8 `ai_feedback_service`
- nur bei aktivem Setting und konfiguriertem `core_ai`-Provider,
- Prompt aus Systemprompt, Hinweisen und Text zusammensetzen,
- datensparsame Nutzlast an `ai_provider_adapter` übergeben,
- Original behalten, wenn die Antwort Platzhalter verliert.

### 5.9 `draft_cleanup_service`
- Entwürfe nach Ablauf der TTL löschen,
- abgesendete Entwürfe nach kurzer Karenz löschen.

### Nicht umgesetzt
`course_provisioning_service` und `group_provisioning_service`. Die Settings
und die Formularfelder für Kurs- und Gruppenaktionen existieren, aber der
Wizard schreibt sie bisher nur in die Konfiguration und legt selbst nichts an.

## 6. Empfohlenes Datenmodell

### 6.1 Wizard-State
Für die interne Arbeit soll ein **DTO-/Array-Modell** genutzt werden, getrennt von Persistenz und Form-Feldnamen.

Beispiel:

```json
{
  "mode": "clone",
  "purpose": "placement_test",
  "source": {
    "courseid": 42,
    "adaptivequizid": 15,
    "testenvironmentid": 7
  },
  "target": {
    "courseid": 42,
    "categoryid": 9
  },
  "feedback": {
    "template_source": "mustache_document",
    "ranges": []
  },
  "routing": {
    "rules": []
  },
  "automation": {
    "create_missing_courses": false,
    "create_missing_groups": false,
    "use_ai_refinement": false
  }
}
```

### 6.2 Persistenzprinzip
- dauerhafte Konfigurationsdaten in Zielsystemen (`local_catquiz` etc.)
- Wizard-Drafts nur kurzlebig
- keine personenbezogene Fachhistorie im Block

## 7. Datenschutz-Blueprint

### Aktueller Konflikt
Der Prototyp speichert Drafts in `block_catquiz_feedbackwizard` mit `userid`, `courseid` und `datajson`.

### Zielentscheidung
Vor Produktivsetzung ist eine der folgenden Strategien verbindlich umzusetzen:

1. **Session-/Cache-basierte Entwürfe** ohne dauerhafte personenbezogene Tabelle,
2. **TTL-basierte Minimalpersistenz** mit automatischer Bereinigung,
3. **begründete Minimalpersistenz** mit klar dokumentierter Notwendigkeit und Löschstrategie.

Empfehlung: **Strategie 1 oder 2**.

## 8. Admin-Settings-Blueprint

Empfohlene Settings:

- `enable_courseprovisioning`
- `enable_groupautocreate`
- `enable_ai_feedback_refinement`
- `ai_feedback_systemprompt`
- `allowed_target_categories`
- `pattern_import_maxfilesize`
- `pattern_export_include_feedback_texts`
- `draft_ttl_hours`

Alle riskanten Funktionen: **Default aus**.

## 9. Import-/Export-Format

Empfehlung:

- strukturiertes JSON mit Versionsfeld,
- klare Trennung von Metadaten, Wizard-State und abgeleiteten Regeln,
- keine personenbeziehbaren Daten,
- optional eingebettete oder referenzierte Feedbackvorlagen.

Beispiel:

```json
{
  "format": "catquiz-settings-pattern",
  "version": 1,
  "exported_at": "2026-04-22T12:00:00Z",
  "settings": {},
  "feedback": {},
  "routing": {}
}
```

## 10. Umsetzungsetappen

### Phase 1 — Produktgerüst stabilisieren
- Terminologie auf „Settings Wizard“ festziehen
- echte Settings anlegen
- Architektur-Services einführen
- bisherigen Draft-/Wizard-Code technisch bereinigen

### Phase 2 — Vorlagen und Übernahme
- bestehende CAT-Tests und Testumgebungen lesbar machen
- Copy-/Clone-Flows implementieren
- Review der Berechtigungen

### Phase 3 — Feedback-Serienlogik
- Importformat definieren
- Parser / Mapper bauen
- UI für Ergebnisbereiche und Vorlagen

### Phase 4 — Routing-Regeln
- Zielkurse und Zielgruppen prüfen
- Regelmodell und Validierung aufbauen

### Phase 5 — optionale Automatisierung
- Kursanlage / Antrag
- Gruppenanlage
- nur bei aktivem Setting

### Phase 6 — optionale KI
- Adapter-Schicht
- Prompt-Handling
- datensparsame Übergabe

### Phase 7 — Export, Bereinigung, Tests
- Pattern Export / Import Roundtrip
- Draft-Bereinigung
- Privacy-/PHPUnit-/Behat-Abdeckung

## 11. Explizite Architekturentscheidungen

- User-facing Begriff: **Settings Wizard**.
- Technischer Component-Name bleibt vorerst unverändert.
- Der Block schreibt nicht direkt beliebige Fachstrukturen „frei Hand“, sondern über wohldefinierte Service- und Adapter-Schichten.
- Kein direkter Provider-Code in Formklassen.
- Keine neue fachliche Duplikation zu `local_catquiz`.
- Riskante oder organisatorisch invasive Features nur mit Admin-Opt-in.

