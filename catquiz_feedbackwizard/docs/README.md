# Dokumentationspaket — `block_catquiz_feedbackwizard`

Dieses Paket enthält die Entwicklungsdokumentation für das Moodle-Plugin **`block_catquiz_feedbackwizard`**.

## Ziel des Pakets

Die Dokumentation soll die Weiterentwicklung des Plugins als **CATQuiz Settings Wizard** strukturieren. Dabei ist wichtig:

- **Technischer Komponentenname bleibt vorerst** `block_catquiz_feedbackwizard`.
- **Fachliche/UI-Bezeichnung** ist konsequent **CATQuiz Settings Wizard** bzw. **Settings Wizard**.
- Das Block-Plugin ist **UI- und Orchestrierungs-Plugin** und baut fachlich auf **`local_catquiz`** sowie **`mod_adaptivequiz`** auf.
- Das Zielbild ist **datenarm**: keine unnötige plugin-spezifische Speicherung personenbezogener Daten.

## Inhalt

- `Lastenheft.md` — fachliche Anforderungen aus Sicht der Stakeholder
- `Pflichtenheft.md` — konkrete Systemanforderungen und Abnahmekriterien
- `Blueprint.md` — technische Zielarchitektur und Umsetzungsphasen
- `Moodle-Plugin Coding-Styles.md` — projektbezogene Coding-Regeln
- `coding-standards-prompt.md` — kompakter Systemprompt für KI-gestützte Entwicklungsarbeit
- `linting jobs.txt` — empfohlene CI-/Linting-Aufgaben
- `prompt-templates/sessionstart.txt` — Startprompt für neue Entwicklungssitzungen
- `prompt-templates/sessionende.txt` — Endprompt zur Erzeugung eines Kontextdokuments
- `sessions/session-001.md` — initiales Kontextdokument auf Basis der aktuellen Quellanalyse
- `sessions/session-template.md` — Vorlage für künftige Sitzungsdokumente

## Stand der Dokumentation

Diese Dokumentation wurde **retrospektiv aus dem vorhandenen Prototyp, dem analysierten Referenz-Plugin `local_catquiz` und der fachlichen Zielbeschreibung** erzeugt. Sie beschreibt daher:

- den **Ist-Stand** des aktuellen Block-Prototyps,
- das **Zielbild** für den Settings Wizard,
- und die **Lücke** zwischen beidem.

