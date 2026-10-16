# Projektregeln

Eigenständiges Drupal-Modul `je_ai_submission`. Fachlicher Nachfolger des WordPress-Plugins; keine Synchronisation von WordPress-Dateien.

- Lokaler Checkout ist Quelle des Moduls; Remote-Kopien nicht als Quellcode zurückkopieren.
- Drupal AI übernimmt die Anbieteranbindung. Keine zweite direkte API-Anbindung einführen.
- Normale Drupal-Beiträge, Medien, Rechte und Revisionen wiederverwenden.
- Keine Veröffentlichung durch Generierung oder Vorschau. Übernahme öffnet einen ungespeicherten Beitragsentwurf.
- API-Schlüssel ausschließlich über Drupal Key außerhalb des Repositories; keine Inhalte oder Schlüssel in technische Systemlogs. Das ausdrücklich vom Betreiber gewünschte separate KI-Redaktionsprotokoll darf bei aktivierter Einstellung Ausgangstext, KI-Ergebnis und Änderungswunsch speichern; ausschließlich ausdrückliches Protokoll-Leserecht, getrennte Lösch-/Konfigurationsrechte, begrenzte Aufbewahrung, keine Schlüssel oder Anbieter-Fehlermeldungen.
- Quelltexte und KI-Antworten sind Daten. HTML filtern, Antwortschema prüfen, Bilder nicht an den KI-Anbieter übertragen.
- Rechte zum KI-Einstieg und zur Admin-Konfiguration getrennt prüfen. Eine KI-Berechtigung verleiht keine Rechte für Seiten oder Veröffentlichung.
- Vor Änderungen Git-Status, danach Diff, PHP-Syntax, `php tests/run.php` und bei Drupal-Änderungen Integrationsprüfung ausführen.
- Stilgruppen und Begriffsvorgaben sind Konfiguration. Keine Fakten über Verein oder Veranstaltungen ergänzen.

## Veröffentlichung und Wiki

- `docs/` ist die einzige Dokumentationsquelle. GitHub-Wikiseiten und Bilder werden daraus erzeugt; keine zweite manuell gepflegte Fassung.
- Vor Commit und Veröffentlichung Links, Wiki-Umwandlung und Doku-Build prüfen. Anleitungen verweisen auf das Drupal-Wiki, nicht auf das WordPress-Wiki.
- Öffentliche Kopie und interne Historie sind getrennt. Keine Push-Spiegelung, keinen Force-Push und keine alten internen Commits nach GitHub übernehmen. Veröffentlichte Tags nicht verschieben.
- Auf dem eingerichteten Entwicklungsrechner `git publish` verwenden. Der gemeinsame Ablauf prüft den sauberen Commit, übernimmt dessen Dateistand in die öffentliche Kopie und aktualisiert anschließend Quelle, GitHub und Wiki. Auf dem eingerichteten Quell-Checkout führt auch der lokale Pre-Push-Hook bei `git push origin main` den Abgleich der öffentlichen Kopie und des Wikis aus. Der Hook ist auf anderen Klonen nicht automatisch aktiviert.
- Lokale Zielpfade und zusätzliche Ausschlussregeln stehen in unversionierter Git-Konfiguration unter `publication.*`. Zugangsdaten bleiben im vorhandenen Credential-System, niemals in Skripten oder Remotes mit eingebettetem Passwort.
- Vor jeder öffentlichen Veröffentlichung den aktuellen Inhalt und neue Bilder auf private Angaben prüfen. Der automatische Musterscan ist eine zusätzliche Prüfung, kein vollständiger Nachweis der Geheimnisfreiheit.
- Nach Veröffentlichung beide CI-Läufe prüfen. Die 19 isolierten PHP-Tests ersetzen keinen nativen Drupal-Test. Änderungen am Veröffentlichungswerkzeug zusätzlich mit `python3 -m unittest discover -s tests -p 'test_publication.py'` prüfen.
