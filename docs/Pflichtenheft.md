# Pflichtenheft — CATQuiz Settings Wizard (`block_catquiz_feedbackwizard`)

## 1. Produktdefinition

Der **CATQuiz Settings Wizard** ist ein Moodle-Block-Plugin mit dem technischen Komponenten-Namen **`block_catquiz_feedbackwizard`**. Das Plugin stellt in einem Kurs einen Wizard bereit, der die Konfiguration von CAT-Tests fachlich führt und orchestriert.

Der Wizard arbeitet auf Basis von:

- **`mod_adaptivequiz`** als Test-/Aktivitätsinstanz,
- **`local_catquiz`** als fachlicher CAT- und Testumgebungs-Layer,
- Moodle-Core-APIs für Kurs-, Gruppen- und Rechteverwaltung,
- optionalen Konnektoren für KI-gestützte Textbearbeitung.

## 2. Produktprinzipien

1. **UI-Name:** „CATQuiz Settings Wizard“.
2. **Technischer Komponentenname:** bleibt bis zu einer expliziten Migration `block_catquiz_feedbackwizard`.
3. **Thin Block Principle:** Das Block-Plugin bleibt UI- und Orchestrierungsschicht.
4. **No Business Logic Fork:** Keine fachliche Parallelwelt neben `local_catquiz`.
5. **Privacy by Design:** Keine unnötige plugin-spezifische personenbezogene Datenhaltung.
6. **Admin First for Risky Features:** Automatisierungs- und KI-Funktionen sind standardmäßig aus.

## 3. Funktionale Soll-Anforderungen

### 3.1 Einstieg und Berechtigung
- Der Block ist in Kurskontexten verfügbar.
- Der Wizard ist nur für Nutzer*innen mit entsprechender Capability sichtbar und nutzbar.
- Bei fehlender Berechtigung wird kein bedienbarer Einstieg angeboten.

### 3.2 Wizard-Modi
Der Wizard muss mindestens folgende Startmodi unterstützen:

1. **Neu anlegen**
2. **Aus bestehendem CAT-Test übernehmen**
3. **Aus Einstellungsmuster importieren**

### 3.3 Fachliche Wizard-Schritte
Die konkrete UI darf iterativ verfeinert werden, aber fachlich muss der Wizard mindestens folgende Themen abdecken:

1. **Zweck / Szenario des Tests**
2. **Auswahl oder Übernahme einer fachlichen Basis**
3. **Feedback-Quellen und Generierung**
4. **Regeln für Anschlussmaßnahmen**
5. **Optionale Automatisierung / KI-Schritt**
6. **Review, Validierung, Speichern, Export**

### 3.4 Übernahme bestehender Konfigurationen
- Nutzer*innen können bestehende CAT-Test-Konfigurationen auswählen, sofern Zugriffsrechte vorliegen.
- Die geladene Konfiguration wird als editierbarer Ausgangszustand in den Wizard übernommen.
- Herkunft und Ziel müssen nachvollziehbar angezeigt werden.

### 3.5 Feedback-Vorlagen und Serienlogik
- Import einer strukturierten Vorlage für Feedbacktexte.
- Zuweisung von Vorlagen bzw. Textbausteinen zu Ergebnisbereichen.
- Erzeugung oder Aktualisierung resultierender Feedbacktexte für die Zielkonfiguration.
- Preferenz für **mustache-kompatible Serienlogik** und wiederverwendbare Platzhalter.

### 3.6 Regeln für Kurse und Gruppen
- Definition von Regeln, die Ergebnisbereiche mit Zielkursen und Zielgruppen verknüpfen.
- Validierung, ob Zielkurse und Zielgruppen vorhanden sind.
- Anzeige klarer Zustände: vorhanden / fehlt / darf angelegt werden / darf nur beantragt werden.

### 3.7 Kursanlage oder Kursantrag
Wenn Admin-Setting aktiv:
- fehlende Zielkurse im gewählten Kursbaumzweig können automatisch angelegt werden,
- oder alternativ in einen geregelten Antragsfluss überführt werden.

Wenn Admin-Setting deaktiviert:
- diese Option wird nicht angeboten oder klar als deaktiviert markiert.

### 3.8 Gruppenanlage
Wenn Admin-Setting aktiv:
- fehlende Zielgruppen in Zielkursen können angelegt werden.

Wenn Admin-Setting deaktiviert:
- diese Option wird nicht angeboten oder klar als deaktiviert markiert.

