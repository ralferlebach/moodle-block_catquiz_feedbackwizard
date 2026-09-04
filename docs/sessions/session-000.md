# Kontext-Dokument — CATQuiz Settings Wizard
Erstellt: 2026-04-22
Sitzung Nr.: 001

> Hinweis: Dieses Dokument wurde retrospektiv aus der vorliegenden Codebasis, dem analysierten Referenz-Plugin `local_catquiz` und der fachlichen Zielbeschreibung erzeugt. Es ist ein belastbarer Startpunkt, aber kein historisches Protokoll realer Entwicklungssitzungen.

## 1. Aktueller Projektstand

- **Aktuelle Phase:** Architektur- und Anforderungsfestigung vor der eigentlichen Fachimplementierung
- **Ziel dieser Phase:** Aus dem vorhandenen Alpha-Prototyp einen klar definierten Settings Wizard mit sauberem Zielbild ableiten
- **Was in dieser Sitzung konkret erledigt wurde:**
  - vorhandenen Quellcode des Block-Plugins analysiert
  - Abhängigkeit zu `local_catquiz` und `mod_adaptivequiz` fachlich eingeordnet
  - Ziel-User-Stories aus der Produktbeschreibung verdichtet
  - Lastenheft, Pflichtenheft, Blueprint, Coding-Styles und Prompt-Templates erstellt
  - Ist-/Soll-Lücke und priorisierte Architekturentscheidungen dokumentiert

## 2. Finalisierte Artefakte

### Bereits im Plugin vorhanden (Ist-Stand)

| Pfad | Zweck | Einschränkungen / TODOs |
|---|---|---|
| `block_catquiz_feedbackwizard.php` | Einstiegspunkt des Blocks, Capability-Check, Template-Render | nur einfacher Einstieg, keine Fachlogik |
| `classes/form/wizard.php` | Dynamic-Form-Wizard mit 6 Schritten und Draft-Speicherung | Schritte 2–6 fachlich weitgehend leer / Platzhalter |
| `classes/catquiz_data.php` | einfache Datenabfrage vorhandener Adaptive-Quiz/CAT-Zuordnungen im Kurs | Methodennamenfehler (`get_catquiz_by_couseid`), Fachbreite unzureichend |
| `classes/persistent/draft.php` | Persistenz für Wizard-Drafts | speichert `userid` und `datajson`; datenschutzfachlich nur Übergang |
| `classes/privacy/provider.php` | Privacy-Provider für aktuelle Draft-Persistenz | muss nach finaler Datenstrategie ggf. angepasst werden |
| `db/install.xml` | Tabelle für Wizard-Drafts | aktuell personenbezogener Bezug über `userid` |
| `db/access.php` | Capabilities für Blockinstanz und Nutzung | genügt nicht für feinere Zielbild-Rechte |
| `settings.php` | Admin-Settings-Stub | faktisch leer |
| `amd/src/main.js` | Modal-/Wizard-Navigation im Frontend | auf aktuellen Prototyp zugeschnitten |
| `templates/block.mustache` | einfacher Button zum Starten des Wizards | nur Minimal-UI |

### In dieser Doku-Sitzung erstellt

| Pfad | Zweck | Einschränkungen / TODOs |
|---|---|---|
| `docs/Lastenheft.md` | Fachliche Anforderungen aus Sicht der Stakeholder | muss bei Scope-Änderungen fortgeschrieben werden |
| `docs/Pflichtenheft.md` | Konkrete Systemanforderungen und Abnahmekriterien | einzelne Schnittstellenentscheidungen noch verfeinerbar |
| `docs/Blueprint.md` | Zielarchitektur und Phasenmodell | technische Detailentscheidungen je Phase noch auszuarbeiten |
| `docs/Moodle-Plugin Coding-Styles.md` | Projektspezifische Coding-Regeln | bei Team-/CI-Anforderungen weiter ergänzbar |
| `docs/coding-standards-prompt.md` | kompakter KI-Systemprompt | muss mit der Architektur synchron bleiben |
| `docs/prompt-templates/sessionstart.txt` | Startprompt für neue Arbeitssitzungen | ggf. um Repo-URLs ergänzen |
| `docs/prompt-templates/sessionende.txt` | Abschluss-Prompt für Folgesitzungen | Struktur stabil |
| `docs/sessions/session-template.md` | Vorlage für neue Sitzungsdokumente | anpassbar |

## 3. Offene Arbeitspakete (priorisiert)

