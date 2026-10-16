<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Service;

use Drupal\Core\Render\Markup;

/** Verbindet den administrativen Symbolpool mit Stilgruppen und Drupal. */
final class VisualStyles {

  /** Liefert eine neutrale Grundausstattung ohne Website-Branding. */
  public static function defaults(): array {
    return [
      ['id' => 'information', 'icon' => 'info', 'label' => 'Information', 'description' => 'Sachliche Informationen und Hinweise.', 'manual' => TRUE, 'ai' => TRUE, 'inline' => TRUE, 'heading' => TRUE],
      ['id' => 'besonderes', 'icon' => 'sparkles', 'label' => 'Besonderes', 'description' => 'Besondere Aspekte, sparsam einsetzen.', 'manual' => TRUE, 'ai' => TRUE, 'inline' => TRUE, 'heading' => TRUE],
    ];
  }

  /** Prüft die begrenzte Konfiguration vor ihrer Speicherung. */
  public static function normalize(array $rows): array {
    if (count($rows) > 81) { throw new \InvalidArgumentException('Höchstens 80 Symbole konfigurieren.'); }
    $result = [];
    foreach ($rows as $row) {
      $clean = [];
      foreach (['id', 'icon', 'label', 'description'] as $key) { $clean[$key] = trim((string) ($row[$key] ?? '')); }
      if (!array_filter($clean)) { continue; }
      if (!$clean['id']) { $clean['id'] = $clean['icon']; }
      if (!preg_match('/^[a-z][a-z0-9_-]{0,63}$/D', $clean['id']) || isset($result[$clean['id']]) || !IconCatalog::has($clean['icon']) || !$clean['label'] || mb_strlen($clean['label']) > 100 || mb_strlen($clean['description']) > 1000 || strip_tags($clean['label'] . $clean['description']) !== $clean['label'] . $clean['description']) { throw new \InvalidArgumentException('Eindeutige Kennung, gültigen Lucide-Namen und Bezeichnung ohne HTML angeben.'); }
      foreach (['manual', 'ai', 'inline', 'heading'] as $key) { $clean[$key] = !empty($row[$key]); }
      if (!$clean['inline'] && !$clean['heading']) { throw new \InvalidArgumentException('Mindestens Text oder Überschrift als Verwendung erlauben.'); }
      $result[$clean['id']] = $clean;
    }
    if (count($result) > 80) { throw new \InvalidArgumentException('Höchstens 80 Symbole konfigurieren.'); }
    return array_values($result);
  }

  /** Erlaubt einer Website ausschließlich die Zuordnung eigener Gestaltungsklassen. */
  public static function definitions(?array $pool = NULL): array {
    $pool ??= \Drupal::config('je_ai_submission.settings')->get('visual_pool') ?? [];
    $definitions = ['_lead' => ['element' => 'p', 'class' => 'je-ai-lead', 'label' => 'Einleitung'], '_table' => ['element' => 'table', 'class' => 'je-ai-results', 'label' => 'Ergebnisübersicht']];
    foreach ($pool as $item) {
      if (!IconCatalog::has($item['icon'])) { continue; }
      foreach (['inline' => 'strong', 'heading' => 'h2'] as $position => $tag) {
        if (!empty($item[$position])) {
          $definitions[$item['id'] . ':' . $position] = ['element' => $tag, 'class' => 'je-ai-' . $position . '-' . $item['id'], 'label' => ($position === 'heading' ? 'Abschnitt: ' : '') . $item['label'], 'icon' => $item['icon'], 'manual' => !empty($item['manual'])];
        }
      }
    }
    \Drupal::moduleHandler()->alter('je_ai_submission_visual_styles', $definitions);
    foreach ($definitions as $key => $definition) {
      if (!in_array($definition['element'] ?? '', ['p', 'table', 'strong', 'h2'], TRUE) || !preg_match('/^[a-z][a-z0-9_-]{0,100}$/D', $definition['class'] ?? '')) { unset($definitions[$key]); }
    }
    return $definitions;
  }

