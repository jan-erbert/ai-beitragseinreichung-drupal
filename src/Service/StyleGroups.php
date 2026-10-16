<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Service;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Markup;

/** Gemeinsame Symbole für die Auswahl einer Beitragsart, unabhängig vom Textstil. */
final class StyleGroups {

  /** Liefert eine kleine, erweiterbare Auswahl ohne vollständige Katalogübertragung. */
  public static function icons(array $pool, array $styles): array {
    $icons = ['file-text' => 'Standardartikel', 'calendar-days' => 'Veranstaltung', 'newspaper' => 'Presse und Medien', 'trophy' => 'Wettkampf', 'users' => 'Gemeinschaft', 'plane' => 'Reisen', 'map-pin' => 'Ort', 'camera' => 'Fotos', 'megaphone' => 'Ankündigung', 'book-open' => 'Wissen', 'graduation-cap' => 'Nachwuchs', 'handshake' => 'Partnerschaft', 'info' => 'Information', 'sparkles' => 'Besonderes'];
    foreach (array_merge($pool, $styles) as $item) {
      $icon = $item['icon'] ?? '';
      if (IconCatalog::has($icon)) { $icons[$icon] ??= $item['label']; }
    }
    return $icons;
  }

  /** Rendert ausschließlich den lokalen Katalog und maskierten Bezeichnungstext. */
  public static function label(string $label, string $icon): Markup {
    $image = IconCatalog::preview($icon);
    return Markup::create('<span class="je-ai-group-label">' . ($image ? '<img src="' . Html::escape($image) . '" width="24" height="24" alt="">' : '') . '<span>' . Html::escape($label) . '</span></span>');
  }

  /** Ergänzt progressive Auswahlgrafiken; das native Select bleibt der Formularwert. */
  public static function attach(array &$form, array $icons): void {
    $form['#attached']['library'][] = 'je_ai_submission/style_choices';
    foreach ($icons as $name) {
      if (IconCatalog::has($name)) { $form['#attached']['drupalSettings']['jeAi']['choiceIcons'][$name] = IconCatalog::preview($name); }
    }
  }

  /** Löst frühere Website-Kennungen nur auf tatsächlich vorhandene Gruppen auf. */
  public static function resolve(string $id): string {
    $styles = array_column(\Drupal::config('je_ai_submission.settings')->get('styles') ?? [], NULL, 'id');
    if (isset($styles[$id])) { return $id; }
    $alias = \Drupal::state()->get('je_ai_submission.style_aliases', [])[$id] ?? '';
    return isset($styles[$alias]) ? $alias : $id;
  }
}
