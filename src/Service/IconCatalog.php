<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Service;

/** Lokale, versionierte Lucide-Grafiken ohne externe Anfragen. */
final class IconCatalog {

  /** Lädt den festen Katalog höchstens einmal pro Anfrage. */
  public static function all(): array {
    static $catalog;
    return $catalog ??= json_decode(gzdecode(file_get_contents(dirname(__DIR__, 2) . '/assets/lucide/catalog.json.gz')), TRUE, 32, JSON_THROW_ON_ERROR);
  }

  /** Akzeptiert ausschließlich tatsächlich mitgelieferte Symbolnamen. */
  public static function has(string $name): bool {
    return preg_match('/^[a-z][a-z0-9-]{0,79}$/D', $name) && isset(self::all()[$name]);
  }

  /** Liefert eine feste SVG-Vorschau; niemals benutzerdefinierten SVG-Code. */
  public static function preview(string $name): string {
    return self::has($name) ? 'data:image/svg+xml;base64,' . base64_encode(self::all()[$name]['svg']) : '';
  }

}
