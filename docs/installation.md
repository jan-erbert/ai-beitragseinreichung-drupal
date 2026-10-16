# Installation und Einrichtung

Diese Seite richtet sich an die technische Administration. Das Modul wird in ein bestehendes Drupal-Projekt eingebunden. Es ersetzt weder dessen Beitragskonfiguration noch dessen Redaktionsworkflow.

## Voraussetzungen

| Voraussetzung | Erwarteter Stand |
| --- | --- |
| PHP | Ab 8.3 |
| Drupal | Ab 11.2 innerhalb der Hauptversion 11 |
| Drupal AI | Version 1.5 oder kompatible neuere Version innerhalb der Hauptversion 1 |
| PHP-Erweiterung | Zlib für den lokalen Symbolkatalog |
| Markdown-Verarbeitung | `league/commonmark` ab 2.8 innerhalb der Hauptversion 2, über Composer |
| Drupal-Kernmodule | Node, Text, File, Taxonomy, Content Moderation und CKEditor 5 |
| KI-Anbieter | Passender Drupal-AI-Provider mit Chat und strukturierten Antworten |
| Beitragstyp | Standardmäßig `article`, mit `body` und `field_tags` |
| Schlagwort-Vokabular | Standardmäßig `tags` |
| Textformat | Standardmäßig `basic_html`, für die berechtigten Konten nutzbar |

Das Inhaltsmodell muss vorhanden sein. Ein Moderationsworkflow für den Beitragstyp muss den Entwurfszustand `draft` unterstützen. Für Einreichungen ist zusätzlich ein unveröffentlichter Prüfzustand (standardmäßig `review`) mit berechtigtem Übergang vom Entwurf erforderlich. Das Modul erstellt keinen eigenen Workflow und keine Medienbibliothek. Der normale Editor wird von der Website eingerichtet. Wenn CKEditor 5 und ein HTML-Filter vorhanden sind, ergänzt die Symbolpool-Speicherung gezielt Stile und erlaubte Klassen im konfigurierten Textformat. Fremde Editorstile bleiben erhalten.

## Modul einbinden

Das Composer-Paket heißt `jan-erbert/ai-beitragseinreichung-drupal`, der Drupal-Modulname `je_ai_submission`. Das öffentliche [Quell-Repository auf GitHub](https://github.com/jan-erbert/ai-beitragseinreichung-drupal) stellt veröffentlichte Versionen bereit. Die Composer-Einbindung wird im jeweiligen Drupal-Hauptprojekt eingerichtet. Zugangsdaten einer eigenen privaten Paketquelle gehören in eine geschützte Ablage, nicht in Projektdateien.

Nach Einbindung des Pakets und seiner Abhängigkeiten aktivierst du das Modul unter **Erweitern** oder mit Drush:

```bash
vendor/bin/drush en je_ai_submission -y
vendor/bin/drush cr
```

Bei späteren Modul-Updates sind gegebenenfalls Datenbank-Updates erforderlich:

```bash
vendor/bin/drush updb -y
vendor/bin/drush cr
```

Die erste Installation startet mit deaktivierter KI, deaktivierter KI-Gestaltung und ohne Modellkennung oder API-Schlüssel. Drei neutrale [Stilgruppen](stilgruppen.md#die-drei-startgruppen) und zwei neutrale Pool-Symbole sind enthalten. Updates ersetzen keine individuellen Gruppen. Update `11006` ergänzt Gruppensymbole und den neuen Protokoll-Lese-Standard für KI-Rollen.

## Anbieter und Schlüssel einrichten

1. Installiere und konfiguriere den passenden Provider für Drupal AI.
2. Hinterlege dessen Schlüssel über Drupal Key, vorzugsweise mit geschützter Datei oder einer geeigneten externen Ablage.
3. Öffne `/admin/config/content/je-ai-submission`.
4. Wähle Anbieter und ein verfügbares Modell, das strukturierte Antworten unterstützt.
5. Prüfe Grundstil, Stilgruppen, Schlagwortregeln und Nutzungsgrenzen.
6. Starte bewusst **Verbindung jetzt testen**. Der Test sendet einen kurzen technischen Text und kann API-Kosten verursachen.
7. Aktiviere die KI-Beitragserstellung und speichere die Konfiguration.

Das Modul besitzt kein eigenes API-Schlüsselfeld. Ein Schlüssel gehört weder in Stilvorgaben noch in Konfigurationsexporte oder Git.

## Einreichungen und Mailversand einrichten

Wähle zuerst den gewünschten Abschlussweg. Für den standardmäßigen Prüfmodus ergänze im nativen Workflow einen unveröffentlichten Prüfzustand und den Übergang vom Entwurf dorthin. Für direkte Veröffentlichung ist stattdessen ein erlaubter Übergang in einen veröffentlichten Zustand nötig. Erlaube der Redaktion diesen Übergang. Ein Rückweg zum Entwurf und ein berechtigter Veröffentlichungsweg können im Workflow ergänzt werden. Aktiviere die Benachrichtigungen erst mit eingerichtetem Mailtransport und gültigen Empfängerkonten. Die [Einstellungen](einstellungen.md) erklären die Optionen.

Ohne Anpassung zeigt die Mail den AI-Beitragseinreichung-Header aus den Modul-Assets. Optional kann die Administration eine eigene Headergrafik hochladen. Es werden keine Vereinsgrafiken, Kontaktadressen oder projektspezifischen Mailvorlagen als Modulstandard ausgeliefert.

Die allgemeine HTML-Mailvorlage `templates/je-ai-submission-email.html.twig` kann im Website-Theme überschrieben werden. Für deren Darstellung benötigt Drupal einen HTML-fähigen Mailer wie Symfony Mailer Lite. Ein einfacher Core-Mailer erhält den Textinhalt. Header, Footer, Antwortadresse und eingebettete Branding-Bilder kann das Hauptprojekt über seine Mailvorlage und Mail-Hooks ergänzen.

## Berechtigungen und erster Test

Vergib die [getrennten Rechte für Redaktion und Administration](benutzerrechte.md). Beim erstmaligen Zuweisen des KI-Rechts erhält die Rolle auch Protokolllesen als Standard; kontrolliere diesen Zugriff ausdrücklich. Er umfasst alle gespeicherten Vorgänge und bei aktivierter Textaufzeichnung deren Inhalte. Prüfe den Ablauf einmal mit einem gewöhnlichen Redaktionskonto: Vorschlag erzeugen, kontrollieren, im normalen Editor öffnen und als Entwurf speichern. Prüfe anschließend die Inhaltsliste und als Admin die Beitragsverknüpfung im Protokoll.

Kontrolliere außerdem die Regeln des Hauptprojekts für Beitragsbilder, Veröffentlichungen, Medien und Vorschautexte. Diese werden nicht durch die Modulinstallation eingerichtet.

[Zur Startseite](index.md) · Weiter: [Einstellungen](einstellungen.md)
