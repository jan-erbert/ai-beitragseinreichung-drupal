# Einen Beitrag vorbereiten und speichern

Der Drupal-Ablauf nutzt den normalen Beitragseditor. Die Administration wählt zwischen direkter Veröffentlichung und Prüfmodus. Einreichung und gewöhnliches Entwurfsspeichern sind getrennte Schritte. Benachrichtigungen werden nach dem konfigurierten Abschluss ausgelöst: nach ausdrücklicher Einreichung im Prüfmodus oder nach erfolgreicher direkter Veröffentlichung.

## 1. Ausgangstext eingeben

Öffne **Erstellen → KI-Beitrag erstellen**. Trage Text oder Stichpunkte ein, wähle eine Beitragsart und prüfe das Beitragsdatum. Ergänze bei Bedarf einen Arbeitstitel und eigene Schlagwörter. Entscheide, ob die KI Vorschautext und zusätzliche Tags vorschlagen soll. Die [Formular-Übersicht](formular.md) erklärt die Felder.

## 2. KI-Vorschlag prüfen

Klicke **KI-Vorschlag erstellen**. Prüfe insbesondere Namen, Daten, Orte und Ergebnisse. Nutze bei Bedarf einen konkreten Änderungswunsch. Sind [Gestaltungsstile](symbole.md) freigegeben, prüfe auch die gewählten Symbole und die vollständige Übernahme tabellarischer Ergebnisse. Bis hierhin existiert noch kein gespeicherter Drupal-Beitrag; ein KI-Protokolleintrag kann bereits vorhanden sein.

## 3. Im normalen Beitragseditor weiterarbeiten

Klicke **Im Beitragseditor weiterbearbeiten**. Drupal öffnet ein noch ungespeichertes Beitragsformular mit übernommenem Titel, Text, Vorschautext, Tags und Datum. Dort kannst du den Text mit CKEditor 5 manuell bearbeiten.

Die Übergabe ist kontogebunden und höchstens eine Stunde gültig. Speichere rechtzeitig einen Entwurf. Ist die Übergabe abgelaufen, lässt sie sich nicht über das private Token erneut öffnen; prüfe zuerst, ob du bereits einen Entwurf gespeichert hast.

## 4. Medien ergänzen und abschließen

Nutze die Medienfelder und Medienbibliothek des normalen Editors. Je nach Einrichtung unterstützt die Website Beitragsbilder, eingebettete Medien oder zusätzliche Galerien und Slider. Medienfelder, Großansichten und deren Darstellung sind Funktionen des Hauptprojekts, keine mitgelieferte Funktion des KI-Moduls.

Im Modus **Direkt veröffentlichen** kannst du nach deiner Prüfung bereits bei der ersten Speicherung **Veröffentlichen** wählen. Voraussetzung sind die nativen Veröffentlichungsrechte und alle Pflichtangaben der Website. **Als Entwurf speichern** bleibt zusätzlich möglich. Das Modul legt selbst keine Beitragsbildpflicht fest; diese gehört zur Website. Im **Prüfmodus** bleibt die erste Speicherung ein unveröffentlichter Entwurf. Wiederholtes Speichern derselben Übergabe legt keinen zweiten Beitrag an.

## 5. Wiederfinden und später veröffentlichen

Öffne die normale Inhaltsverwaltung unter **Inhalt**. Die native Inhaltsliste liegt unter `/admin/content/node`; welche Einträge du sehen und bearbeiten kannst, bestimmt die Website über Rechte und Ansichten. Der gespeicherte Beitrag steht dort entsprechend seiner gewählten Speicherung zur weiteren Bearbeitung bereit.

Konten mit Protokoll-Leserecht können außerdem den zugehörigen Vorgang im [KI-Protokoll](protokoll.md) öffnen. Nach der ersten Speicherung enthält dessen Detailseite den Link **Beitrag öffnen**. Der Status „Erfolgreich“ im Protokoll bezieht sich auf die KI-Anfrage, nicht auf eine Veröffentlichung.

Eine Veröffentlichung erfolgt ausschließlich auf ausdrücklichen Klick und mit den entsprechenden Rechten. Welche weiteren Zustände und Übergänge verfügbar sind, legt das Hauptprojekt mit Content Moderation fest. Das Modul setzt den Entwurfszustand `draft` voraus und verwendet für die direkte Veröffentlichung einen nativ erlaubten veröffentlichten Zielzustand, bevorzugt `published`. Es richtet keinen eigenen Prüfstatus ein.

## 6. Ausdrücklich zur Prüfung einreichen

Dieser Weg ist nur im **Prüfmodus** verfügbar. Im direkten Modus wird kein zusätzlicher Prüfknopf angeboten; auch der direkte Aufruf der Prüfroute ist gesperrt.