1. **Terminologie und Scope im Code bereinigen** — „Feedback Wizard“ auf „Settings Wizard“ umstellen, ohne den technischen Komponenten-Namen zu ändern.
2. **Echte Admin-Settings implementieren** — Gating für Kursanlage/Kursantrag, Gruppenanlage, KI-Glättung, Systemprompt und Draft-TTL.
3. **Wizard-Architektur entkoppeln** — Business-Logik aus `classes/form/wizard.php` in Services/Step-Klassen auslagern.
4. **Clone-/Vorlagenlogik implementieren** — bestehende `local_catquiz`-Testumgebungen als Vorlagen lesen und in Wizard-State überführen.
5. **Feedback-Template-Import definieren** — Austauschformat, Parser, Mapping und UI für Serienlogik.
6. **Routing-Regeln für Kurse/Gruppen bauen** — Zielobjekte validieren, Konflikte zeigen, spätere Automatisierung vorbereiten.
7. **Datensparsame Draft-Strategie festlegen** — Session/Cache oder TTL-Persistenz statt unbefristeter personenbezogener Drafts.
8. **Review-/Finalisierungsschicht umsetzen** — finale Konfiguration validiert nach `local_catquiz` persistieren.
9. **Import-/Export für Einstellungsmuster ergänzen** — strukturiertes versionsfähiges JSON-Format mit Roundtrip-Tests.
10. **Privacy-, PHPUnit- und Behat-Abdeckung aufbauen** — besonders für Persistenz, Settings-Gating und Wizard-Flows.

## 4. Architekturentscheidungen (verbindlich)

- Nutzerseitige/fachliche Bezeichnung ist **CATQuiz Settings Wizard**.
- Der technische Komponentenname bleibt vorerst **`block_catquiz_feedbackwizard`**.
- Das Block-Plugin ist **UI-/Orchestrierungsschicht**, nicht neues Fachkernsystem.
- Fachlogik aus `local_catquiz` wird **nicht dupliziert**.
- Riskante Features sind **standardmäßig deaktiviert** und nur per Admin-Opt-in sichtbar/nutzbar.
- Das Plugin soll **keine unnötigen plugin-spezifischen personenbezogenen Daten** dauerhaft speichern.
- Der aktuelle Draft-Mechanismus ist **eine Übergangslösung**, keine bestätigte Zielarchitektur.
- Neue Integrationen mit KI oder Provisionierung dürfen **nicht direkt in Formklasse oder AMD** implementiert werden, sondern nur über Services/Adapter.

## 5. Kritische Abhängigkeiten

```text
settings.php
  └── Feature-Gating in Wizard-Steps und Services

local_catquiz Adapter
  └── Clone-/Vorlagenlogik
        └── Review-/Finalisierungsschicht

Feedback-Template-Service
  └── Routing-Rules-Service
        └── optionale Kurs-/Gruppen-Provisionierung

AI-Feedback-Service
  └── nur nutzbar, wenn Settings + Datenschutzpfad definiert sind

Draft-/Wizard-State-Service
  └── alle Wizard-Schritte
        └── Privacy-Provider + Bereinigungskonzept
```

Empfohlene Reihenfolge:
1. Settings + Architekturgrundgerüst
2. Wizard-State-Service
3. Clone-/Vorlagenlogik
4. Feedback-Template-Service
5. Routing-Regeln
6. optionale Provisionierung
7. optionale KI
8. Finalisierung + Tests

## 6. Bekannte Probleme und Risiken

| Thema | Risiko | Status |
|---|---|---|
| Personenbezug in Draft-Tabelle | widerspricht dem Zielbild der Datenminimierung | offen |
| `settings.php` leer | optionale Features können derzeit nicht sauber gegated werden | offen |
| Fachliche Lücken in Schritt 2–6 | Prototyp wirkt weiter als er fachlich ist | offen |
| Name „feedback wizard“ vs. „settings wizard“ | inkonsistente Terminologie in Code und Doku | offen |
| direkte Abhängigkeit von `local_catquiz`-Strukturen | erfordert saubere Adapter statt ad-hoc SQL | offen |
| KI-Anbindung | Datenschutz, Prompt-Handling, Provider-Abstraktion ungeklärt | offen |
| Kurs-/Gruppenanlage | organisatorisch und sicherheitstechnisch sensibel | offen |

## 7. Repositorium / Codebasis

- URL: unbekannt
- Branch: unbekannt
- letzte bekannte Commit-Message: unbekannt
- verwendete Codebasis in dieser Sitzung: lokal bereitgestellte ZIP-Dateien des Block-Plugins und von `local_catquiz`

## 8. Für die nächste Sitzung mitzugebende Dateien

- `docs/Lastenheft.md` — fachliche Zielsetzung
- `docs/Pflichtenheft.md` — verbindliche Soll-Anforderungen
- `docs/Blueprint.md` — Zielarchitektur und Umsetzungsreihenfolge
- `docs/Moodle-Plugin Coding-Styles.md` — Coding-Regeln
- `docs/coding-standards-prompt.md` — KI-Entwicklungsregeln
- `docs/sessions/session-001.md` — aktueller Kontext
- `block_catquiz_feedbackwizard.php` — Einstiegspunkt und Thin-Block-Prinzip
- `classes/form/wizard.php` — zentrale Baustelle der aktuellen Architektur
- `classes/persistent/draft.php` — datensensible Persistenzstelle
- `settings.php` — leerer Settings-Stub als nächster Ausbaupunkt
- `classes/catquiz_data.php` — aktueller Zugriff auf bestehende CAT-Tests

