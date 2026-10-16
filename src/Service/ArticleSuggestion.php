<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Service;

/** Prüft KI-Ausgaben vor Vorschau und Entwurfsübernahme. */
final class ArticleSuggestion {

  /** Beschreibt die erwarteten Antwortfelder für den KI-Anbieter. */
  public static function schema(bool $visual = FALSE): array {
    $schema = [
      'type' => 'object',
      'additionalProperties' => FALSE,
      'properties' => [
        'title' => ['type' => 'string'],
        'content' => ['type' => 'string'],
        'excerpt' => ['type' => 'string'],
        'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
      ],
      'required' => ['title', 'content', 'excerpt', 'tags'],
    ];
    if ($visual) {
      $schema['properties']['presentation'] = ['type' => 'array', 'maxItems' => 80, 'items' => ['type' => 'object', 'additionalProperties' => FALSE, 'properties' => ['style_id' => ['type' => 'string'], 'position' => ['type' => 'string', 'enum' => ['heading', 'inline']], 'target' => ['type' => 'string'], 'occurrence' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20]], 'required' => ['style_id', 'position', 'target', 'occurrence']]];
      $schema['required'][] = 'presentation';
    }
    return $schema;
  }

  /** Verwirft unvollständige Antworten statt Daten still zu ersetzen. */
  public static function parse(string $json): array {
    $data = json_decode($json, TRUE, 32, JSON_THROW_ON_ERROR);
    if (!is_array($data) || array_diff(['title', 'content', 'excerpt', 'tags'], array_keys($data)) || array_diff(array_keys($data), ['title', 'content', 'excerpt', 'tags', 'presentation', 'visual_style'])) {
      throw new \UnexpectedValueException('Die KI-Antwort enthält nicht die erwarteten Beitragsfelder.');
    }
    foreach (['title' => 120, 'content' => 80000, 'excerpt' => 1000] as $key => $limit) {
      if (!is_string($data[$key]) || mb_strlen($data[$key]) > $limit) {
        throw new \UnexpectedValueException('Die KI-Antwort enthält ein ungültiges Textfeld.');
      }
      $data[$key] = trim($data[$key]);
    }
    if ($data['title'] === '' || $data['content'] === '' || strip_tags($data['title']) !== $data['title'] || strip_tags($data['excerpt']) !== $data['excerpt']) {
      throw new \UnexpectedValueException('Titel oder Beitragstext fehlen oder enthalten unzulässige Formatierung.');
    }
    if (!is_array($data['tags']) || !array_is_list($data['tags']) || count($data['tags']) > 24) {
      throw new \UnexpectedValueException('Die KI-Schlagwörter sind ungültig.');
    }
    foreach ($data['tags'] as $tag) {
      if (!is_string($tag) || mb_strlen($tag) > 80 || strip_tags($tag) !== $tag) {
        throw new \UnexpectedValueException('Ein KI-Schlagwort ist ungültig.');
      }
    }
    if (isset($data['presentation'])) { $data['presentation'] = VisualPresentation::validate($data['presentation']); }
    if (isset($data['visual_style']) && (!is_string($data['visual_style']) || !preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $data['visual_style']))) { throw new \UnexpectedValueException('Ungültige Stilgruppenkennung.'); }
    $data['tags'] = self::mergeTags($data['tags']);
    return $data;
  }

  /** Führt Schreibweisen ohne doppelte Begriffe zusammen. */
  public static function mergeTags(array ...$groups): array {
    $result = [];
    foreach ($groups as $group) {
      foreach ($group as $tag) {
        $tag = trim((string) $tag);
        if ($tag !== '') {
          $key = mb_strtolower($tag);
          $result[$key] ??= $tag;
        }
      }
    }
    return array_values($result);
  }

}