  /** Begrenzt die KI-Auswahl zusätzlich auf die gewählte Stilgruppe. */
  public static function context(array $style): array {
    $settings = \Drupal::config('je_ai_submission.settings');
    if (!$settings->get('visual_enabled') || empty($style['visual_enabled'])) { return []; }
    $allowed = $style['visual_symbols'] ?? [];
    $pool = array_values(array_filter($settings->get('visual_pool') ?? [], static fn($item): bool => !empty($item['ai']) && in_array($item['id'], $allowed, TRUE)));
    return ['symbols' => $pool, 'instructions' => $style['visual_instructions'] ?? '', 'lead' => !empty($style['visual_lead']), 'results_table' => !empty($style['visual_table'])];
  }

  /** Rendert Vorschau und Editorübernahme mit derselben aktuellen Freigabe. */
  public static function render(array $suggestion): string {
    $html = DraftTransfer::toHtml($suggestion['content']);
    $styles = array_column(\Drupal::config('je_ai_submission.settings')->get('styles') ?? [], NULL, 'id');
    $context = self::context($styles[StyleGroups::resolve($suggestion['visual_style'] ?? '')] ?? []);
    if (!$context) { return $html; }
    $definitions = self::definitions($context['symbols']);
    return VisualPresentation::apply($html, $suggestion['presentation'] ?? [], $definitions, $context);
  }

  /** Ergänzt feste, gefilterte native Editorstile ohne fremde Einträge zu entfernen. */
  public static function syncEditor(): void {
    $format_id = \Drupal::config('je_ai_submission.settings')->get('text_format');
    $editor = \Drupal\editor\Entity\Editor::load($format_id);
    $format = \Drupal\filter\Entity\FilterFormat::load($format_id);
    if (!$editor || $editor->getEditor() !== 'ckeditor5' || !$format || !$format->filters()->has('filter_html')) { return; }
    $definitions = self::definitions();
    $desired = [];
    foreach ($definitions as $key => $definition) {
      if (str_starts_with($key, '_') || !empty($definition['manual'])) { $desired[] = ['label' => $definition['label'], 'element' => '<' . $definition['element'] . ' class="' . $definition['class'] . '">']; }
    }
    $settings = $editor->getSettings();
    $previous = \Drupal::state()->get('je_ai_submission.editor_styles.' . $format_id, []);
    $elements = array_merge($previous, array_column($desired, 'element'));
    $keep = array_filter($settings['plugins']['ckeditor5_style']['styles'] ?? [], static fn($row): bool => !in_array($row['element'], $elements, TRUE));
    $settings['plugins']['ckeditor5_style']['styles'] = array_values(array_merge($keep, $desired));
    if (!in_array('style', $settings['toolbar']['items'], TRUE)) { $settings['toolbar']['items'][] = 'style'; }
    $extra = [];
    foreach ($definitions as $key => $definition) {
      if (!str_starts_with($key, '_') && empty($definition['manual'])) { $extra[] = '<' . $definition['element'] . ' class="' . $definition['class'] . '">'; }
    }
    $settings['plugins']['je_ai_submission_visual_preservation'] = ['enabled' => (bool) $extra, 'elements' => implode(' ', $extra)];
    $editor->setSettings($settings)->save();
    $filter = $format->filters('filter_html')->getConfiguration();
    $allowed = \Drupal\ckeditor5\HTMLRestrictions::fromString($filter['settings']['allowed_html']);
    $all = implode(' ', array_map(static fn($d): string => '<' . $d['element'] . ' class="' . $d['class'] . '">', $definitions));
    $filter['settings']['allowed_html'] = $allowed->merge(\Drupal\ckeditor5\HTMLRestrictions::fromString($all))->toFilterHtmlAllowedTagsString();
    $format->setFilterConfig('filter_html', $filter)->save();
    \Drupal::state()->set('je_ai_submission.editor_styles.' . $format_id, array_column($desired, 'element'));
  }

