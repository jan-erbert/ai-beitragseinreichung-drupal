# JE KI-Beiträge für Drupal

Zusätzliche Beitragserstellung mit Drupal AI: Ausgangstext eingeben, KI-Vorschlag prüfen und im normalen Drupal-Beitragseditor weiterbearbeiten. Die klassische Erstellung ohne KI bleibt erhalten.

**Stand: Version 1.0.1.** Die vollständige, allgemein gehaltene [Anleitung unter docs](docs/index.md) beschreibt Redaktion, Administration und Entwicklung mit Abbildungen der Bedienoberfläche. `mkdocs.yml` enthält die Navigation. Das [GitHub-Wiki](https://github.com/jan-erbert/ai-beitragseinreichung-drupal/wiki) wird aus denselben Dateien erzeugt.

## Funktionsumfang

- Vorschlag und Überarbeitung mit zusätzlichen Hinweisen, Vorschautext und passenden Schlagwörtern.
- Frei pflegbare Stilgruppen mit eigenem Erkennungszeichen; drei neutrale Startgruppen auf neuen Installationen.
- Optionaler Symbolpool und Gestaltungsregeln je Gruppe, mit lokalem Lucide-Katalog und getrennten manuellen/KI-Freigaben.
- Ungespeicherte Übernahme in den nativen Editor; Entwurf oder ausdrücklicher Abschluss je konfiguriertem Modus und Drupal-Rechten.
- Benachrichtigungen nach Prüfungseinreichung oder direkter Veröffentlichung, mit Revision, Empfängerauswahl, lokalen Bildern und Versandstatus.
- Getrennte Rechte für KI, Protokolllesen, Löschen und Einstellungen. KI-Rollen erhalten Lesen als anpassbaren Standard.
- Lokal ausgelieferte Banner, Symbolgrafiken und Lottie-Animationen.

Generierung und Vorschau veröffentlichen nichts. Bilder werden im normalen Editor ergänzt und nicht an den KI-Anbieter übertragen. Newsletter, Jahresarchive, Galerien und websiteeigene Pflichtfelder gehören zum Hauptprojekt.

## Einstieg und Voraussetzungen

PHP ab 8.3 mit Zlib, Drupal 11.2 oder neuer innerhalb Version 11, Drupal AI 1.5 oder kompatible Version 1, geeigneter Provider mit strukturierten Chat-Antworten. Composer-Abhängigkeiten und benötigte Kernmodule stehen in `composer.json` und `je_ai_submission.info.yml`. Beitragstyp, Textformat, Taxonomie und Workflow müssen zur Konfiguration passen.

- Modul: `je_ai_submission`
- Formular: `/admin/content/ki-beitrag`
- Einstellungen: `/admin/config/content/je-ai-submission`
- Symbolpool: `/admin/config/content/je-ai-submission/symbols`
- Protokoll: `/admin/content/ki-beitrag/protokoll`

Die Installation startet mit ausgeschalteter KI und KI-Gestaltung, ohne Modellkennung und ohne Schlüssel. Schlüssel ausschließlich über Drupal AI/Key in geschützter Ablage einrichten. Keine Schlüssel in Stiltexten, Konfigurationsexporten oder Git speichern.

Weiter: [Installation](docs/installation.md) · [Stilgruppen](docs/stilgruppen.md) · [Symbole](docs/symbole.md) · [Rechte](docs/benutzerrechte.md)

## Entwicklung und Dokumentation

```bash
php tests/run.php
php tests/run.php /pfad/zum/drupal/vendor/autoload.php
composer --no-plugins run lint
mkdocs build --strict --site-dir /tmp/je-ai-submission-docs
```

Der vollständige isolierte Testlauf umfasst derzeit 19 Tests. Ohne Drupal-/Composer-Autoloader wird der Markdown-Test ausdrücklich übersprungen. Die nativen Integrationsskripte unter `scripts/` setzen teilweise Fixtures der Referenz-Testinstallation voraus; Voraussetzungen, bekannte Grenzen und Website-Adapter stehen unter [Entwicklung](docs/entwicklung.md).

Modullizenz: GPL-2.0-or-later. Lizenzhinweise des lokalen Lucide-Bestands liegen unter `assets/lucide/`. Die Dokumentation bleibt unabhängig von Branding, Betriebsadressen und individueller Konfiguration einer Beispielwebsite.

## GitHub und Qualität

Das öffentliche Repository heißt [ai-beitragseinreichung-drupal](https://github.com/jan-erbert/ai-beitragseinreichung-drupal). Die öffentliche Historie beginnt mit dem bereinigten Stand 1.0.0. Ältere interne Entwicklungsstände werden nicht übernommen.

GitHub Actions prüft Composer-Metadaten, PHP-Syntax und die isolierten Tests mit Composer-Abhängigkeiten unter PHP 8.3 und 8.4. Native Drupal-Integrationstests erfordern zusätzlich eine passend eingerichtete Testinstallation; sie werden durch diese Prüfungen nicht ersetzt. Dependabot prüft monatlich Composer- und Action-Abhängigkeiten. Änderungen an Abhängigkeiten werden als Pull Request vorgeschlagen.

Für Forgejo liegt dieselbe PHP-Prüfung unter `.forgejo/workflows/quality.yml`. Dort sind Action-Quellen als vollständige URLs angegeben; GitHub verwendet die übliche Schreibweise unter `.github/workflows/quality.yml`. Beide prüfen PHP 8.3 und 8.4. Composer-Plugins bleiben bei der isolierten Paketinstallation und beim Lint ausgeschaltet; Website-Dateien werden dabei nicht erzeugt oder verändert.

## Gemeinsam veröffentlichen

Im als Quelle eingerichteten Checkout `git publish` verwenden. Der Befehl prüft den eingecheckten Stand und aktualisiert Forgejo, die öffentliche GitHub-Kopie und deren Wiki. `git publish --check` führt nur die Vorprüfung aus. Rohes `git push` betrifft weiterhin nur sein jeweiliges Ziel und ersetzt diesen gemeinsamen Ablauf nicht.

Einrichtung, Grenzen und Umgang mit Teilfehlern: [Veröffentlichungsanleitung](docs/veroeffentlichen.md). Die vollständige interne Historie darf nicht auf den öffentlichen Branch gespiegelt werden. Wiki-Seiten ausschließlich unter `docs/` bearbeiten.
