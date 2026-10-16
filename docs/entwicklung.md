# Architektur, Prüfungen und Grenzen

Eigenständiges Modul `je_ai_submission`, Version **1.0.1**, Lizenz GPL-2.0-or-later. Drupal AI übernimmt die Anbieteranbindung. Der Hauptbeitrag bleibt eine normale Drupal-Node; es gibt keinen zweiten Beitragsdatenspeicher des Moduls.

## Aufgabenverteilung

| Bestandteil | Aufgabe |
| --- | --- |
| `SubmissionForm` | Eingabe, KI-Vorschau, Änderungswünsche und private Übergabe. |
| `ArticleAssistant` / `DrupalAiGateway` | Redaktionelle Anfrage und Providerzugriff über Drupal AI. |
| `ArticleSuggestion` | Antwortschema, begrenzte Werte und normalisierte Vorschlagsdaten. |
| `DraftTransfer` / `ArticleDraftForm` | Kontogebundene Übergabe, Markdown-Verarbeitung und erste native Speicherung. |
| `EditorialFlow` | Entwurf oder ausdrückliche Veröffentlichung mit nativen Workflow-Rechten. |
| `SubmissionNotification` / `MailImages` | Eingereichte/veröffentlichte Revision, Empfängerstatus und lokale Mailbilder. |
| `AuditLog` / `LogController` | Begrenztes Redaktionsprotokoll, optionaler Textvergleich und getrennte Rechte. |
| `SettingsForm` / `StyleTransfer` | Einstellungen und kompatible Gruppenimporte/-exporte. |
| `StyleGroups` / `style-choices.js` | Gruppenerkennung, lokale Auswahlgrafiken und ältere Kennungszuordnungen. |
| `IconCatalog` / `VisualStyles` / `VisualPresentation` | Lokaler Symbolkatalog, Freigaben und geprüfte Zuordnung zu Textstellen. |
| `VisualPreservation` | Native CKEditor-Konfiguration zur Erhaltung erlaubter KI-only-Klassen. |

## Speicherung und externe Aufrufe

- Einstellungen und Gruppen sind Drupal-Konfiguration; Zugangsschlüssel liegen getrennt in Drupal Key beziehungsweise dessen Ablage.
- Private Übergaben sind kontogebunden, zeitlich auf eine Stunde begrenzt und gegen doppelte Anlage geschützt.
- Das KI-Protokoll liegt in einer eigenen begrenzten Datenbanktabelle. Textaufzeichnung ist standardmäßig aus und ausdrücklich konfigurierbar.
- `je_ai_submission.submissions` enthält Versandmetadaten je Beitrag und Ereignis, unabhängig von der Aufbewahrungsgrenze des KI-Protokolls. Reguläres Löschen des Beitrags entfernt die zugehörigen Versandmetadaten.
- Die gespeicherte Modellliste, ältere Gruppenalias-Zuordnungen und Informationen zur Editorsynchronisation verwenden Drupal State. Sie sind Laufzeitdaten, kein Stilgruppenexport.
- KI-Aufrufe entstehen bei Erstellung, Überarbeitung und dem ausdrücklichen Verbindungstest. Die Modellliste wird nur bewusst aktualisiert. Symbole, Animationen und Banner benötigen keinen externen Abruf.

Technische Systemlogs enthalten Metadaten und bereinigte Fehlerreferenzen, keine Beitragstexte oder Schlüssel. Zusätzliche AI-Logging-Module der Website sind davon getrennt zu betrachten.

## Gestaltungsadapter für Websites

`hook_je_ai_submission_visual_styles_alter(array &$definitions)` kann semantische Stile auf vorhandene Website-Klassen abbilden. Schlüssel sind `<pool-id>:heading`, `<pool-id>:inline`, `_lead` und `_table`. Nur `p`, `table`, `strong`, `h2` und einfache Klassennamen sind zugelassen. Die KI liefert weder SVG-Code noch freie HTML-Klassen.

Moduleigene Klassen beginnen mit `je-ai-`. Ihre Web- und PNG-Maildarstellung ist enthalten. Bei anderen Klassen stellt die Website ihre CSS- und Mailabbildung selbst bereit; die allgemeine Mailabbildung bearbeitet nur moduleigene Klassen.

`VisualStyles::syncEditor()` wird bei Pool- und Einstellungsspeicherung verwendet. Es ergänzt Stile im konfigurierten CKEditor-Textformat, erhält fremde Stile und erweitert die erlaubten HTML-Klassen. KI-only-Klassen werden über native General-HTML-Support-Konfiguration erhalten. Ein geeignet eingerichteter Editor und dessen `filter_html` bleiben Voraussetzung.

`assets/lucide/catalog.json.gz` enthält den festen lokalen Katalog. `scripts/build-lucide-catalog.cjs` baut ihn aus dem in `assets/lucide/SOURCE.txt` genannten Paket. Der Entwicklungsbuild benötigt Node.js und `sharp`; zur PHP-Laufzeit genügen die vorhandenen Drupal-Abhängigkeiten und Zlib. Keine Laufzeitrasterisierung auf dem Webserver. Lizenzhinweise in `assets/lucide/` bei Weitergabe erhalten.