### 3.9 KI-Glättung / Textvariation
Wenn Admin-Setting aktiv:
- Feedbacktexte können zur sprachlichen Überarbeitung an eine freigegebene KI-Schnittstelle übergeben werden,
- ein spezifischer Systemprompt kann hinterlegt oder ausgewählt werden,
- personenbezogene Daten sind dabei zu minimieren.

Wenn Admin-Setting deaktiviert:
- keine KI-Aktionen in der UI.

### 3.10 Import / Export von Einstellungsmustern
- Konfigurationen können in ein strukturiertes Austauschformat exportiert werden.
- Konfigurationen können daraus importiert werden.
- Das Format muss versionsfähig und validierbar sein.

## 4. Admin-Settings

Mindestens folgende Settings sind vorzusehen:

- `enable_courseprovisioning` (Default `0`)
- `enable_groupautocreate` (Default `0`)
- `enable_ai_feedback_refinement` (Default `0`)
- `ai_feedback_systemprompt` (leer oder Standardprompt)
- optional weitere Settings für erlaubte Zielkategorien, Default-Kursbaumzweig, erlaubte Provider, Dateiformate und Limits

## 5. Datenhaltung

### 5.1 Zielbild
Das Plugin soll keine eigenständige dauerhafte personenbezogene Fachhistorie aufbauen.

### 5.2 Erlaubte Datenhaltung
Erlaubt sind nur Daten, die für die unmittelbare Orchestrierung technisch nötig sind, insbesondere:

- temporärer Wizard-Zwischenstand,
- referenzielle Konfigurationsdaten,
- nicht-personenbezogene Einstellungsmuster,
- technische Status- und Validierungsinformationen.

### 5.3 Konsequenz für den aktuellen Prototyp
Der aktuelle Draft-Ansatz mit `userid`-gebundener Persistenz ist **fachlich als Übergangslösung** zu betrachten. Vor Produktivsetzung ist zu entscheiden, ob:

- Drafts auf kurzlebige Session-/User-Cache-Strukturen umgestellt werden,
- Draft-Records TTL-basiert gelöscht werden,
- oder eine minimale personenbezogene Verarbeitung mit sauberer Begründung bestehen bleibt.

## 6. Schnittstellen

### 6.1 Zu `local_catquiz`
- Lesen vorhandener Testumgebungen / Templates
- Schreiben der finalen Konfiguration
- Zugriff auf Skalen, Kontexte, Strategien und Feedback-Mappings

### 6.2 Zu `mod_adaptivequiz`
- Zuordnung der Konfiguration zu einer Adaptive-Quiz-Instanz
- ggf. Auswahl oder Anlage der Zielinstanz im Kurskontext

### 6.3 Zu Moodle-Core
- Kursanlage oder Kursantrag
- Gruppenprüfung und Gruppenanlage
- Capability-Prüfungen
- Datei-Uploads / Draftfiles

### 6.4 Zu KI-Providern
- nur über klar definierte Adapter-/Service-Schicht
- keine Provider-spezifische Logik direkt in der Formklasse

## 7. Qualitätsanforderungen

- Moodle Coding Style konform
- stabile Dynamic-Form-/Modal-Interaktion
- step-spezifische Validierung
- nachvollziehbare Fehlerbehandlung
- CI-fähige Struktur
- saubere Privacy-Dokumentation

## 8. Abnahmekriterien (MVP)

### MVP bestanden, wenn:
1. ein Wizard mit fachlich sinnvollen Schritten bedienbar ist,
2. bestehende CAT-Konfigurationen übernommen werden können,
3. Einstellungsmuster importiert/exportiert werden können,
4. Feedbackvorlagen eingelesen und Ergebnisbereichen zugeordnet werden können,
5. Anschlussregeln für Kurse/Gruppen konfigurierbar sind,
6. deaktivierte Admin-Features in der UI nicht fälschlich aktiv erscheinen,
7. die finale Konfiguration konsistent in die Zielstruktur überführt wird,
8. keine unnötige plugin-spezifische personenbezogene Datenhaltung verbleibt.

## 9. Explizit bekannte Lücken des Ist-Stands

Der aktuelle Prototyp erfüllt das Pflichtenheft **noch nicht**. Insbesondere fehlen oder sind unvollständig:

- echte Admin-Settings,
- fachliche Wizard-Felder in Schritt 2–6,
- Übernahme bestehender Testumgebungen über das Zielmodell,
- Import-/Exportlogik,
- Feedback-Serienlogik,
- Regelengine für Anschlussmaßnahmen,
- Kurs-/Gruppenautomatisierung,
- KI-Adapter,
- datensparsame Finalarchitektur für Drafts,
- belastbare Review-/Finalisierungsschicht.

