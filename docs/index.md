# JE KI-Beiträge für Drupal

**Anleitung für Version 1.0.1**

Das Modul ergänzt Drupal um eine zweite Möglichkeit, Beiträge vorzubereiten. Aus Text oder Stichpunkten entsteht ein KI-Vorschlag. Du prüfst ihn und bearbeitest ihn anschließend im normalen Beitragseditor. Die klassische Beitragserstellung bleibt erhalten.

Diese Dokumentation beschreibt das Modul allgemein. Eigene Website-Farben, spezielle Stilgruppen, Beitragsbildpflichten oder Galerien sind keine allgemeinen Modulvorgaben.

## Schnell zum passenden Thema

| Du möchtest … | Anleitung |
| --- | --- |
| die Eingabefelder verstehen | [Formular-Übersicht](formular.md) |
| einen Beitrag erstellen und abschließen | [Beitrag vorbereiten und speichern](beitrag-einreichen.md) |
| bessere Ausgangstexte liefern | [Gute Beiträge schreiben](gute-beitraege.md) |
| Vorschautext und Schlagwörter nutzen | [KI-Funktionen](ki-funktionen.md) |
| Symbole und Tabellen einsetzen | [Symbole und Gestaltung](symbole.md) |
| das Modul installieren | [Installation](installation.md) |
| Modell, Mails oder Grenzen einstellen | [Einstellungen](einstellungen.md) |
| Beitragsarten und ihre Symbole pflegen | [Stilgruppen](stilgruppen.md) |
| Rollen und Protokollzugriff festlegen | [Benutzerrechte](benutzerrechte.md) |
| Fehler oder einen Vorgang nachvollziehen | [Protokoll](protokoll.md) |
| Architektur und bekannte Grenzen verstehen | [Entwicklung](entwicklung.md) |

## Der Ablauf

**Ausgangstext → KI-Vorschlag → eigene Prüfung → normaler Beitragseditor → Entwurf, Veröffentlichung oder Einreichung.**

Generierung und Vorschau speichern keinen Beitrag. Auch die Übernahme öffnet zunächst ein ungespeichertes Formular. Bilder ergänzt du dort; das Modul sendet sie nicht an den KI-Anbieter.

Die Administration wählt genau einen Abschlussweg: **Direkt veröffentlichen** oder **Zur Prüfung einreichen**. **Als Entwurf speichern** bleibt möglich. Die normalen Drupal-Rechte und Pflichtfelder gelten in beiden Fällen. Aktivierte Benachrichtigungen folgen dem gewählten Abschluss; Entwürfe verschicken keine Mail.

## Die Grundausstattung

Neue Installationen enthalten **Standardartikel**, **Veranstaltung** sowie **Presse und Medien**. Jede Gruppe hat ein änderbares Erkennungszeichen. Die zusätzliche KI-Gestaltung ist zunächst ausgeschaltet; ihr neutraler Symbolpool enthält Information und Besonderes. Bestehende Installationen behalten ihre eigenen Gruppen und Vorgaben.

Redaktion, Protokolllesen, Protokolllöschen und Einstellungen verwenden getrennte Drupal-Rechte. Das Leserecht wird bei neuer KI-Freigabe als anpassbarer Standard ergänzt. Es umfasst alle protokollierten Vorgänge, nicht nur eigene.

## Dokumentation und Bilder

Die Anleitung und Abbildungen der Bedienoberfläche liegen unter `docs/`.

Die Themen orientieren sich am Original-Wiki, die beschriebenen Abläufe am Drupal-Modul. Das [GitHub-Wiki](https://github.com/jan-erbert/ai-beitragseinreichung-drupal/wiki) erhält dieselben Inhalte aus `docs/`. Siehe [Dokumentation und Backstage](backstage.md) sowie [Quellen und Abgrenzung](quellen.md).
