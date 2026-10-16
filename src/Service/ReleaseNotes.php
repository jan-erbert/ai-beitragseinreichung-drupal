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
      'intro' => ['#type' => 'item', '#plain_text' => 'Die Drupal-Anleitung ist jetzt als eigenes Wiki erreichbar. Passende Hilfe-Links führen direkt zu den benötigten Themen.'],
      'items' => ['#theme' => 'item_list', '#items' => [
        'Beitrag erstellen und Gute Beiträge schreiben sind am Ende des KI-Formulars verlinkt.',
        'Einstellungen erklärt öffnet die passende Anleitung direkt aus der Administration.',
        'Anleitung, Bilder und Navigation werden gemeinsam aus docs/ gepflegt und mit dem Drupal-Wiki abgeglichen.',
      ]],
      'wiki' => ['#type' => 'link', '#title' => 'Drupal-Anleitung im Wiki öffnen', '#url' => \Drupal\Core\Url::fromUri(self::WIKI_URL), '#attributes' => ['target' => '_blank', 'rel' => 'noopener noreferrer']],
      'note' => ['#type' => 'item', '#plain_text' => 'Die Drupal-Anleitung wird aus docs/ gepflegt und mit dem GitHub-Wiki synchronisiert.'],
    ];
  }
}
