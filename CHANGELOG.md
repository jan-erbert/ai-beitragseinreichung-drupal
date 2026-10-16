# Changelog

## 1.0.1 – 2026-10-16

- Passende Anleitungslinks im KI-Formular und in den Einstellungen ergänzt.
- Drupal-Wiki aus `docs/` mit Navigation, umgeschriebenen Verweisen und Bildern eingerichtet; alte WordPress-Wiki-Verweise im Modul und aktuellen Anleitungen ersetzt.
- Gemeinsamen lokalen Veröffentlichungsweg für getrennte interne und öffentliche Git-Historien plus Wiki ergänzt. Saubere Arbeitsbäume, Zielprüfung, Secret-Muster und lokale Ausschlussregeln werden vor dem Push geprüft.
- PHP-8.3-/8.4-Workflows auf GitHub und Forgejo, Drupal-Paketquelle und konsistente Composer-Pluginsperre eingerichtet. Drupal-Einstieg und Installation werden mitgeprüft.
- Projektregeln für Agenten und Veröffentlichungsanleitung ergänzt; Wiki- und Schutzregeln mit isolierten Python-Tests abgesichert.

## 1.0.0 – 2026-10-16

### Veröffentlichung

- Erste stabile Version von JE KI-Beiträge für Drupal; bisherige Entwicklungsstände als gemeinsamer Funktionsumfang zusammengeführt.
- Versionsangaben in Modul, Release-Hinweis und Anleitung auf 1.0.0 abgeglichen.
- KI-Erstellung und Überarbeitung, native Editorübernahme, konfigurierbarer Abschluss, Benachrichtigungen mit lokalen Bildern, Stilgruppen und optionaler Symbolpool.
- Getrennte Rollenrechte für Nutzung, Protokolllesen, Löschen und Administration. Allgemeine Dokumentation unter `docs/`; individuelle Website-Konfiguration bleibt außerhalb des Moduls.

### Hinweise

- Bestehende Einstellungen bleiben erhalten; Generierung und Vorschau veröffentlichen keinen Beitrag.
- Installationsvoraussetzungen und bekannte Grenzen stehen unter `docs/installation.md` und `docs/entwicklung.md`. Die Integrationsprüfung erfolgt in der Referenz-Testinstallation; eine frische neutrale Drupal-Installation ist noch gesondert zu prüfen.

## 0.8.1 – 2026-10-16

- Ausgewählte Beitragsart in Benachrichtigungen ergänzt. Bezeichnung bereits bei der KI-Vorschau erfasst und über die private Übergabe unabhängig vom KI-Protokoll aufbewahrt.
- Bekannte Bezeichnungen älterer Einreichungen beim Update ergänzt, ohne neue Mails auszulösen. Mailprüfung für Herkunft und Erhaltung ohne Protokolleintrag erweitert.

## 0.8.0 – 2026-10-16

- Allgemeine Dokumentation vollständig auf 0.8.0 abgeglichen, widersprüchliche Altangaben bereinigt und eigene Symbol-Anleitung ergänzt. Sechs aktuelle echte Screenshots ohne Wartungsmeldung; Navigation und lokale Links geprüft.

- Individuelle Lucide-Kennzeichen für Beitragsarten, lokale Vorschauen und per Tastatur bedienbare Auswahl mit nativem Select als Rückfall ergänzt. Gruppenexport, Duplizieren und Import erhalten das Kennzeichen.
- Neue Installationen starten mit drei neutralen Gruppen: Standardartikel, Veranstaltung sowie Presse und Medien. Updates ersetzen keine bestehenden Gruppen.
- Interne Kennungen im Formular ausgeblendet und weiter automatisch verwaltet; gezielte Website-Aliase erhalten ältere Vorschauzuordnungen bei Umbenennung.
- Protokoll-Leserecht bei Installation/Update für vorhandene KI-Rollen und bei neuer KI-Rollenfreigabe als Standard ergänzt. Späterer manueller Entzug bleibt erhalten; keine automatische Vergabe von Lösch-/Konfigurationsrechten.

## 0.7.1 – 2026-10-16

- Einstellungen in feste Themenbereiche gegliedert; Stilgruppen und seltene Optionen bleiben gezielt aufklappbar.
- Mehrfachauswahl der Stilgruppensymbole durch Checkbox-Kacheln mit lokaler Vorschau ersetzt; sichtbare Symbolpool-Links und Erläuterung interner Kennungen ergänzt.

## 0.7.0 – 2026-10-16

- Optionalen, standardmäßig deaktivierten KI-Gestaltungsweg mit konfigurierbarem Symbolpool und getrennten manuellen/KI-Freigaben ergänzt. Lokaler Lucide-Katalog 1.58.0 validiert Namen und liefert Vorschauen ohne Netzwerkaufruf.
- Gestaltung je Stilgruppe freigeben: erlaubte Symbole, Aufbauvorgaben, Einleitung und Ergebnistabellen. Strukturierte Zuordnungen werden gegen Freigaben und vorhandene Textstellen geprüft; kein KI-HTML und keine Veränderung von Ergebnissen durch Formatierung.
- Einheitliche Vorschau-/Editorübernahme, native CKEditor-Stile und HTML-Filter, lokale PNG-Mailabbildung sowie Website-Adapter für vorhandene Gestaltung ergänzt. Stilgruppenexport erhält optionale Drupal-Gestaltungsregeln.
- Isolierte und native Integrationsprüfungen für Freigaben, unbekannte Symbole, Filter, Inhalte, Rechte und ungespeicherte Übernahme ergänzt.