Speichere zuerst alle Änderungen. Öffne anschließend **Zur Prüfung einreichen** im Beitragseditor. Die Bestätigungsseite nennt den gespeicherten Beitrag und weist darauf hin, dass ungespeicherte Änderungen nicht versendet werden. Erst **Zur Prüfung einreichen und benachrichtigen** führt den Übergang aus.

Der Beitrag bleibt unveröffentlicht und wechselt in den administrativ eingestellten Prüfzustand, beispielsweise `review`. Der native Drupal-Workflow muss diesen Zustand und einen passenden Übergang bereitstellen. Die normalen Moderationsrechte gelten weiterhin. Hat jemand den Beitrag seit dem Öffnen der Bestätigung geändert, musst du die Bestätigungsseite erneut öffnen.

## 7. Mail und Versandstatus

Direkte Veröffentlichung versendet ab 0.6.1 ebenfalls die aktivierten Benachrichtigungen an die ausgewählten Konten und optional an den Autor. Betreff und Status nennen dabei die Veröffentlichung. Eine frühere Prüfbenachrichtigung bleibt davon getrennt. Die erste veröffentlichte Fassung wird für die Mail eingefroren; erfolgreich benachrichtigte Empfänger erhalten bei späteren Speicherungen keine neue Veröffentlichungsmail. Gleiche Adressen, etwa Autor und ausgewähltes Konto, erhalten eine gemeinsame Nachricht.

Die Mail enthält Titel, Autor, Beitragsdatum, die gewählte Beitragsart, Schlagwörter, weitere Taxonomiebegriffe, gegebenenfalls das protokollierte KI-Modell, Vorschautext und die vollständige eingereichte Textfassung. Manuelle Änderungen aus dem normalen Editor sind damit enthalten. Der Bearbeitungslink benötigt Anmeldung und die normalen Beitragsrechte. Öffentliche lokale Bilder aus dem Text werden an ihrer Textposition angezeigt. Einzelne Bildfelder können als Beitragsbild erscheinen; weitere Bildfelder werden als statische Galerie dargestellt. Ein Slider ist in einer Mail eine Bildfolge. Doppelte Dateien werden nur einmal angehängt.

Die Mailkopien sind auf zwölf verschiedene Bilder mit maximal 1000 × 1000 Pixeln, je 1 MiB und zusammen 5 MiB begrenzt. Originale über 20 MiB oder 16 Megapixel werden ausgelassen. Bei überschrittenen Grenzen weist die Mail auf die vollständigen Medien im Beitragseditor hin. Nur verwaltete öffentliche Dateien dieser Drupal-Installation werden übernommen. Private Dateien und externe Bildadressen bleiben ausgeschlossen; es erfolgt kein externer Download. Die Originaldateien werden nicht verändert. Temporäre Mailkopien entfernt Drupal-Cron nach sieben Tagen.

Die Bezeichnung der gewählten Beitragsart wird bereits bei der Vorschau festgehalten und unabhängig von der Protokoll-Aufbewahrung gespeichert. Bei älteren Beiträgen wird sie ergänzt, soweit sie noch aus dem Protokoll bekannt ist.

Die Administration wählt die Empfängerkonten und optional die Autorenbestätigung in den [Einstellungen](einstellungen.md). Gleiche Adressen werden zusammengefasst. Nachrichten gehen einzeln hinaus, ohne eine offen sichtbare Empfängerliste.

Im Prüfmodus heißt der Link nach der Einreichung **Einreichung und Versandstatus**. Erfolgreich an den Mailtransport übergebene Nachrichten werden für dasselbe Ereignis nicht erneut verschickt. Fehlgeschlagene Zustellungen lassen sich dort bewusst wiederholen. Ein unklarer Zustand nach einem Prozessabbruch wird nicht automatisch erneut versendet; die Administration muss ihn anhand der Mailserver-Protokolle klären.

Ein erfolgreicher Versandstatus bestätigt die Übergabe an den Mailtransport, nicht das tatsächliche Eintreffen im Posteingang. Spätere Änderungen und gewöhnliches Speichern versenden keine zweite Einreichungs-Mail. Eine erneute Prüf-Einreichungsrunde desselben Beitrags ist derzeit nicht vorgesehen. Die zusätzliche Benachrichtigung nach direkter Veröffentlichung wird getrennt geführt.

Bei fehlgeschlagenem Veröffentlichungsversand zeigt Drupal eine Warnung. Erneutes ausdrückliches Veröffentlichen versucht nur fehlgeschlagene oder noch ausstehende Zustellungen desselben Ereignisses erneut. Nach einem Prozessabbruch unklare Zustellungen werden nicht automatisch wiederholt. Websiteweite Testumleitungen können die Kontoadresse beim tatsächlichen Transport ersetzen; neue Versanddaten erfassen auch diese Transportadresse.

[Zur Startseite](index.md) · Weiter: [KI-Funktionen](ki-funktionen.md)
