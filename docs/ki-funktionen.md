# KI-Funktionen

Die Anbindung erfolgt über **Drupal AI** und einen konfigurierten Anbieter. Das Modul besitzt keinen zweiten direkten OpenAI-Client. Die KI wird nur durch die ausdrücklich ausgelöste Erstellung, Überarbeitung oder den administrativen Verbindungstest aufgerufen.

## Was die KI vorbereitet

- Einen passenden Titel, bei Bedarf aus dem Ausgangstext.
- Einen strukturierten Beitragstext nach Grundstil und gewählter Stilgruppe.
- Optional einen kurzen Vorschautext.
- Optional zusätzliche Schlagwörter nach den gepflegten Regeln.
- Eine Überarbeitung anhand eines konkreten Änderungswunsches.
- Optional freigegebene Symbolzuordnungen, eine hervorgehobene Einleitung und gestaltete Ergebnistabellen.

Die Antwort wird strukturiert angefordert und vor der Übernahme geprüft. Das sichert das erwartete Datenformat, bestätigt aber keine Fakten. Du prüfst den Inhalt weiterhin selbst.

## Formatierung

Unterstützt sind unter anderem Überschriften, Absätze, Listen, Hervorhebungen, Zitate, Links und einfache Markdown-Tabellen. Das Modul wandelt Markdown für die Vorschau und Drupal-Übernahme in gefiltertes HTML um. Freies HTML, unsichere Links und Markdown-Bilder werden nicht übernommen.

Bilder wählst du anschließend im normalen Editor aus. Das Modul sendet keine Bilddateien an den KI-Anbieter. Ausgangstext und die für den Vorschlag benötigten Textvorgaben werden dagegen an den konfigurierten Anbieter übertragen.

Für [Symbole und Gestaltung](symbole.md) erhält die KI nur den explizit freigegebenen Vorrat. Ihre Zuordnungen werden separat vom Markdown geprüft. Farben und konkrete HTML-Klassen kommen vom Modul beziehungsweise der Website. Das Erkennungszeichen einer Beitragsart ist davon unabhängig.

## Schlagwörter und Jahr

Eigene Tags bleiben erhalten. Zusätzliche KI-Vorschläge berücksichtigen je nach Konfiguration den bevorzugten Begriffspool, vorhandene Begriffe und einen eindeutig im Ausgangstext erkennbaren Ort. Das zusätzliche KI-Limit ist administrativ zwischen 1 und 12 einstellbar. Reine Jahreszahlen werden nicht als neue Tags ergänzt.

Die separate Jahresanzeige folgt dem Beitragsdatum. Eine Jahresübersicht kann das Hauptprojekt darauf aufbauen; das KI-Modul übergibt das gewählte Datum an Drupal, richtet aber keinen Archivfilter ein.

## Vorschautext

Ist **Vorschautext von der KI vorschlagen lassen** aktiv, bleibt das manuelle Eingabefeld unsichtbar. Bei abgewählter Option kannst du einen eigenen Text eintragen. Die Übergabe speichert ihn im Zusammenfassungsfeld des Beitragstextes. Ein späterer Newsletter kann diesen Wert nutzen; Newsletter-Versand gehört derzeit nicht zum Modul.

## Dynamische Oberfläche

Lokale Lottie-Animationen begleiten Verarbeitung, Erfolg, Fehler und ausgewählte Hinweise. Die Oberfläche berücksichtigt reduzierte Bewegung. Originale breite und mobile Banner werden abhängig von der Bildschirmbreite ausgewählt. Die Animationen benötigen kein externes CDN.

**Was ist neu?** zeigt die Release-Hinweise. Der Hinweis erscheint einmal pro Version und Konto; der Reiter bleibt erneut erreichbar. Der dortige Wiki-Link verweist vorläufig weiterhin auf das WP-Original. Die Drupal-Anleitung liegt bereits hier im Repository.

[Zur Startseite](index.md) · Weiter: [Einstellungen](einstellungen.md)
