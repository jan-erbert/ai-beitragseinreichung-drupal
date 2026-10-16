# Dokumentation und Backstage

Die Anleitung wird mit dem Modul gepflegt. Markdown-Seiten und lokale PNG-Abbildungen liegen unter `docs/`, die Navigation unter `mkdocs.yml`. Relative Links funktionieren in der Repository-Ansicht und im Dokumentations-Build.

## Aufbau

- Redaktion: Formular, gute Ausgangstexte, Beitragsablauf und KI-Funktionen.
- Administration: Installation, Einstellungen, Stilgruppen, Symbole, Rechte und Protokoll.
- Entwicklung: Architektur, Prüfungen, Grenzen, Dokumentationspflege und Quellen.
- Bilder: Abbildungen der Bedienoberfläche unter `docs/images/`.

Die Anleitung ist allgemein. Konfiguration, Zugangsdaten und individuelle Betriebsdetails einer Website gehören in deren eigenes Repository beziehungsweise Betriebsdokumentation.

## Lokal prüfen

Mit installiertem MkDocs im Repository ausführen:

```bash
mkdocs build --strict --site-dir /tmp/je-ai-submission-docs
```

Der Ausgabeordner liegt außerhalb des Repositories und gehört nicht in Git. Die Vorschau veröffentlicht kein Wiki. Für den Dokumentations-Build werden keine zusätzlichen Drupal-Laufzeitabhängigkeiten benötigt.

Nach Änderungen Navigation, lokale Links, Abschnittsverweise und Bildpfade prüfen. Aufnahmen visuell auf Lesbarkeit, aktuelle Beschriftungen und unbeabsichtigte Kontodaten prüfen. Statusmeldungen nicht in Bedienungsbilder übernehmen; passende Ausschnitte der tatsächlichen Oberfläche wählen.

## In Backstage verwenden

Ein Backstage-TechDocs-Einstieg benötigt eine registrierte Komponente, Lesezugriff auf das private Repository und die dort konfigurierte Build-/Veröffentlichungskette. Die zuständige Komponente kann `backstage.io/techdocs-ref: dir:.` verwenden. Bei einem TechDocs-Core-Build wird dessen `techdocs-core`-Plugin in der Buildumgebung benötigt; der eigenständige MkDocs-Build dieser Anleitung erzwingt es nicht.

Die Ablage unter `docs/` registriert keine Komponente und setzt keine öffentliche Freigabe voraus. Ob neue Commits automatisch erscheinen, bestimmt die jeweilige Backstage-Anbindung. Ein erfolgreicher Push bestätigt zunächst nur den Repository-Stand; eine veröffentlichte Ausgabe muss gesondert geprüft werden.

## Späteres Wiki

Ein eigenes öffentliches Forgejo-/GitHub-Wiki ist weiterhin zurückgestellt. Die Dateien unter `docs/` bleiben die gepflegte Quelle. Bestehende Links auf das WordPress-Wiki sind vorläufige Querverweise, kein Ersatz für die Drupal-Anleitung.

[Zur Startseite](index.md) · Weiter: [Quellen](quellen.md)
