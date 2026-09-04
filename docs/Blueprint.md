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

### Schritt 1 — Startmodus und Zweck
- Neu / Übernehmen / Import
- verständlicher Einsatzzweck
- Zielkurs / Zielinstanz / Konfigurationsziel

### Schritt 2 — Fachliche Basis
- Auswahl einer vorhandenen CAT-Testumgebung oder Vorlage
- Auswahl von Skala, Kontext, Strategie
- ggf. Ableitung aus bestehendem Test

### Schritt 3 — Feedbackquellen
- Einlesen von Seriendokumenten / Mustervorlagen
- Mapping auf Fähigkeits- oder Ergebnisbereiche
- Validierung fehlender Felder / Platzhalter

### Schritt 4 — Anschlussregeln
- Regeln: Ergebnisbereich → Zielkurs → Zielgruppe
- Anzeige vorhandener / fehlender Ziele
- Konfliktprüfung

### Schritt 5 — Optionale Automatisierung / KI
- falls freigeschaltet: Kursanlage / Antrag
- falls freigeschaltet: Gruppenanlage
- falls freigeschaltet: KI-Glättung / Variation

### Schritt 6 — Review und Persistenz
- Zusammenfassung aller Entscheidungen
- Validierung gegen Zielsystem
- Speichern in `local_catquiz`
- optional Export als Muster

## 4. Empfohlene Zielstruktur im Plugin

```text
block/catquiz_feedbackwizard/
├── amd/src/
├── classes/
│   ├── form/
│   │   ├── wizard.php
│   │   └── step/
│   │       ├── start_step.php
│   │       ├── source_step.php
│   │       ├── feedback_step.php
│   │       ├── routing_step.php
│   │       ├── automation_step.php
│   │       └── review_step.php
│   ├── local/
│   │   ├── service/
│   │   │   ├── wizard_state_service.php
│   │   │   ├── source_clone_service.php
│   │   │   ├── feedback_template_service.php
│   │   │   ├── routing_rules_service.php
│   │   │   ├── course_provisioning_service.php
│   │   │   ├── group_provisioning_service.php
│   │   │   ├── ai_feedback_service.php
│   │   │   ├── pattern_import_service.php
│   │   │   ├── pattern_export_service.php
│   │   │   └── finalise_wizard_service.php
│   │   ├── adapter/
│   │   │   ├── local_catquiz_adapter.php
│   │   │   ├── adaptivequiz_adapter.php
│   │   │   └── ai_provider_adapter.php
│   │   ├── dto/
│   │   └── validation/
│   ├── persistent/
│   └── privacy/
├── db/
├── lang/
├── templates/
└── tests/
```

## 5. Zentrale Services

### 5.1 `source_clone_service`
Verantwortung:
- bestehende CAT-Konfiguration lesen,
- berechtigte Vorlagenlisten aufbauen,
- Konfiguration in Wizard-DTO überführen.

### 5.2 `feedback_template_service`
Verantwortung:
- Serienquellen lesen,
- Platzhalter prüfen,
- Ergebnisbereich-Mapping auflösen,
- generierte Feedbacktexte vorbereiten.

### 5.3 `routing_rules_service`
Verantwortung:
- Regeln für Kurs-/Gruppenzuordnung modellieren,
- Zielobjekte validieren,
- Konflikte melden,
- finale Routing-Daten für Persistenz erzeugen.

### 5.4 `course_provisioning_service`
Verantwortung:
- nur bei aktivem Setting,
- Existenz von Zielkursen prüfen,
- Kurse anlegen oder Antragsobjekte erzeugen.

### 5.5 `group_provisioning_service`
Verantwortung:
- Zielgruppen validieren,
- fehlende Gruppen anlegen, wenn erlaubt.

### 5.6 `ai_feedback_service`
Verantwortung:
- nur bei aktivem Setting,
- Prompt und Textmaterial zusammenführen,
- datensparsame Nutzlast an KI-Adapter übergeben,
- Ergebnis protokollfrei oder minimal rückgeben.

### 5.7 `finalise_wizard_service`
Verantwortung:
- Gesamtkonfiguration prüfen,
- in Zielstruktur für `local_catquiz` überführen,
- Persistenz orchestrieren,
- Exportmuster optional erzeugen.

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

