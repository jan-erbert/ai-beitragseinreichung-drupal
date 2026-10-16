<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Service;

/** Enthält die tatsächlichen Neuerungen dieser Drupal-Version. */
final class ReleaseNotes {
  public const VERSION = '1.0.1';
  public const WIKI_URL = 'https://github.com/jan-erbert/ai-beitragseinreichung-drupal/wiki';

  /** Baut den Versionshinweis mit der aktuellen Drupal-Anleitung. */
  public static function content(): array {
    return [
      'visual' => ['#type' => 'container', '#attributes' => ['class' => ['je-ai-motion'], 'data-je-lottie' => 'release-animation.json', 'aria-hidden' => 'true']],
      'version' => ['#type' => 'item', '#plain_text' => 'Version ' . self::VERSION],
      'heading' => ['#type' => 'html_tag', '#tag' => 'h2', '#value' => 'Was ist neu?'],
      'intro' => ['#type' => 'item', '#plain_text' => 'Erste stabile Version von JE KI-Beiträge für Drupal: KI-Vorschläge prüfen, im normalen Editor abschließen und Benachrichtigungen sowie Stilgruppen individuell einrichten.'],
      'items' => ['#theme' => 'item_list', '#items' => [
        'Benachrichtigungen nennen zusätzlich die gewählte Beitragsart. Ihre Bezeichnung bleibt unabhängig von der Protokoll-Aufbewahrung gespeichert.',
        'Wählbares Symbol je Beitragsart, mit Vorschau in Einstellungen und Beitragsauswahl. Interne Kennungen werden automatisch verwaltet.',
        'Drei neutrale Startgruppen für neue Installationen: Standardartikel, Veranstaltung sowie Presse und Medien. Vorhandene Gruppen bleiben erhalten.',
        'KI-Rollen erhalten Protokoll-Lesen als anpassbaren Standard. Löschen und Einstellungen bleiben getrennte Rechte.',
        'Übersichtlich gegliederte Einstellungen und lesbare Symbolkacheln mit Checkboxen statt Strg-Mehrfachauswahl.',
        'Konfigurierbarer Symbolpool mit lokal geprüften Lucide-Namen, Vorschauen und getrennten manuellen und KI-Freigaben.',
        'Erlaubte Symbole, Gestaltungsregeln, Einleitung und Ergebnistabellen je Stilgruppe festlegen.',
        'Text und Symbolzuordnungen werden getrennt geprüft. Vorschau und Editor nutzen dieselbe Darstellung; Website-Farben bleiben separat.',
        'Eigene Website-Stile und lokale PNG-Mailbilder werden unterstützt. Die Stilgruppenverwaltung und ihre Exporte erhalten die zusätzlichen Regeln.',
        'Generierung und Vorschau veröffentlichen weiterhin nichts. Native Beitragsrechte, Bildpflichten und Benachrichtigungen bleiben wirksam.',
      ]],
      'wiki' => ['#type' => 'link', '#title' => 'Drupal-Anleitung im Wiki öffnen', '#url' => \Drupal\Core\Url::fromUri(self::WIKI_URL), '#attributes' => ['target' => '_blank', 'rel' => 'noopener noreferrer']],
      'note' => ['#type' => 'item', '#plain_text' => 'Die Drupal-Anleitung wird aus docs/ gepflegt und mit dem GitHub-Wiki synchronisiert.'],
    ];
  }
}
