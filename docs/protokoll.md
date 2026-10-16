# Protokoll und Fehlersuche

Das KI-Protokoll liegt unter `/admin/content/ki-beitrag/protokoll`. Übersicht und Details benötigen `view je ai submission log`. Löschen benötigt zusätzlich `delete je ai submission log`; Einstellungen werden gesondert freigegeben. Neue KI-Freigaben erhalten Lesen als anpassbaren Standard; Löschen bleibt ein gesondertes Recht. Siehe [Benutzerrechte](benutzerrechte.md).

## Was protokolliert wird

Die Übersicht zeigt Zeitpunkt, Konto, Erstellung oder Überarbeitung, Stilgruppe, Modell, Ergebnis und Dauer. In den Details stehen gegebenenfalls eine bereinigte Fehlerreferenz und nach der Entwurfsspeicherung **Beitrag öffnen**.

**„Erfolgreich“ bedeutet, dass die KI-Anfrage erfolgreich war.** Es bestätigt weder eine abgeschlossene Einreichung noch eine Veröffentlichung oder einen Mailversand. Ein KI-Vorschlag kann protokolliert sein, obwohl noch kein Beitrag gespeichert wurde.

Bei aktivierter Textaufzeichnung werden zusätzlich Ausgangstext einschließlich zusätzlicher KI-Hinweise, KI-Ergebnis, vorheriger Vorschlag und Änderungswunsch gespeichert. Die Standardinstallation zeichnet nur Metadaten auf. Frühere Vorgänge werden nicht nachträglich mit Texten ergänzt.

## Textvergleich lesen

Ausgangstext und aktuelles KI-Ergebnis stehen am Desktop nebeneinander und bei schmaler Anzeige untereinander. Eigene Bereiche gliedern Titel, Beitragstext, Vorschautext, Schlagwörter und zusätzliche Hinweise. Der Ausgangstext bleibt wörtlich erhalten; KI-Markdown wird sicher formatiert dargestellt. Bei Überarbeitungen lässt sich der vorherige Vorschlag zusätzlich aufklappen. Ein eigener Änderungswunsch steht unter dem Vergleich. Die Metadatenleiste zeigt Zeitpunkt, Modell, Stilgruppe, Vorgang, Dauer und Ergebnis.

## Aufbewahren und löschen

Standardmäßig bleiben 100 Vorgänge erhalten; die Administration kann 1 bis 500 einstellen. Bei neuen Einträgen oder beim Speichern einer niedrigeren Grenze werden die ältesten überzähligen Einträge entfernt.

Das Abschalten der Textaufzeichnung betrifft neue Vorgänge. Bereits gespeicherte Vergleiche bleiben bis zur normalen Aufbewahrungsgrenze oder Einzellöschung erhalten. **Protokolleintrag löschen** öffnet eine Bestätigung und entfernt nur diesen Eintrag, keinen Beitrag.

Das Protokoll liegt in der Drupal-Datenbank. Es gehört damit zum Datenbank-Backup. Eine Löschung im laufenden System entfernt nicht automatisch vorhandene Backup-Kopien. Technische Systemlogs enthalten keine Beitragstexte oder Schlüssel.

## Einreichung und Versand getrennt prüfen

Der Versandstatus liegt beim Beitrag unter **Einreichung und Versandstatus**, nicht im KI-Generierungsprotokoll. Er merkt die eingereichte Revision und die einzelnen Empfänger unabhängig von dessen Aufbewahrungsgrenze. Gewöhnliche Bearbeitungen versenden nicht erneut. Die Versandmetadaten werden beim regulären Löschen des Beitrags entfernt und gehören bis dahin zur Drupal-Datenbank samt deren Sicherung.

## Typische Fragen und Fehler

| Beobachtung | Prüfen |
| --- | --- |
| KI-Menü fehlt oder Zugriff verweigert | KI-Recht, Erstellungsrecht für den Ziel-Inhaltstyp und Nutzungsrecht für das Textformat. |
| Eingabe zu kurz | Angezeigte Mindestwortzahl erfüllen; nicht mit erfundenen Angaben auffüllen. |
| Anfragelimit erreicht | Administratives Stundenlimit prüfen und später erneut versuchen. |
| Modell fehlt im Dropdown | Modellliste ausdrücklich aktualisieren oder gültige eigene Kennung eintragen. |
| Verbindungstest schlägt fehl | Provider-Konfiguration, geschützte Key-Zuordnung, Anbieterzugang, Modellverfügbarkeit und Unterstützung strukturierter Antworten. |
| Einstellungen reagieren langsam | Größe importierter Stilvorgaben prüfen; Modelllisten werden nicht bei jedem Seitenaufruf geladen. |
| Übergabe nicht mehr erreichbar | Richtiges Konto und Ablaufzeit von einer Stunde prüfen; zuerst nach einem bereits gespeicherten Entwurf suchen. |
| Kein Beitrag in der Inhaltsliste | Generierung und Editoröffnung speichern noch nichts. Entwurf speichern und Zugriffsregeln/Filter prüfen. |
| Veröffentlichung scheitert | Normale Drupal-Validierung, Moderationsrechte und projektspezifische Pflichtfelder prüfen. |
| Nach Abschluss kommt keine Mail | Benachrichtigungen, Empfängerkonten und Autor prüfen. Je Modus ausdrücklich einreichen oder veröffentlichen; ein Entwurf versendet nicht. Versandstatus, Mailtransport und mögliche globale Testumleitung prüfen. |
| Symbole fehlen im Vorschlag | Globale Freigabe, Gruppenfreigabe, ausgewählte Symbole und KI-Freigabe im Pool prüfen; siehe [Symbol-Anleitung](symbole.md). |
| Symbolname wird abgelehnt | Exakten Namen aus dem mitgelieferten lokalen Lucide-Katalog verwenden. Eine neuere Lucide-Webseite kann zusätzliche, hier noch nicht enthaltene Symbole zeigen. |
| Nach manueller Bearbeitung fehlt ein Stil | Textformat, CKEditor-Konfiguration und Poolfreigaben prüfen; nicht ungeprüft HTML-Freigaben erweitern. |

Bei einer fehlgeschlagenen KI-Anfrage bleiben Ausgangstext und vorhandener Vorschlag erhalten. Notiere für eine technische Prüfung die Fehlerreferenz und den Zeitpunkt. Gib keine Schlüssel in Supporttexte ein.

[Zur Startseite](index.md) · Weiter: [Entwicklung und offene Funktionen](entwicklung.md)
