<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Service;

/** Liest und schreibt begrenzte, mit dem WP-Plugin kompatible Stilgruppen. */
final class StyleTransfer {

  /** Prüft eine Importdatei vollständig, bevor sie zur Übernahme angeboten wird. */
  public static function decode(string $json, array $existing = []): array {
    if (strlen($json) > 512000) {
      throw new \InvalidArgumentException('Die Importdatei darf höchstens 500 KB groß sein.');
    }
    $data = json_decode($json, TRUE, 16, JSON_THROW_ON_ERROR);
    if (!is_array($data) || ($data['version'] ?? NULL) !== 1 || !in_array($data['type'] ?? '', ['ai-beitragseinreichung-style-group', 'ai-beitragseinreichung-style-groups'], TRUE)) {
      throw new \InvalidArgumentException('Kein unterstützter Stilgruppen-Export (Version 1).');
    }
    $rows = $data['type'] === 'ai-beitragseinreichung-style-group' ? [$data['data'] ?? NULL] : ($data['data'] ?? NULL);
    if (!is_array($rows) || !array_is_list($rows) || !$rows || count($rows) > 50 || count($rows) + count($existing) > 50) {
      throw new \InvalidArgumentException('Bitte eine bis höchstens 50 Stilgruppen importieren; insgesamt maximal 50.');
    }
    $result = [];
    $ids = array_column($existing, 'id');
    $labels = array_map('mb_strtolower', array_column($existing, 'label'));
    foreach ($rows as $row) {
      if (!is_array($row)) {
        throw new \InvalidArgumentException('Eine Stilgruppe ist ungültig.');
      }
      foreach (['label', 'stil'] as $key) {
        if (!isset($row[$key]) || !is_string($row[$key]) || !trim($row[$key])) {
          throw new \InvalidArgumentException('Jede Stilgruppe benötigt Name und Stilbeschreibung.');
        }
      }
      $goal = $row['ziel'] ?? '';
      if (!is_string($goal)) {
        throw new \InvalidArgumentException('Das Ziel muss ein Text sein.');
      }
      $label = trim($row['label']);
      $instructions = trim(str_replace(["\r\n", "\r"], "\n", $row['stil']));
      if (trim($goal)) {
        $instructions .= "\nZiel: " . trim($goal);
      }
      if (mb_strlen($label) > 100 || mb_strlen($instructions) > 10000 || preg_match('/(?![\t\n\r])\p{C}/u', $label . $instructions)) {
        throw new \InvalidArgumentException('Name höchstens 100, Stil höchstens 10.000 Zeichen; keine unsichtbaren Steuerzeichen.');
      }
      $base = $label;
      for ($n = 2; in_array(mb_strtolower($label), $labels, TRUE); $n++) {
        $label = mb_substr($base, 0, 90) . ' (' . $n . ')';
      }
      $labels[] = mb_strtolower($label);
      $id = 'import_' . substr(hash('sha256', $label . $instructions), 0, 12);
      while (in_array($id, $ids, TRUE)) {
        $id .= '_2';
      }
      $ids[] = $id;
      $visual = $row['drupal_visual'] ?? [];
      if (!is_array($visual) || !is_string($visual['visual_instructions'] ?? '') || mb_strlen($visual['visual_instructions'] ?? '') > 4000 || !is_array($visual['visual_symbols'] ?? []) || count($visual['visual_symbols'] ?? []) > 80) { throw new \InvalidArgumentException('Ungültige Drupal-Gestaltungsvorgaben.'); }
      $icon = $visual['icon'] ?? 'file-text';
      if (!is_string($icon) || !IconCatalog::has($icon)) { throw new \InvalidArgumentException('Unbekanntes Symbol der Stilgruppe.'); }
      $symbols = $visual['visual_symbols'] ?? [];
      if (!is_array($symbols) || !array_is_list($symbols) || array_filter($symbols, static fn($id): bool => !is_string($id) || !preg_match('/^[a-z][a-z0-9_-]{0,63}$/D', $id))) { throw new \InvalidArgumentException('Ungültige Symbolkennungen.'); }
      $result[] = ['id' => $id, 'label' => $label, 'instructions' => $instructions, 'icon' => $icon, 'visual_enabled' => !empty($visual['visual_enabled']), 'visual_instructions' => $visual['visual_instructions'] ?? '', 'visual_symbols' => $symbols, 'visual_lead' => !empty($visual['visual_lead']), 'visual_table' => !empty($visual['visual_table'])];
    }
    return $result;
  }

  /** Exportiert gespeicherte Gruppen ohne Anbieterzugänge im ursprünglichen JSON-Format. */
  public static function encode(array $styles, bool $single = FALSE): string {
    $rows = array_map(static fn(array $style): array => ['label' => $style['label'], 'stil' => $style['instructions'], 'ziel' => '', 'drupal_visual' => array_intersect_key($style, array_flip(['icon', 'visual_enabled', 'visual_instructions', 'visual_symbols', 'visual_lead', 'visual_table']))], $styles);
    return json_encode(['type' => $single ? 'ai-beitragseinreichung-style-group' : 'ai-beitragseinreichung-style-groups', 'version' => 1, 'data' => $single ? $rows[0] : $rows], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }

}