PNG-Mailkopien liegen im Drupal-Temporärverzeichnis. Keine Laufzeitdateien oder Website-Branding in den Modul-Quellcode schreiben.

## Prüfungen

Im Modul-Checkout:

```bash
composer --no-plugins run lint
php tests/run.php
php tests/run.php /pfad/zum/drupal/vendor/autoload.php
```

Der letzte Aufruf prüft auch den Markdown-Renderer mit den Drupal-Abhängigkeiten. Ohne diesen Autoloader wird ein Test ausdrücklich übersprungen. Der zuletzt geprüfte vollständige Lauf umfasst **19 Tests**; er enthält keine KI-Anfrage.

Native Integrationsskripte liegen unter `scripts/`:

| Skript | Schwerpunkt |
| --- | --- |
| `check-drupal.php` | Vorschlag, Rechte, Entwurfsübernahme, private Übergabe und Pflichtfelder. |
| `check-editorial.php` | Releasehinweise, Gruppenaktionen, Fehlerfall und bestätigte Protokolllöschung. |
| `check-editorial-flow.php` | Exklusive Abschlusswege, Veröffentlichungsrechte und Pflichtfelder. |
| `check-notifications.php` | Versandereignisse, Revision, Empfänger, Wiederholungen und Testumleitung. |
| `check-completion.php` | Begriffshäufigkeiten, getrennte Rollen und lokale Mailbilder. |
| `check-log-display.php` | Sichere und lesbare Darstellung der Textvergleiche. |
| `check-visuals.php` | Mehrfache Freigabe, Editor/HTML-Filter, Schema und neutrale Symbol-Maildarstellung. |
| `check-style-groups.php` | Startgruppen, Gruppensymbole, Import, Aliasauflösung und anpassbarer Rollenstandard. |

Beispielaufruf aus dem Drupal-Projekt:

```bash
vendor/bin/drush php:script web/modules/custom/je_ai_submission/scripts/check-style-groups.php
```

Diese Integrationsskripte sind für die bestehende Referenz-Testinstallation geschrieben. Mehrere setzen insbesondere Admin UID 1, Redaktion UID 2 sowie vorhandene Artikel-, Medien- und Workflow-Konfiguration voraus. Sie sind **kein universeller Installer und kein Nachweis einer vollständig frischen Drupal-Installation**. Vor Verwendung auf einer anderen Installation die Fixtures und Erwartungen lesen. Sie verwenden zurückgerollte Prüfdaten und keine echten KI-Anfragen; Mailprüfungen verwenden einen Nulltransport.

Browserprüfungen ergänzen Formulartests: Maus-/Tastaturauswahl, Speichern, Fehlermeldungen und tatsächlich sichtbare Darstellung. Ein syntaktisch gültiger KI-Vorschlag bestätigt keine fachliche Richtigkeit.

## Bekannte Grenzen und Prüfung vor Veröffentlichung

| Thema | Stand |
| --- | --- |
| Vollständig frische, neutrale Drupal-Installation | Vor einer öffentlichen Veröffentlichung gesondert durchtesten. Der bisherige Integrationsnachweis stammt aus der Referenz-Testinstallation. |
| Widersprüchliche importierte Stiltexte | Redaktionell bereinigen, beispielsweise alte Emoji-Vorgaben neben einem Grundstil ohne Emojis. Keine automatische Umschreibung individueller Vorgaben. |
| Erneute Prüf-Einreichungsrunden | Keine beliebig wiederholbaren Prüfzyklen desselben Beitrags. Prüf- und erste Veröffentlichungsbenachrichtigung sind getrennte Ereignisse. |
| Unklarer Versand nach Prozessabbruch | Keine automatische Wiederholung; anhand der Mailserver-Protokolle administrativ klären. |
| Private und externe Mailbilder | Bewusst ausgeschlossen. Öffentliche lokale Dateien werden begrenzt verkleinert eingebettet. |
| Bilder/Kategorien im KI-Formular | Bewusst im normalen Beitragseditor belassen. |
| Newsletter, Jahresarchive, URL-Aliase, Galerie/Slider/Lightbox und Beitragsbildpflicht | Funktionen des Hauptprojekts, nicht des portablen Moduls. |
| GitHub-Wiki | Wird aus `docs/` erzeugt; direkte Wiki-Änderungen werden beim nächsten Abgleich ersetzt. |

## Dokumentation pflegen

Funktionsänderungen im selben Arbeitsgang in der jeweiligen Anleitung und im Changelog nachführen. Modulstandard, individuelle Website-Konfiguration und offene Entscheidungen getrennt beschreiben. Links, Navigation und Bilder prüfen; [MkDocs strikt bauen](backstage.md#lokal-prufen).

[Zur Startseite](index.md) · Weiter: [Dokumentation und Backstage](backstage.md)
