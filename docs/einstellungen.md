# Einstellungen

Öffne `/admin/config/content/je-ai-submission`. Dafür ist `administer je ai submission` erforderlich. Normale Änderungen werden erst mit **Konfiguration speichern** dauerhaft übernommen.

Die Seite hat fünf feste Themenbereiche. Einzelne Stilgruppen und seltene Optionen bleiben aufklappbar. Der [Symbolpool](symbole.md#symbolpool-verwalten) hat eine eigene Seite und eigene Speicherung; sein hervorgehobener Link öffnet einen neuen Tab. Änderungen im Einstellungsformular vorher speichern.

## 1. KI-Anbindung

| Einstellung oder Aktion | Wirkung |
| --- | --- |
| KI-Beitragserstellung aktivieren | Schaltet den zusätzlichen KI-Einstieg frei. Standard: aus. |
| KI-Anbieter | Ein eingerichteter Drupal-AI-Provider. |
| KI-Modell | Auswahl aus der lokal gespeicherten Chat-Modellliste. |
| Andere Modellkennung eingeben | Eigene Kennung, wenn das Modell in der Liste fehlt. |
| Modellliste aktualisieren | Ausdrückliche Abfrage über den Provider; dessen Cache kann beteiligt sein. |
| Verbindung jetzt testen | Kurzer technischer Test auf strukturierte Antworten. Kann API-Kosten verursachen, speichert keine Einstellungen und sendet keine Beitragsinhalte. |

Öffnen und Speichern starten keine automatische Modellabfrage oder Testgenerierung. Ein Fehler erhält die bisherige Liste. Die gespeicherte Kennung bleibt verfügbar; es erfolgt kein stiller Modellwechsel. Die Modellliste allein bestätigt weder Zugangsrechte noch Unterstützung des Antwortformats.

Den Anbieterzugang und dessen geschützten Schlüssel richtest du in Drupal AI und Drupal Key ein. Das Modul hat kein eigenes API-Schlüsselfeld.

## 2. Schreibstil und Schlagwörter

Der **Grundstil** gilt für alle Vorschläge; die Beitragsart ergänzt ihn. Maximal 20.000 Zeichen sind für den Grundstil, 10.000 je Stilgruppe vorgesehen. Widersprüche zwischen alten importierten Vorgaben und dem Grundstil sollten bereinigt werden.

Bevorzugte Begriffe mit Enter, Komma oder **Hinzufügen** übernehmen; einzelne Kacheln entfernen. Der Pool erlaubt bis zu 200 Begriffe mit je 80 Zeichen. Dubletten werden ohne Beachtung der Groß-/Kleinschreibung vermieden. Reine Jahreszahlen gehören nicht hinein. Ohne JavaScript steht ein Textfeld mit einem Begriff pro Zeile zur Verfügung.

**Vorhandene Schlagwörter in den KI-Pool übernehmen** zeigt bis zu 50 Begriffe nach Nutzung in veröffentlichten Beiträgen. Revisionen und Entwürfe erhöhen die Zahl nicht; ungenutzte Begriffe können mit 0 erscheinen. Bereits enthaltene Begriffe sind markiert. Filtern, auswählen und **Ausgewählte Schlagwörter übernehmen** ergänzt zunächst nur das Formular. Erst Speichern übernimmt den Pool dauerhaft.

Unter **Schlagwortregeln für die KI** stehen:

- Maximal 1–12 zusätzliche KI-Begriffe, Standard 8. Manuelle Begriffe bleiben erhalten.
- Vorhandene Begriffe als Orientierung, nach Nutzungshäufigkeit sortiert.
- Eindeutigen Ort nur aus dem Ausgangstext berücksichtigen.
- Ergänzende Regeln für Erstellung und Überarbeitung.

Der Pool gibt bevorzugte Schreibweisen vor; er erstellt nicht automatisch sämtliche Begriffe in Drupal.

## 3. Stilgruppen und Symbole

Hier stehen die globale Freigabe **Gestaltungsstile durch die KI verwenden**, der Symbolpool-Link und die einzelnen Stilgruppen. Die globale Gestaltung ist standardmäßig aus.

Die [Stilgruppen-Anleitung](stilgruppen.md) erklärt Namen, Erkennungszeichen, Textvorgaben, Sortierung und Import/Export. Die [Symbol-Anleitung](symbole.md) erklärt die unabhängigen manuellen und KI-Freigaben sowie Checkbox-Kacheln, Einleitung und Ergebnistabelle.

## 4. Einreichung und Benachrichtigungen

| Einstellung | Wirkung |
| --- | --- |
| Abschluss im Beitragseditor | Genau **Zur Prüfung einreichen** (Standard) oder **Direkt veröffentlichen**. Entwurfsspeicherung bleibt zusätzlich möglich. |
| Benachrichtigung nach Veröffentlichung oder Einreichung versenden | Aktiviert die Mails nach dem gewählten Abschluss. Standard: aus. |
| Benachrichtigte Benutzerkonten | Bis zu 20 Konten. Aktive Konten mit gültiger hinterlegter Mailadresse werden berücksichtigt. |
| Autor zusätzlich benachrichtigen | Benachrichtigt den Beitragsautor. Gleiche Adressen erhalten nur eine Nachricht je Ereignis. |
| Kennung des Moderationszustands zur Prüfung | Standard `review`; vorhandener unveröffentlichter Workflow-Zustand außerhalb von `draft`. |
| Eigene Mailheader-Grafik | PNG/JPEG bis 2 MB. Empfohlen etwa 1200 Pixel breit. Ohne Datei gilt das Modulbanner. |

Der gewählte Modus verleiht keine Veröffentlichungsrechte und umgeht keine Pflichtfelder. Im direkten Modus ist der Prüfweg deaktiviert. Im Prüfmodus wird zunächst ein Entwurf gespeichert und anschließend ausdrücklich eingereicht. Entwürfe verschicken keine Mail.

Das Modul richtet keinen eigenen Workflow oder SMTP-Transport ein. Für gestaltete HTML-Mails braucht die Website einen passenden Mailer. Eine Website-Vorlage kann das Layout einschließlich Header/Footer ersetzen; die hochgeladene Headerdatei bleibt in ihren Uploads, nicht im Modul.

Die Mail nennt auch die ausgewählte Beitragsart. Empfänger und Revision werden je Versandereignis festgehalten. Eine neue Empfängerliste verschickt alte Beiträge nicht erneut. Weitere Angaben zu Bildern, Wiederholungen und Testumleitungen: [Beitragsabschluss und Mails](beitrag-einreichen.md#7-mail-und-versandstatus).

## 5. Protokoll und Nutzungsgrenzen

| Einstellung | Standard | Bereich |
| --- | --- | --- |
| Ausgangstext und KI-Ergebnis speichern | Aus | Ein/Aus |
| Maximal gespeicherte KI-Vorgänge | 100 | 1–500 |
| Mindestanzahl Wörter im Ausgangstext | 30 | 1–1.000 |
| Maximale Zeichen im Ausgangstext | 40.000 | 100–100.000 |
| KI-Anfragen je Konto und Stunde | 20 | 1–200 |

Die Mindestwortzahl wird vor einer KI-Anfrage geprüft. Das Stundenlimit ist keine Kostenabrechnung. Der [Protokoll-Leitfaden](protokoll.md) erklärt Textaufzeichnung und Aufbewahrung.

## Zugriff und Rollen

Die zusätzliche Übersicht zeigt je Drupal-Rolle KI-Nutzung, Protokolllesen, Löschen und Einstellungen. Sie verlinkt zur nativen Berechtigungsverwaltung, wenn dein Konto diese öffnen darf. Neue KI-Freigaben erhalten Lesen als anpassbaren Standard. Einzelheiten und Umfang der Freigaben: [Benutzerrechte](benutzerrechte.md).

[Zur Startseite](index.md) · Weiter: [Stilgruppen](stilgruppen.md)

**Einstellungen erklärt** am Formularende öffnet diese Anleitung im Drupal-Wiki in einem neuen Tab.
