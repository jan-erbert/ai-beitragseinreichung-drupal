<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Service;

/** Ergänzt geprüfte Gestaltung, ohne Text oder Ergebnisse zu verändern. */
final class VisualPresentation {

  /** Prüft Form und Grenzen der optionalen KI-Gestaltungsauswahl. */
  public static function validate(mixed $rows): array {
    if (!is_array($rows) || !array_is_list($rows) || count($rows) > 80) { throw new \UnexpectedValueException('Ungültige Gestaltungsauswahl.'); }
    foreach ($rows as $row) {
      if (!is_array($row) || count($row) !== 4 || !is_string($row['style_id'] ?? NULL) || !preg_match('/^[a-z][a-z0-9_-]{0,63}$/D', $row['style_id']) || !in_array($row['position'] ?? '', ['heading', 'inline'], TRUE) || !is_string($row['target'] ?? NULL) || !trim($row['target']) || mb_strlen($row['target']) > 300 || strip_tags($row['target']) !== $row['target'] || !is_int($row['occurrence'] ?? NULL) || $row['occurrence'] < 1 || $row['occurrence'] > 20) {
        throw new \UnexpectedValueException('Ungültige Symbolzuordnung.');
      }
    }
    return $rows;
  }

  /** Verwendet ausschließlich erlaubte Symbole und eindeutig vorhandene Textstellen. */
  public static function apply(string $html, array $rows, array $definitions, array $layout): string {
    $document = new \DOMDocument();
    $previous = libxml_use_internal_errors(TRUE);
    try {
      $document->loadHTML('<!doctype html><html><head><meta charset="utf-8"></head><body><div id="je-visual-content">' . $html . '</div></body></html>', LIBXML_NONET);
      $xpath = new \DOMXPath($document);
      $root = $document->getElementById('je-visual-content');
      if (!empty($layout['lead'])) {
        $first = $xpath->query('./p', $root)->item(0);
        if ($first) { $first->setAttribute('class', $definitions['_lead']['class']); }
      }
      if (!empty($layout['results_table'])) {
        foreach ($xpath->query('.//table', $root) as $table) { $table->setAttribute('class', $definitions['_table']['class']); }
      }
      $applied = [];
      foreach (self::validate($rows) as $row) {
        $key = $row['style_id'] . ':' . $row['position'];
        $definition = $definitions[$key] ?? NULL;
        if (!$definition || isset($applied[$key . ':' . $row['target'] . ':' . $row['occurrence']])) { continue; }
        $target = $row['target'];
        $matches = [];
        if ($row['position'] === 'heading') {
          foreach ($xpath->query('.//h2', $root) as $heading) {
            if (trim($heading->textContent) === $target) { $matches[] = $heading; }
          }
          $element = $matches[$row['occurrence'] - 1] ?? NULL;
          if ($element && !$element->hasAttribute('class')) { $element->setAttribute('class', $definition['class']); }
        }
        else {
          foreach ($xpath->query('.//text()[not(ancestor::h1) and not(ancestor::h2) and not(ancestor::h3) and not(ancestor::h4) and not(ancestor::h5) and not(ancestor::h6) and not(ancestor::a)]', $root) as $text) {
            $offset = 0;
            while (($position = mb_strpos($text->textContent, $target, $offset)) !== FALSE) {
              $matches[] = [$text, $position];
              $offset = $position + mb_strlen($target);
            }
          }
          if (isset($matches[$row['occurrence'] - 1])) {
            [$text, $offset] = $matches[$row['occurrence'] - 1];
            if ($text->parentNode instanceof \DOMElement && $text->parentNode->tagName === 'strong' && $text->parentNode->hasAttribute('class')) { continue; }
            $fragment = $document->createDocumentFragment();
            $fragment->appendChild($document->createTextNode(mb_substr($text->textContent, 0, $offset)));
            $element = $document->createElement('strong');
            $element->setAttribute('class', $definition['class']);
            $element->appendChild($document->createTextNode($target));
            $fragment->appendChild($element);
            $fragment->appendChild($document->createTextNode(mb_substr($text->textContent, $offset + mb_strlen($target))));
            $text->parentNode->replaceChild($fragment, $text);
          }
        }
        $applied[$key . ':' . $target . ':' . $row['occurrence']] = TRUE;
      }
      $result = '';
      foreach ($root->childNodes as $child) { $result .= $document->saveHTML($child); }
      return $result;
    }
    finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
  }

}