  /** Liefert nur feste lokale Masken für freigegebene Gestaltungsklassen. */
  public static function css(): string {
    $css = '';
    foreach (self::definitions() as $definition) {
      if (empty($definition['icon']) || !str_starts_with($definition['class'], 'je-ai-')) { continue; }
      $css .= '.' . $definition['class'] . '{--je-ai-icon:url("' . IconCatalog::preview($definition['icon']) . '")}';
    }
    return $css;
  }

  /** Bettet moduleigene Symbole als PNG ein; Website-Klassen behandelt deren Adapter. */
  public static function mail(array &$message): void {
    $content = (string) ($message['params']['submission']['content'] ?? '');
    if (!str_contains($content, 'je-ai-')) { return; }
    $definitions = self::definitions();
    $mapping = [];
    foreach ($definitions as $definition) { if (isset($definition['icon']) && str_starts_with($definition['class'], 'je-ai-')) { $mapping[$definition['class']] = $definition['icon']; } }
    $used = [];
    $document = new \DOMDocument();
    $previous = libxml_use_internal_errors(TRUE);
    try {
      $document->loadHTML('<!doctype html><html><head><meta charset="utf-8"></head><body><div id="je-symbol-mail">' . $content . '</div></body></html>', LIBXML_NONET);
      $xpath = new \DOMXPath($document);
      foreach ($xpath->query('//*[@class]') as $element) {
        foreach (preg_split('/\s+/', $element->getAttribute('class')) as $class) {
          if (!isset($mapping[$class])) { continue; }
          $icon = $mapping[$class];
          $image = $document->createElement('img');
          foreach (['src' => 'cid:je-ai-symbol-' . $icon, 'width' => '18', 'height' => '18', 'alt' => '', 'style' => 'display:inline-block;vertical-align:middle;margin-right:6px;width:18px;height:18px;'] as $name => $value) { $image->setAttribute($name, $value); }
          $element->insertBefore($image, $element->firstChild);
          $used[$icon] = TRUE;
          break;
        }
      }
      foreach ($xpath->query('//*[@class]') as $element) {
        $classes = preg_split('/\s+/', $element->getAttribute('class'));
        if (in_array('je-ai-lead', $classes, TRUE)) { $element->setAttribute('style', 'font-size:18px;line-height:1.65;'); }
        if (in_array('je-ai-results', $classes, TRUE)) {
          $element->setAttribute('style', 'width:100%;border-collapse:collapse;font-size:14px;margin:18px 0;');
          foreach ($element->getElementsByTagName('th') as $cell) { $cell->setAttribute('style', 'padding:8px;border-bottom:1px solid #dce3e7;text-align:left;background:#f3f6f7;'); }
          foreach ($element->getElementsByTagName('td') as $cell) { $cell->setAttribute('style', 'padding:8px;border-bottom:1px solid #dce3e7;text-align:left;'); }
        }
      }
      $content = '';
      foreach ($document->getElementById('je-symbol-mail')->childNodes as $child) { $content .= $document->saveHTML($child); }
    }
    finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
    $folder = 'temporary://je-ai-symbols';
    \Drupal::service('file_system')->prepareDirectory($folder, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);
    foreach (array_keys($used) as $icon) {
      $uri = $folder . '/' . $icon . '.png';
      if (!is_file($uri)) { \Drupal::service('file_system')->saveData(base64_decode(IconCatalog::all()[$icon]['png'], TRUE), $uri, \Drupal\Core\File\FileExists::Replace); }
      $message['params']['images'][] = ['uri' => $uri, 'filename' => $icon . '.png', 'filemime' => 'image/png', 'cid' => 'je-ai-symbol-' . $icon];
    }
    $message['params']['submission']['content'] = Markup::create($content);
  }

}
