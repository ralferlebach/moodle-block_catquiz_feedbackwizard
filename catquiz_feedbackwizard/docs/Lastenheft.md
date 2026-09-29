# Lastenheft — CATQuiz Settings Wizard (`block_catquiz_feedbackwizard`)

## 1. Ausgangslage

Im vorhandenen Block-Plugin existiert bereits ein früher Prototyp eines mehrstufigen Wizards mit Modal-UI, Draft-Speicherung, Capability-Prüfung und sechs Wizard-Schritten. Die fachliche Zielsetzung ist jedoch noch nicht umgesetzt.

Das Plugin soll Lehrenden ermöglichen, **CAT-Tests in Moodle möglichst einfach zu konfigurieren**, ohne die technischen Tiefen von `local_catquiz` und `mod_adaptivequiz` direkt beherrschen zu müssen.

## 2. Ziel des Produkts

Das Produkt soll als **fachlich geführter Settings Wizard** dienen, mit dem Lehrende:

1. einen CAT-Test neu konfigurieren,
2. Einstellungen bestehender CAT-Tests übernehmen und anpassen,
3. Feedbacktexte aus Seriendokumenten einlesen und generieren,
4. Kurs- und Gruppeneinschreibungen regelbasiert an Feedbacks koppeln,
5. optional fehlende Zielkurse und Gruppen prüfen und erzeugen bzw. beantragen,
6. optional Feedbacktexte per KI sprachlich glätten und variieren,
7. und Einstellungsmuster importieren/exportieren können.

## 3. Stakeholder

### 3.1 Primäre Stakeholder
- Lehrende / Editing Teachers
- Manager / CAT-Verantwortliche
- Administrator*innen

### 3.2 Sekundäre Stakeholder
- Lernende (indirekt über resultierende Feedback- und Einschreibungslogik)
- Datenschutzverantwortliche
- Entwickler*innen und Maintainer des Plugins

## 4. Fachliche Anforderungen

### 4.1 Wizard-Grundfunktion
Als Lehrende*r möchte ich einen CAT-Test über einen **verständlich strukturierten Wizard** anlegen oder bearbeiten können, damit die Konfiguration didaktisch nachvollziehbar und ohne Spezialwissen bedienbar ist.

### 4.2 Übernahme bestehender Einstellungen
Als Lehrende*r möchte ich **Einstellungen bestehender CAT-Tests übernehmen** können, auf die ich berechtigten Zugriff habe, damit ich Konfigurationen effizient wiederverwenden und anpassen kann.

### 4.3 Feedback aus Seriendokumenten
Als Lehrende*r möchte ich **Feedbacktexte aus Seriendokumenten/Vorlagen einlesen** und für Ergebnisbereiche generieren können, damit große Mengen an Feedback strukturiert verwaltet werden können.

### 4.4 Regelbasierte Anschlussmaßnahmen
Als Lehrende*r möchte ich **Kurs- und Gruppeneinschreibungen an Feedbackregeln koppeln** können, damit Testergebnisse unmittelbar in Förder- oder Anschlussangebote überführt werden.

### 4.5 Fehlende Zielkurse
Als Lehrende*r möchte ich **erkennen**, wenn Zielkurse im gewünschten Kursbaum fehlen, und diese — sofern administrativ erlaubt — **automatisiert anlegen oder beantragen** können.

### 4.6 Fehlende Gruppen
Als Lehrende*r möchte ich **prüfen**, ob die für die Rückmeldelogik benötigten Gruppen in Zielkursen existieren, und diese — sofern administrativ erlaubt — **automatisiert anlegen** können.

### 4.7 KI-gestützte Textbearbeitung
Als Lehrende*r möchte ich Feedbacktexte — sofern administrativ freigeschaltet — **per KI glätten und variabler gestalten** lassen können, inklusive Hinterlegung eines **spezifischen Systemprompts**.

### 4.8 Import / Export von Mustern
Als Lehrende*r möchte ich **Einstellungsmuster importieren und exportieren** können, damit Konfigurationen zwischen Kursen, Fachbereichen oder Instanzen wiederverwendbar sind.

## 5. Administrative Anforderungen

### 5.1 Optionale Funktionen schaltbar
Folgende Funktionen müssen **per Plugin-Setting ein- und ausschaltbar** sein, **Default = aus**:

- automatische Anlage oder Beantragung fehlender Zielkurse,
- automatische Anlage fehlender Gruppen,
- KI-gestützte Glättung/Variation von Feedbacktexten.

### 5.2 Rechte und Sichtbarkeit
Nur berechtigte Personen dürfen:

- den Wizard öffnen,
- bestehende Konfigurationen als Vorlage sehen,
- Import-/Exportfunktionen verwenden,
- Kurs-/Gruppenautomatisierung auslösen,
- KI-Prompts verwalten, soweit nicht nur globale Admin-Settings gelten.

## 6. Datenschutz und Datenminimierung

Das System soll **keine plugin-spezifischen personenbezogenen Daten** dauerhaft erheben oder speichern, soweit dies für die Funktion nicht zwingend erforderlich ist.

Insbesondere gilt:

- keine dauerhafte fachliche Historisierung pro Person im Block-Plugin,
- keine zusätzliche personenbezogene Shadow-Datenhaltung neben Moodle-Core und `local_catquiz`,
- KI-Verarbeitung nur ohne unnötige personenbezogene Daten,
- temporäre Zwischenstände nur so kurz wie technisch nötig.

## 7. Nichtfunktionale Anforderungen

### 7.1 Usability
- klare Schrittstruktur,
- verständliche Terminologie,
- didaktischer Fokus statt technischer Feldnamen,
- nachvollziehbare Review-/Bestätigungsseite,
- robuste Validierung mit verständlichen Fehlermeldungen.

### 7.2 Wartbarkeit
- Block bleibt dünne UI-/Orchestrierungsschicht,
- Fachlogik wird nicht unnötig aus `local_catquiz` dupliziert,
- klare Service-Grenzen,
- Moodle-konforme Plugin-Struktur.

### 7.3 Erweiterbarkeit
- spätere Erweiterung um weitere Importschnittstellen,
- spätere Erweiterung um alternative KI-Backends,
- regelbasierte Zielsysteme modular erweiterbar.

## 8. Abgrenzung

Nicht Ziel des Block-Plugins ist:

- die komplette Neuimplementierung der CAT-Fachlogik aus `local_catquiz`,
- die adaptive Laufzeitsteuerung des Tests,
- die Speicherung detaillierter personenbezogener Testergebnisdaten,
- die Verlagerung der gesamten Testumgebungsverwaltung aus `local_catquiz` in das Block-Plugin.

## 9. Erfolgsbild

Das Produkt ist erfolgreich, wenn Lehrende einen CAT-Test **ohne direkte Arbeit in technisch komplexen Verwaltungsseiten** konfigurieren können und die resultierende Konfiguration konsistent in die zugrunde liegende CAT-Struktur überführt wird.

