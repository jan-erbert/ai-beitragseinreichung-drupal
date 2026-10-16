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
