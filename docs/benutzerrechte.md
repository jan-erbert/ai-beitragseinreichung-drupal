# Benutzerrechte und Rollen

Drupal trennt Inhaltstypen und Berechtigungen. Eine Rolle kann Beiträge bearbeiten dürfen, ohne Seiten bearbeiten zu dürfen. Das Modul ergänzt vier unabhängig verwaltbare Rechte:

| Recht | Erlaubt |
| --- | --- |
| `use je ai submission` | KI-Einstieg nutzen, mit zusätzlichen Beitrags- und Textformatrechten. |
| `view je ai submission log` | Alle KI-Vorgänge und gegebenenfalls gespeicherte Textvergleiche lesen. |
| `delete je ai submission log` | Einzelne Protokolleinträge nach Bestätigung löschen. Zusätzlich ist Lesen nötig. |
| `administer je ai submission` | Einstellungen, Gruppen, Symbolpool, Empfänger, Grenzen und Verbindungstest verwalten. |

## Der Standard ab 0.8.0

Bei Installation und dem einmaligen Update `11006` erhalten bereits für die KI freigegebene Rollen zusätzlich das Protokoll-Leserecht. Wird das KI-Recht später erstmals einer Rolle zugewiesen, ergänzt das Modul Lesen ebenfalls als Standard. **Löschen und Einstellungen werden nicht automatisch vergeben.**

Die Freigabe hängt vom KI-Recht ab, nicht vom Rollennamen. Eigene Rollen werden unterstützt. Eine native Volladministrationsrolle darf weiterhin alles.

Alle Rechte bleiben nativ anpassbar. Ein späterer manueller Entzug des Leserechts bleibt bei gewöhnlichem Speichern der Rolle erhalten. Wird das KI-Recht entfernt und später neu vergeben, gilt erneut der Lese-Standard.

## Freigaben bearbeiten

Öffne **Benutzer → Berechtigungen** oder `/admin/people/permissions` und suche **JE KI-Beiträge**. Lege je Rolle KI-Nutzung, Protokolllesen, Löschen und Einstellungen fest. Mehrere Rollen eines Kontos ergänzen sich.

Die Moduleinstellungen zeigen **Zugriff und Rollen** mit einer Übersicht und, bei entsprechender Berechtigung, einem Link zur nativen Rechteverwaltung. Es gibt keine zweite Rollenverwaltung im Modul.

Beispiele:

- Redaktion: KI vorbereiten und Protokoll lesen; keine Einstellungen und keine Einzellöschung.
- Protokoll-Prüfung: nur das Leserecht; keine KI-Erstellung erforderlich.
- Protokollpflege: Lesen und Löschen, ohne Einstellungen.
- Technische Konfiguration: Einstellungsrecht, ohne gesondert vergebene Protokollrechte.

## Umfang des Leserechts

Lesen umfasst **alle** protokollierten Vorgänge und bei aktivierter Textaufzeichnung vollständige Ausgangs- und Ergebnistexte. Es ist kein Recht ausschließlich für eigene Beiträge. Die Vergabe sollte zu den tatsächlich gewünschten Einsichtsrechten passen.

## Normale Drupal-Rechte bleiben nötig

Zusätzlich zum KI-Recht braucht ein Konto Erstellungsrechte für den Ziel-Inhaltstyp und Zugriff auf das konfigurierte Textformat. Einreichen und Veröffentlichen benötigen die nativen Moderationsrechte. Das Modul verleiht keine Seitenrechte und richtet keine eingeschränkte Inhaltsliste ein.

Die private Übergabe in den Editor ist kontogebunden. Danach gelten die normalen Beitragsrechte. Auch ein Bearbeitungslink aus einer Mail verleiht keinen zusätzlichen Zugriff.

[Zur Startseite](index.md) · Weiter: [Protokoll](protokoll.md)
