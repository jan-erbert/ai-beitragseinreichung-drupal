# Gemeinsam veröffentlichen

Die interne Entwicklung und die öffentliche GitHub-Kopie haben getrennte Historien. `docs/` ist gleichzeitig die Quelle für das GitHub-Wiki. Der gemeinsame Veröffentlichungsbefehl aktualisiert alle drei Ziele aus einem geprüften Commit.

## Auf dem eingerichteten Rechner

1. Änderungen im Quell-Checkout bearbeiten, prüfen und committen.
2. `git publish --check` für die Vorprüfung aufrufen.
3. Mit `git publish` Quelle, öffentliche Kopie und Wiki aktualisieren.
4. Die aktuellen Qualitätsläufe auf beiden Plattformen kontrollieren.

Nicht im öffentlichen Checkout weiterentwickeln oder Wiki-Seiten manuell bearbeiten. Die öffentliche Kopie und die erzeugten Wiki-Dateien werden aus der Quelle aktualisiert. Auf dem eingerichteten Quell-Checkout übernimmt der Pre-Push-Hook auch bei `git push origin main` den Abgleich der öffentlichen Kopie und des Wikis. Er stoppt den Quell-Push bei einem Fehler. Auf anderen Klonen ist dieser Hook zunächst nicht aktiviert; ohne Einrichtung überträgt ein gewöhnlicher Push nur an sein angegebenes Ziel.

## Was der Befehl prüft

- Alle drei Arbeitsbäume müssen sauber sein; veröffentlicht werden nur eingecheckte Quelldateien.
- Die Quelle und die öffentliche Kopie müssen auf `main` stehen. Ziel-URLs und der bekannte öffentliche Startcommit werden geprüft.
- Geschützte Dateinamen und typische Schlüssel-, Token- und Passwortmuster brechen den Vorgang ab. Zusätzliche lokale Ausschlussregeln sind möglich. Gefundene Werte werden nicht ausgegeben.
- Dokumentationsziele und Bilder müssen vorhanden sein. Wiki-Navigation stammt aus `mkdocs.yml`.
- Die lokalen PHP-Tests laufen ohne KI-Aufruf. Ohne Composer-Abhängigkeiten meldet der lokale Lauf den übersprungenen Markdown-Test; die CI prüft alle 19 Tests mit diesen Abhängigkeiten.
- Ein Fast-Forward-Abgleich erfolgt vor der Übernahme. Ändert er den Quellstand, ist ein neuer Aufruf nötig.

Der Musterscan erkennt keine beliebigen personenbezogenen Angaben und kann Bildinhalte nicht beurteilen. Texte, Konfigurationen und neue Screenshots deshalb weiterhin vor der Veröffentlichung prüfen. Kein Force-Push, kein History-Mirror und keine automatische Übernahme älterer Tags.

## Auf einem weiteren Rechner einrichten

Benötigt werden Git, Python ab 3.11, PHP sowie der bereits eingerichtete Git-Zugang zu beiden Anbietern. Für die vollständigen PHP-Tests gelten die Composer-Abhängigkeiten; für den Dokumentations-Build MkDocs. Zugangsdaten nicht in Befehle, Skripte oder Repository-Dateien schreiben.

Den internen Quell-Checkout, die öffentliche Kopie und das Wiki getrennt klonen. Das Wiki muss auf GitHub einmal mit einer ersten Seite angelegt worden sein. Danach im Quell-Checkout die lokalen Git-Einstellungen setzen:

```bash
git config publication.mode source
git config publication.publicCheckout /absoluter/pfad/oeffentliche-kopie
git config publication.wikiCheckout /absoluter/pfad/wiki-kopie
git config publication.publicUrl https://github.com/jan-erbert/ai-beitragseinreichung-drupal.git
git config publication.wikiUrl https://github.com/jan-erbert/ai-beitragseinreichung-drupal.wiki.git
git config publication.publicRoot 08288913641b5d9475d9ba593f7a96a158a10f89
git config alias.publish '!python3 scripts/publish.py'
git config core.hooksPath .githooks
```

Vor dem Setzen von `core.hooksPath` vorhandene Hook-Konfiguration prüfen; fremde Hooks nicht überschreiben. Der mitgelieferte Hook greift nur bei einem ausdrücklich als Quelle eingerichteten Checkout und einem `main`-Push nach `origin`. Tags und andere Branches bleiben gesonderte Schritte.

`publication.publicRoot` ist der bestätigte öffentliche Startcommit dieses Projekts. Für eine eigene abgeleitete Veröffentlichung den eigenen freigegebenen Startcommit und die eigenen Ziele verwenden. Zusätzliche lokale Ausschlüsse lassen sich mit `git config --add publication.forbiddenPattern 'REGULAERER_AUSDRUCK'` hinterlegen. Diese Git-Konfiguration wird nicht veröffentlicht.

## Wiki-Abgleich und Fehlerfälle

Die Markdown-Seiten aus `docs/` werden zu Wiki-Seiten, `index.md` zu `Home.md`. Bilder bleiben enthalten; Links werden auf Wiki-Seiten und die Wiki-Bildablage umgesetzt. Seitenleiste und Footer werden erzeugt. Eine Manifestdatei merkt sich die verwalteten Wiki-Dateien. Entfernt wird nur zuvor erzeugter Bestand; andere Dateien bleiben erhalten.

Die drei Pushes sind keine gemeinsame Transaktion. Scheitert ein Ziel, stoppt der Befehl mit Fehlerstatus. Bereits erfolgreiche Pushes bleiben bestehen. Ursache prüfen und `git publish` erneut starten; unveränderte Dateien erzeugen keine neuen Commits. Ein erfolgreicher Push ersetzt die anschließende Prüfung der CI-Ergebnisse nicht. Tags, Releases und ein Drupal-Deployment bleiben bewusste separate Schritte.

[Zur Startseite](index.md) · [Entwicklung](entwicklung.md)
