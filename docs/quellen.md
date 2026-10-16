# Quellen und Abgrenzung zum Original

Diese Anleitung beschreibt den Drupal-Quellcode und die Standardkonfiguration für **1.0.1**. Die Themen orientieren sich am Original-Wiki; die beschriebenen Funktionen am eigenständigen Drupal-Modul.

## Maßgebliche Quellen

- [Drupal-Wiki](https://github.com/jan-erbert/ai-beitragseinreichung-drupal/wiki): aus denselben Dateien unter `docs/` erzeugte Online-Anleitung.
- [WordPress-Originalprojekt](https://github.com/jan-erbert/ai-beitragseinreichung): fachlicher Vorgänger; kein Drupal-Installationspaket.
- Drupal-Repository: Formulare unter `src/Form/`, Dienste unter `src/Service/`, Zugriffe in `je_ai_submission.routing.yml` und `je_ai_submission.permissions.yml`.
- `config/install/je_ai_submission.settings.yml`: Vorgaben für neue Installationen.
- `config/schema/je_ai_submission.schema.yml` und `je_ai_submission.install`: Konfigurationsschema und Updates.
- `CHANGELOG.md`: umgesetzte Änderungen; die [Entwicklungsseite](entwicklung.md) nennt Prüfungen und Grenzen.
- `assets/lucide/SOURCE.txt` und `assets/lucide/LICENSE`: lokaler Symbolbestand und Lizenzhinweise.

## Wichtige Unterschiede zu WordPress

| Thema | Drupal-Modul |
| --- | --- |
| Beitragserstellung ohne KI | Bleibt der normale Drupal-Editor. |
| Anbieteranbindung | Drupal AI und Provider, Schlüssel über Drupal Key. |
| Medienpflege | Im normalen Editor; keine Bildübertragung an die KI. |
| Erstes Speichern | Entwurf oder ausdrückliche Veröffentlichung je Modus und nativen Rechten. |
| Prüfmodus | Entwurf, anschließend bestätigte Einreichung in den konfigurierten Zustand. |
| Benachrichtigungen | Nach Einreichung oder direkter Veröffentlichung; eigene Ereignisse, Revision und Empfängerauswahl. |
| Jahr | Anzeige und Datumsübernahme, kein automatisch angelegtes Jahres-Schlagwort. |
| Stilgruppen | Drei neutrale Startgruppen, weitere frei pflegbar; kompatible JSON-Grundformate. |
| Symbolik | Lokaler Lucide-Katalog, Gruppenerkennung und optionaler Text-Symbolpool. |
| Protokoll | Eigenes begrenztes Redaktionsprotokoll; Textaufzeichnung optional, Rechte getrennt. |

Ein Jahresarchiv, Newsletter, Galerien, Slider, Lightbox, URL-Aliase oder eine Beitragsbildpflicht müssen im Hauptprojekt eingerichtet werden. Solche Funktionen einer Beispielwebsite gehören nicht automatisch zum Modul.

[Zur Startseite](index.md)