## 0.6.1 – 2026-10-16

- Konfigurierte Benachrichtigungen auch nach direkter Veröffentlichung aus dem Beitragseditor ausgelöst; erst nach erfolgreicher Speicherung und Herkunftsregistrierung. Entwürfe versenden weiterhin nicht.
- Veröffentlichungsereignis getrennt von früherer Prüfung gespeichert; unveränderliche Revisionsvorschau und Zustandsverwaltung mit Dublettenschutz, gezielten Fehlerwiederholungen und erfasster tatsächlicher Transportadresse ergänzt.
- Betreff und HTML-/Text-Inhalt zwischen Prüfung und Veröffentlichung unterschieden. Empfängerkonten und optionale Autorenbestätigung bleiben wirksam, gleiche Adressen erhalten nur eine Mail.
- Native Prüfung für beide Empfängerfälle, Veröffentlichungs-Callback, Revision, Testumleitung und Zustellwiederholungen erweitert; keine externe Testzustellung.

## 0.6.0 – 2026-10-16

- Exklusive Auswahl zwischen direkter Veröffentlichung und Prüfmodus ergänzt. Update und Neuinstallation behalten Prüfung als Standard.
- Direkte Erstveröffentlichung aus dem übernommenen Beitragseditor mit nativen Übergangsrechten und vollständiger Inhaltsvalidierung ermöglicht; Entwurfsspeicherung und Schutz vor Doppelanlage erhalten.
- Prüfroute im direkten Modus gesperrt; konfigurierte Prüfbenachrichtigungen bleiben dem Prüfmodus zugeordnet. Keine Veröffentlichung durch Generierung oder Vorschau.
- Native Integrationsprüfung für Bildpflicht, Entwurf, Veröffentlichungsrechte, Moduswechsel und Doppelübernahme ergänzt.

## 0.5.2 – 2026-10-16

- Ergebnisanimation rechts in der Vorschau von 64 auf bis zu 128 Pixel vergrößert, auf schmalen Anzeigen 96 Pixel; Vorschau umschließt die Animation vollständig.

- Protokollvergleich mit kompakten Beschriftungen, klaren Textkarten und separater Metadatenleiste überarbeitet. Unerwünschte HTML-Einrückungen aus der Leerraumdarstellung entfernt.
- KI-Ergebnisse mit sicherer Markdown-Formatierung angezeigt; Original bleibt unverändert als Text. Vorheriger Vorschlag bei Überarbeitungen einklappbar, Statusmarkierungen und mobile Stapelung ergänzt.

## 0.5.1 – 2026-10-16

- Eigenes optionales Feld für zusätzliche KI-Hinweise ergänzt; unabhängig vom Änderungswunsch, bei Erstellung und Überarbeitung berücksichtigt und bei aktivem Textprotokoll angezeigt. Serverseitige Grenze: 2000 Zeichen.
- Verarbeitung in einem mittigen nativen Dialog mit größerer lokaler Lottie-Animation angezeigt; Fokus bleibt während der Anfrage im Dialog, Fehler und Abschluss geben das Formular wieder frei.

## 0.5.0 – 2026-10-16

- Öffentliche lokale Beitrags-, Inline- und Galeriebilder als verkleinerte CID-Mailbestandteile ergänzt; feste Größen-/Mengenlimits, keine privaten Dateien oder externen Downloads, temporäre Kopien mit Cron-Aufbewahrung.
- Vorhandene Schlagwörter mit Nutzungshäufigkeit veröffentlichter Beiträge auswählen und zunächst ungespeichert in den KI-Pool übernehmen; bestehende Orientierung für die KI nach Häufigkeit sortiert.
- Protokoll-Lese- und Löschrechte vom Einstellungsrecht getrennt; beliebige native Rollen unterstützt, Rollenübersicht und Verwaltungslink ergänzt. Update erhält bisherige Freigaben ohne zusätzliche Redaktionsrechte.
- Native Rechte-, Häufigkeits-, Bild- und MIME-Prüfung mit zurückgerollten Testdaten ergänzt; allgemeine Dokumentation und Release-Hinweis aktualisiert.

## 0.4.0 – 2026-10-16

- Standard-Mailfooter auf „Erstellt mit AI Beitragseinreichung“ und „Powered by Jan Erbert“ mit Entwicklerlink angepasst; langen Entwicklerzusatz entfernt, Textalternative ergänzt.
- Eigenes AI-Beitragseinreichung-Banner als Mailstandard und optionalen nativen PNG-/JPEG-Headerupload ergänzt. Individuelle Website-Vorlagen und Brandingassets bleiben außerhalb des Modul-Repositories; technische Standardvorschau getrennt von Theme-Overrides.

