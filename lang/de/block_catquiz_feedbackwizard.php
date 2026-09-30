<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * German language strings for block_catquiz_feedbackwizard.
 *
 * @package     block_catquiz_feedbackwizard
 * @copyright   2024 Ralf Erlebach <ralf.erlebach@gmx.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['action:course'] = 'Kurseinschreibung';
$string['action:downloadpattern'] = 'Einstellungsmuster herunterladen (JSON)';
$string['action:group'] = 'Gruppenzuordnung';
$string['action:text'] = 'Textfeedback';
$string['capability:use'] = 'Mehrstufigen CAT-Wizard verwenden';
$string['capability:writeconfig'] = 'CAT-Testkonfiguration über den Wizard schreiben';
$string['catquiz_feedbackwizard:addinstance'] = 'Neuen CATQuiz-Wizard-Block hinzufügen';
$string['catquiz_feedbackwizard:use'] = 'CATQuiz-Wizard verwenden';
$string['catquiz_feedbackwizard:writeconfig'] = 'CAT-Testkonfiguration über den Wizard schreiben';
$string['clonescope:conditions'] = 'Nur Bedingungen und Grenzwerte übernehmen';
$string['clonescope:full'] = 'Gesamte Konfiguration übernehmen';
$string['clonescope:structure'] = 'Nur Skalen und Subskalen übernehmen';
$string['error:feedbackactioncoursetargetrequired'] = 'Wählen Sie mindestens einen Kurs für die Einschreibungsaktion aus.';
$string['error:feedbackactiongrouptargetrequired'] = 'Geben Sie mindestens einen Gruppennamen für diesen Bereich an.';
$string['error:feedbackinvalidrange'] = 'Jeder Bereich muss oberhalb seiner Untergrenze enden.';
$string['error:feedbackrangegap'] = 'Benachbarte Feedbackbereiche müssen lückenlos aneinander anschließen.';
$string['error:invalidstep'] = 'Ungültiger Wizard-Schritt.';
$string['error:localcatquizunavailable'] = 'Die Schreibschnittstelle von local_catquiz ist nicht verfügbar. Prüfen Sie, ob local_catquiz installiert und aktuell ist.';
$string['error:matchingcategoryrequired'] = 'Wählen Sie eine Kursbereichskategorie für die Zuordnung aus.';
$string['error:matchingcsvinvalid'] = 'Geben Sie mindestens eine gültige CSV-Zuordnungsregel an.';
$string['error:matchingpatternrequired'] = 'Geben Sie ein Suchmuster an.';
$string['error:matchingregexinvalid'] = 'Der reguläre Ausdruck für die Zuordnung ist ungültig.';
$string['error:matchingtargetrequired'] = 'Geben Sie einen Zielwert für die Zuordnung an.';
$string['error:minlargerthanmax'] = 'Die Mindestfragenzahl darf die Maximalfragenzahl nicht überschreiten.';
$string['error:patternexportdenied'] = 'Sie können nur Ihren eigenen Wizard-Entwurf in diesem Kurs exportieren.';
$string['error:patternfilerequired'] = 'Wählen Sie eine Datei mit einem Einstellungsmuster zum Import aus.';
$string['error:patterninvalidjson'] = 'Die hochgeladene Datei ist kein gültiges JSON.';
$string['error:patternmissingsection'] = 'Im Einstellungsmuster fehlt der Abschnitt „{$a}".';
$string['error:patternunsupportedversion'] = 'Einstellungsmuster der Version {$a} werden von dieser Pluginversion nicht unterstützt.';
$string['error:patternwrongformat'] = 'Die hochgeladene Datei ist kein CATQuiz-Einstellungsmuster.';
$string['error:permissiondenied'] = 'Sie haben keine Berechtigung, diesen Wizard zu verwenden.';
$string['error:reportingsubscalesrequired'] = 'Wählen Sie mindestens eine Subskala für diese Auswertungsstrategie aus.';
$string['error:sameclonesource'] = 'Der Quelltest muss sich vom ausgewählten Zieltest unterscheiden.';
$string['error:testnotincourse'] = 'Der ausgewählte CAT-Test gehört nicht zu diesem Kurs.';
$string['field:aiinstructions'] = 'Zusätzliche Formulierungshinweise (optional)';
$string['field:airefinementheader'] = 'KI-Textglättung';
$string['field:clonescope'] = 'Umfang der Übernahme';
$string['field:completionenabled'] = 'Aktivitätsabschluss aktivieren';
$string['field:feedbackactioncourseenabled'] = 'Bereich {$a}: Kurseinschreibung';
$string['field:feedbackactioncoursetarget'] = 'Bereich {$a}: in diese Kurse einschreiben';
$string['field:feedbackactiongroupenabled'] = 'Bereich {$a}: Gruppenzuordnung';
$string['field:feedbackactiongrouptarget'] = 'Bereich {$a}: Gruppennamen, kommagetrennt (die Gruppen müssen bereits existieren)';
$string['field:feedbackactionmessage'] = 'Bereich {$a}: Hinweis zur Einschreibung anzeigen';
$string['field:feedbackactionsummary'] = 'Aktionen für Bereich {$a}';
$string['field:feedbackimportfile'] = 'Feedbacktexte importieren (CSV)';
$string['field:feedbacklabel'] = 'Bezeichnung für Bereich {$a}';
$string['field:feedbacklower'] = 'Untergrenze für Bereich {$a}';
$string['field:feedbackpreview'] = 'Feedbacktexte, wie sie gespeichert werden';
$string['field:feedbackrangecount'] = 'Anzahl fester Bereiche';
$string['field:feedbackrangeheader'] = 'Bereich {$a}';
$string['field:feedbacktemplateformat'] = 'Textformat für Bereich {$a}';
$string['field:feedbacktext'] = 'Feedbacktext für Bereich {$a}';
$string['field:feedbackupper'] = 'Obergrenze für Bereich {$a}';
$string['field:mainscaleid'] = 'Hauptskala';
$string['field:matchingcategoryid'] = 'Kursbereich für die Zuordnung';
$string['field:matchingcoursefield'] = 'Kursfeld';
$string['field:matchingcsv'] = 'CSV-Zuordnungsregeln';
$string['field:matchingmode'] = 'Zuordnungsmodus';
$string['field:matchingoperator'] = 'Vergleichsoperator';
$string['field:matchingpattern'] = 'Suchmuster';
$string['field:matchingsummary'] = 'Zuordnungskonfiguration';
$string['field:matchingtargettype'] = 'Zieltyp der Zuordnung';
$string['field:matchingtargetvalue'] = 'Zielwert der Zuordnung';
$string['field:minquestioncount'] = 'Mindestfragenzahl';
$string['field:patternexport'] = 'Einstellungsmuster';
$string['field:patternfile'] = 'Datei mit Einstellungsmuster';
$string['field:precisionmode'] = 'Genauigkeit';
$string['field:questioncount'] = 'Maximale Fragenzahl';
$string['field:questioncountpersubscale'] = 'Maximale Fragenzahl je Subskala';
$string['field:readiness'] = 'Bereitschaft';
$string['field:reportingstrategy'] = 'Auswertungsstrategie';
$string['field:reviewsummary'] = 'Überblick';
$string['field:reviewwarning'] = 'Warnung';
$string['field:scenario'] = 'Szenario';
$string['field:selectedtest'] = 'CAT-Test';
$string['field:sourcetestid'] = 'Quelltest';
$string['field:subscaleids'] = 'Subskalen';
$string['field:testgoal'] = 'Testziel';
$string['field:timelimitenabled'] = 'Zeitbegrenzung aktivieren';
$string['field:timelimitminutes'] = 'Zeitbegrenzung in Minuten';
$string['field:useairefinement'] = 'Die obigen Feedbacktexte beim Weitergehen glätten';
$string['field:wizardmode'] = 'Wizard-Modus';
$string['goal:diagnostics'] = 'Lernstandsdiagnostik';
$string['goal:final'] = 'Abschlussprüfung';
$string['goal:orientation'] = 'Orientierung';
$string['goal:other'] = 'Sonstiges';
$string['goal:placement'] = 'Einstufung';
$string['goal:strength'] = 'Stärkenprofil';
$string['matchingcoursefield:fullname'] = 'Vollständiger Kursname';
$string['matchingcoursefield:idnumber'] = 'Kurs-ID-Nummer';
$string['matchingcoursefield:shortname'] = 'Kurzer Kursname';
$string['matchingmode:csv'] = 'CSV-Zuordnungsregeln';
$string['matchingmode:none'] = 'Keine Zuordnung';
$string['matchingmode:rule'] = 'Eine Zuordnungsregel';
$string['matchingoperator:contains'] = 'Enthält';
$string['matchingoperator:equals'] = 'Ist gleich';
$string['matchingoperator:regex'] = 'Regulärer Ausdruck';
$string['matchingoperator:startswith'] = 'Beginnt mit';
$string['matchingtargettype:catscale'] = 'CAT-Skala';
$string['matchingtargettype:course'] = 'Kurs';
$string['matchingtargettype:group'] = 'Gruppe';
$string['message:airefinementapplied'] = 'Die KI-Glättung hat {$a} Feedbacktext(e) überarbeitet.';
$string['message:airefinementinfo'] = 'Übertragen werden ausschließlich die Feedbacktexte und Ihre Hinweise. Namen, Kursdaten und Ergebnisse werden nicht mitgesendet. Prüfen Sie das Ergebnis vor dem Speichern.';
$string['message:airefinementnochange'] = 'Die KI-Glättung hat keinen Feedbacktext verändert.';
$string['message:airefinementunavailable'] = 'Auf dieser Website ist kein KI-Anbieter für Textgenerierung eingerichtet, die Glättung steht deshalb nicht zur Verfügung.';
$string['message:courseprovisioningdisabled'] = 'Die automatische Kurseinschreibung ist von der Administration deaktiviert, Kursaktionen werden deshalb nicht angeboten.';
$string['message:feedbackimportapplied'] = '{$a} Feedbacktext(e) importiert.';
$string['message:feedbackimportinfo'] = 'Eine Zeile je Feedbackbereich. Spalten: range, label, text. Bleibt die Bezeichnung leer, wird die vorhandene beibehalten. Platzhalter sind erlaubt und werden beim Speichern aufgelöst.';
$string['message:feedbackimportnothing'] = 'Die Datei enthielt keine verwertbare Zeile, es wurde nichts importiert.';
$string['message:feedbackpreviewnote'] = 'Platzhalter werden beim Speichern aufgelöst; dies ist der Text, den Teilnehmende sehen.';
$string['message:feedbacktokeninfo'] = 'Feedbacktexte dürfen die Platzhalter {{result.ranklabel}}, {{result.scalename}}, {{test.name}} und {{course.fullname}} enthalten. Sie werden beim Speichern durch die tatsächlichen Werte ersetzt, der gespeicherte Text enthält danach also keinen Platzhalter mehr. Der Bestätigungsschritt zeigt das Ergebnis vorab.';
$string['message:groupautocreatedisabled'] = 'Die automatische Gruppenzuordnung ist von der Administration deaktiviert, Gruppenaktionen werden deshalb nicht angeboten.';
$string['message:matchingcsvtemplate'] = 'CSV-Format: Kursfeld, Operator, Suchmuster, Zieltyp, Zielwert';
$string['message:nosubscalesavailable'] = 'Für die aktuell gewählte Hauptskala stehen noch keine Subskalen zur Verfügung.';
$string['message:notestsavailable'] = 'In diesem Kurs wurden keine CAT-Tests gefunden.';
$string['message:patternimportinfo'] = 'Importierte Werte dienen als Vorgabe. Skalenverweise werden gegen diese Website geprüft und verworfen, wenn sie hier nicht existieren.';
$string['message:reviewsummary'] = 'Prüfen Sie die normalisierten CAT-Einstellungen, die Feedbackbereiche und die Auswertung, bevor sie in den gewählten Test zurückgeschrieben werden.';
$string['mode:clone'] = 'Konfiguration eines anderen CAT-Tests übernehmen';
$string['mode:edit'] = 'Den gewählten CAT-Test bearbeiten';
$string['mode:import'] = 'Einstellungsmuster importieren';
$string['mode:scenario'] = 'Mit einem vorgegebenen Szenario beginnen';
$string['openwizard'] = 'CATQuiz-Wizard starten';
$string['pluginname'] = 'CATQuiz Wizard';
$string['precision:high'] = 'Hoch';
$string['precision:low'] = 'Niedrig';
$string['precision:medium'] = 'Mittel';
$string['privacy:metadata:block_catquiz_feedbackwizard'] = 'Speichert Entwürfe und Übermittlungen des Wizards.';
$string['privacy:metadata:block_catquiz_feedbackwizard:courseid'] = 'Die ID des Kurses, in dem der Entwurf entstanden ist.';
$string['privacy:metadata:block_catquiz_feedbackwizard:datajson'] = 'Die unvollständigen Wizard-Daten als JSON.';
$string['privacy:metadata:block_catquiz_feedbackwizard:status'] = 'Der Bearbeitungsstand des Entwurfs.';
$string['privacy:metadata:block_catquiz_feedbackwizard:testid'] = 'Die ID des gewählten CAT-Tests.';
$string['privacy:metadata:block_catquiz_feedbackwizard:userid'] = 'Die ID der Person, die den Entwurf angelegt hat.';
$string['readiness:incomplete'] = 'Unvollständig';
$string['readiness:ready'] = 'Bereit zum Speichern';
$string['readiness:warnings'] = 'Bereit mit Warnungen';
$string['reporting:main_and_subscales_separate'] = 'Hauptskala und gewählte Subskalen getrennt auswerten';
$string['reporting:main_only'] = 'Nur die Hauptskala auswerten';
$string['reporting:subscales_only'] = 'Nur die gewählten Subskalen auswerten';
$string['reporting:subscales_with_parents_without_main'] = 'Gewählte Subskalen und ihre direkten Elternskalen ohne die Hauptskala auswerten';
$string['savedprogress'] = 'Zwischenstand gespeichert.';
$string['scenario:checkup'] = 'Lernfortschrittstest, vollständig adaptiv';
$string['scenario:final'] = 'Abschlusstest über alle Themen, teilweise adaptiv';
$string['scenario:learning_diagnostics'] = 'Lernstandsdiagnostik';
$string['scenario:other'] = 'Sonstiges';
$string['scenario:placement'] = 'Einstufungstest zu Kursbeginn';
$string['scenario:strength'] = 'Persönliche Stärken ermitteln';
$string['settings:ai_feedback_systemprompt'] = 'KI-Systemprompt';
$string['settings:ai_feedback_systemprompt_desc'] = 'Systemprompt für die sprachliche Glättung von Feedbacktexten. Leer lassen, um die eingebaute Vorgabe zu verwenden. Tragen Sie hier keine personenbezogenen Daten ein.';
$string['settings:allowed_target_categories'] = 'Erlaubte Zielkategorien';
$string['settings:allowed_target_categories_desc'] = 'Kommagetrennte Liste der IDs von Kursbereichen, die als Ziel verwendet werden dürfen. Leer lassen, um alle Bereiche zuzulassen.';
$string['settings:dataretention'] = 'Datenhaltung';
$string['settings:dataretention_desc'] = 'Der Wizard speichert nur kurzlebige Arbeitsdaten. Unfertige Entwürfe werden automatisch entfernt, sobald sie die hier eingestellte Lebensdauer überschreiten.';
$string['settings:draft_ttl_hours'] = 'Lebensdauer von Entwürfen (Stunden)';
$string['settings:draft_ttl_hours_desc'] = 'Anzahl der Stunden, die ein unfertiger Entwurf aufbewahrt wird, bevor er automatisch gelöscht wird.';
$string['settings:enable_ai_feedback_refinement'] = 'KI-Glättung von Feedbacktexten aktivieren';
$string['settings:enable_ai_feedback_refinement_desc'] = 'Erlaubt Lehrenden, Feedbacktexte zur sprachlichen Glättung an das KI-Subsystem von Moodle zu senden. Standardmäßig deaktiviert.';
$string['settings:enable_courseprovisioning'] = 'Automatische Kurseinschreibung aktivieren';
$string['settings:enable_courseprovisioning_desc'] = 'Erlaubt Lehrenden, einem Feedbackbereich Kurse zuzuordnen. local_catquiz schreibt Teilnehmende in diese Kurse ein, sobald ein Ergebnis in den Bereich fällt. Es wird nichts angelegt: die Kurse müssen bereits existieren. Standardmäßig deaktiviert.';
$string['settings:enable_groupautocreate'] = 'Automatische Gruppenzuordnung aktivieren';
$string['settings:enable_groupautocreate_desc'] = 'Erlaubt Lehrenden, einem Feedbackbereich Gruppennamen zuzuordnen. local_catquiz nimmt Teilnehmende in Gruppen dieses Namens auf, sobald ein Ergebnis in den Bereich fällt, und nur in bereits vorhandene Gruppen. Standardmäßig deaktiviert.';
$string['settings:optionalfeatures'] = 'Optionale Funktionen';
$string['settings:optionalfeatures_desc'] = 'Diese Funktionen verändern Kurse, Gruppen oder Feedbacktexte über den CAT-Test hinaus. Sie sind alle standardmäßig abgeschaltet und müssen bewusst aktiviert werden.';
$string['settings:pattern_export_include_feedback_texts'] = 'Feedbacktexte in Exporte aufnehmen';
$string['settings:pattern_export_include_feedback_texts_desc'] = 'Wenn aktiviert, enthalten exportierte Einstellungsmuster die Feedbacktexte selbst und nicht nur die Struktur der Bereiche.';
$string['settings:pattern_import_maxfilesize'] = 'Maximale Größe der Musterdatei (Bytes)';
$string['settings:pattern_import_maxfilesize_desc'] = 'Größte Datei mit einem Einstellungsmuster, die der Importschritt annimmt.';
$string['settings:patterns'] = 'Einstellungsmuster';
$string['settings:patterns_desc'] = 'Optionen für den Import und Export wiederverwendbarer Einstellungsmuster.';
$string['step01:description'] = 'Wählen Sie den CAT-Test, der konfiguriert oder geprüft werden soll.';
$string['step01:title'] = 'CAT-Test wählen';
$string['step02:description'] = 'Entscheiden Sie, ob Sie den aktuellen Test bearbeiten, einen anderen übernehmen oder mit einem Szenario beginnen möchten.';
$string['step02:title'] = 'Einrichtungsmodus wählen';
$string['step03:description'] = 'Passen Sie die Skalenauswahl und die zentralen CAT-Einstellungen an, bevor Sie zur Feedbackkonfiguration weitergehen.';
$string['step03:title'] = 'Testeinstellungen bearbeiten';
$string['step04:description'] = 'Legen Sie feste Feedbackbereiche fest, wählen Sie die Auswertungsstrategie und hinterlegen Sie je Bereich einen Text.';
$string['step04:title'] = 'Feedbackbereiche konfigurieren';
$string['step05:description'] = 'Konfigurieren Sie Zuordnungsregeln von Kursen zu Zielen oder fügen Sie CSV-Definitionen ein.';
$string['step05:title'] = 'Anschlussregeln konfigurieren';
$string['step06:description'] = 'Prüfen Sie den normalisierten Stand und schreiben Sie die Einstellungen in den gewählten CAT-Test zurück.';
$string['step06:title'] = 'Bestätigen und speichern';
$string['submissionsuccess'] = 'Die Konfiguration des CAT-Tests wurde erfolgreich aktualisiert.';
$string['submitfinal'] = 'Fertigstellen';
$string['submitnext'] = 'Weiter';
$string['submitprevious'] = 'Zurück';
$string['task:cleanupdrafts'] = 'Abgelaufene Entwürfe des CATQuiz-Wizards löschen';
$string['templateformat:mustache'] = 'Mustache-Vorlage';
$string['templateformat:plain'] = 'Einfacher Text';
$string['warning:aipolicynotaccepted'] = 'Sie haben die KI-Richtlinie dieser Website noch nicht bestätigt, die Texte bleiben deshalb unverändert.';
$string['warning:airefinementplaceholders'] = 'Die KI-Glättung hat Platzhalter aus einem Feedbacktext entfernt, deshalb wurde der ursprüngliche Text beibehalten.';
$string['warning:airefinementunavailable'] = 'Die KI-Glättung wurde angefordert, steht aber nicht zur Verfügung; die Texte bleiben unverändert.';
$string['warning:feedbackimportoutofrange'] = 'Die Datei verweist auf Bereich {$a->range}, es sind aber nur {$a->count} Bereiche konfiguriert. Die Zeile wurde übersprungen.';
$string['warning:feedbackimportshortrow'] = 'Zeile {$a} hat weniger als drei Spalten und wurde übersprungen.';
$string['warning:feedbackimportunknowntoken'] = 'Unbekannte Platzhalter wurden unverändert übernommen und erscheinen bei den Teilnehmenden so, wie sie eingetragen sind: {$a}';
$string['warning:feedbackrangesneedreview'] = 'Die Grenzen der Feedbackbereiche sollten vor dem Speichern geprüft werden.';
$string['warning:feedbacktextmissing'] = 'Mindestens ein Feedbacktext ist noch leer.';
$string['warning:highprecisionlowquestions'] = 'Eine hohe Genauigkeit erfordert in der Regel eine höhere maximale Fragenzahl.';
$string['warning:matchingconfigincomplete'] = 'Die konfigurierte Zuordnung ist unvollständig und sollte geprüft werden.';
$string['warning:nosubscalesselected'] = 'Es wurden noch keine Subskalen ausgewählt.';
$string['warning:patterncategorynotallowed'] = 'Der Zielkursbereich des importierten Musters ist auf dieser Website nicht zugelassen und wurde entfernt.';
$string['warning:patterncoursemissing'] = 'Der Kurs {$a} aus dem importierten Muster existiert auf dieser Website nicht und wurde aus der Einschreibungsaktion entfernt.';
$string['warning:patternscalemissing'] = 'Die Skala „{$a}" aus dem importierten Muster existiert auf dieser Website nicht und wurde übersprungen.';
$string['warning:patternwithouttexts'] = 'Das importierte Muster wurde ohne Feedbacktexte exportiert, die Texte müssen deshalb neu erfasst werden.';
$string['warning:reportingsubscaleswithoutselection'] = 'Die gewählte Auswertungsstrategie setzt ausgewählte Subskalen voraus.';
$string['warning:shorttimelimit'] = 'Die eingestellte Zeitbegrenzung ist für einen CAT-Test sehr knapp.';