- Ausdrückliche Einreichung gespeicherter KI-Entwürfe über native Moderation mit Beitrags-/Übergangsrechten und Revisionsprüfung ergänzt.
- Administrative Empfängerauswahl, optionale Autorenbestätigung und überschreibbare HTML-/Text-Mail mit der tatsächlich eingereichten Textfassung umgesetzt.
- Versandzustand je Beitrag/Empfänger mit Sperre gegen doppelte Zustellung und bewusster Wiederholung fehlgeschlagener Zustellungen ergänzt; unklare Prozessabbrüche werden nicht automatisch wiederholt.
- Einreichungs-, Versand- und Rollentests sowie allgemeine Dokumentation und Release-Hinweis aktualisiert.

- Allgemeine Drupal-Moduldokumentation unter `docs/` mit Redaktions-/Admin-Anleitungen, Vergleich zum WP-Original, zwei Bildschirmabbildungen und MkDocs-Navigation ergänzt. Einreichungsbenachrichtigung und deren Grenzen dokumentiert; kein separates Wiki veröffentlicht.

- Erläuternden Einleitungstext über dem Ausgangstext auf Wunsch entfernt.

- Bevorzugte KI-Schlagwörter als einzeln ergänzbare und entfernbare Kacheln mit Dublettenprüfung und nativer Formularspeicherung pflegen.

- Manuelles Vorschautextfeld bei aktiver KI-Vorschauoption ausblenden; eingegebene Texte bleiben erhalten.
- Konfigurierbare Mindestwortzahl (Standard 30) vor KI-Anfragen prüfen und am Eingabefeld erklären.

## 0.3.0 – 2026-10-15

- Versionsgebundenen Release-Hinweis mit Original-Wiki und erneut aufrufbarem Reiter ergänzt.
- Fünf lokal geladene Original-Lottie-Animationen eingebunden; Sichtbarkeitssteuerung, reduzierte Bewegung und Observer-Bereinigung ergänzt.
- Stilgruppenverwaltung mit Duplizieren, Verschieben, Entfernen sowie geprüftem WP-kompatiblem Einzel-/Sammel-JSON-Import und -Export ergänzt.
- Schlagwort-Kacheln, separate dynamische Jahresanzeige und pflegbare, serverseitig angewendete Schlagwortregeln einschließlich eindeutiger Ortsangabe umgesetzt.
- Expliziten strukturierten Verbindungstest und native bestätigte Einzellöschung des Admin-Protokolls ergänzt.
- Sichere Markdown-Tabellen in Vorschau und Beitragsübergabe aktiviert; bestehende Stiltexte und redaktionelle Rechte erhalten. Mails zurückgestellt.


## 0.2.2 – 2026-10-15

- Originales mobiles Plugin-Banner übernommen; native Bildauswahl bis 768 px, darüber weiterhin das breite Banner.

## 0.2.1 – 2026-10-15

- Größenprüfung für Stilgruppen/Grundstil ergänzt, nachdem ein beschädigter WP-Import mit 1.048.575 Backslashes vor einem Apostroph die Einstellungen im Browser blockierte.
- Modellauswahl aus lokal gespeicherter Anbieter-Liste; expliziter Aktualisierungsknopf und manuelle Kennung für neue Modelle. Keine Modellabfrage beim normalen Formularaufbau/Speichern, bisheriges Modell bleibt erhalten.

## 0.2.0 – 2026-10-15

- Admin-Reiter für Einstellungen und KI-Protokoll mit getrenntem Zugriff ergänzt.
- Begrenztes Redaktionsprotokoll mit optional ausdrücklich aktiviertem Original-/KI-Textvergleich, Laufzeit, Fehlerreferenz und Beitragsverknüpfung hinzugefügt.
- Bestehendes Plugin-Banner lokal übernommen; Ajax-Vorschau, Ladeanzeige und dezente Options-/Vorschauanimationen mit reduzierter Bewegung ergänzt.
- Integration prüft zusätzlich Admin-/Redaktionszugriff, Textprotokoll ein/aus, sichere Ausgabe, Aufbewahrungsgrenze und Entwurfsverknüpfung.

## 0.1.0 – 2026-10-15

- Eigenständiges Drupal-Modul auf Basis von Drupal AI angelegt.
- Ausgangstext, Stilgruppen, Vorschautext- und Schlagwortvorschläge sowie Überarbeitungswünsche umgesetzt.
- Vorschau und kontogebundene Übergabe in den nativen Beitragseditor ergänzt; erste Speicherung als Entwurf.
- Getrennte Redaktions-/Adminrechte, kontrollierte Antwortverarbeitung und schlüssel-/textfreie Betriebsprotokolle ergänzt.
- Standardmäßig deaktivierte Anbieteranbindung; echte API-Prüfung erfordert separate Konfiguration.
- Im Testsystem elf WP-Stilgruppen und 49 bevorzugte Begriffe übernommen; Generierung, Überarbeitung und native Entwurfsspeicherung mit bestehendem OpenAI-Zugang erfolgreich geprüft.
