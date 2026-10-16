<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Service;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Render\Markup;
use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;

/** Bereitet ausschließlich lokale öffentliche Drupal-Bilder für Mail-CIDs vor. */
final class MailImages {
  private array $images = [];
  private int $bytes = 0;
  private bool $limited = FALSE;

  /** Ersetzt Inline-Bilder und ergänzt Bildfelder als statische Mailvorschau. */
  public function build(NodeInterface $node, string $html): array {
    $this->images = [];
    $this->bytes = 0;
    $this->limited = FALSE;
    $dom = \Drupal\Component\Utility\Html::load($html);
    foreach (iterator_to_array($dom->getElementsByTagName('img')) as $image) {
      $file = $this->fromSource($image->getAttribute('src'), $image->getAttribute('data-entity-uuid'));
      $prepared = $file ? $this->prepare($file, $image->getAttribute('alt')) : NULL;
      if (!$prepared) { $image->parentNode->removeChild($image); continue; }
      $replacement = $dom->createElement('img');
      foreach (['src' => 'cid:' . $prepared['cid'], 'alt' => $prepared['alt'], 'width' => (string) min(552, $prepared['width']), 'style' => 'display:block;width:100%;max-width:552px;height:auto;border:0;border-radius:6px;margin:16px 0;'] as $key => $value) { $replacement->setAttribute($key, $value); }
      $image->parentNode->replaceChild($replacement, $image);
    }
    $featured = NULL;
    $gallery = [];
    foreach ($node->getFieldDefinitions() as $name => $definition) {
      $items = $node->get($name);
      if ($items->isEmpty()) { continue; }
      $files = [];
      if ($definition->getType() === 'image') {
        foreach ($items as $item) { if ($item->entity) { $files[] = [$item->entity, $item->alt ?? '']; } }
      }
      elseif ($definition->getType() === 'entity_reference' && $definition->getSetting('target_type') === 'media') {
        foreach ($items->referencedEntities() as $media) {
          if (!$media->access('view')) { continue; }
          $source = $media->getSource()->getConfiguration()['source_field'] ?? '';
          if (!$source || !$media->hasField($source) || $media->get($source)->getFieldDefinition()->getType() !== 'image') { continue; }
          foreach ($media->get($source) as $item) { if ($item->entity) { $files[] = [$item->entity, $item->alt ?? $media->label()]; } }
        }
      }
      foreach ($files as [$file, $alt]) {
        $known = isset($this->images[$file->uuid()]);
        $prepared = $this->prepare($file, $alt);
        if (!$prepared || $known) { continue; }
        if (!$featured && $definition->getFieldStorageDefinition()->getCardinality() === 1) { $featured = $prepared; }
        else { $gallery[] = $prepared; }
      }
    }
    return ['content' => Markup::create(\Drupal\Component\Utility\Html::serialize($dom)), 'featured' => $featured, 'gallery' => $gallery, 'images' => array_values($this->images), 'limited' => $this->limited];
  }

  /** Ordnet UUIDs oder lokale Datei-URLs verwalteten öffentlichen Dateien zu. */
  private function fromSource(string $src, string $uuid): ?FileInterface {
    if ($uuid && \Drupal\Component\Uuid\Uuid::isValid($uuid)) {
      $file = \Drupal::service('entity.repository')->loadEntityByUuid('file', $uuid);
      if ($file instanceof FileInterface) { return $file; }
    }
    $base = parse_url(\Drupal::service('file_url_generator')->generateAbsoluteString('public://'));
    $url = parse_url(html_entity_decode($src, ENT_QUOTES | ENT_HTML5));
    if (!$url || isset($url['host']) && (strcasecmp($url['host'], $base['host'] ?? '') !== 0 || ($url['port'] ?? NULL) !== ($base['port'] ?? NULL))) { return NULL; }
    if (isset($url['scheme']) && !in_array(strtolower($url['scheme']), ['http', 'https'], TRUE)) { return NULL; }
    $prefix = rtrim($base['path'] ?? '', '/') . '/';
    $path = rawurldecode($url['path'] ?? '');
    if (!str_starts_with($path, $prefix)) { return NULL; }
    $relative = substr($path, strlen($prefix));
    $relative = preg_replace('@^styles/[^/]+/public/@', '', $relative);
    if (!$relative || str_contains($relative, "\0") || str_contains($relative, '\\') || in_array('..', explode('/', $relative), TRUE)) { return NULL; }
    $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => 'public://' . $relative]);
    return $files ? reset($files) : NULL;
  }

  /** Verkleinert mit Drupals Bildwerkzeug und begrenzt Menge und Mailgröße. */
  private function prepare(FileInterface $file, string $alt): ?array {
    if (isset($this->images[$file->uuid()])) { return $this->images[$file->uuid()]; }
    if (count($this->images) >= 12) { $this->limited = TRUE; return NULL; }
    $uri = $file->getFileUri();
    if (!str_starts_with($uri, 'public://') || !$file->access('download')) { return NULL; }
    $filesystem = \Drupal::service('file_system');
    $path = $filesystem->realpath($uri);
    $root = $filesystem->realpath('public://');
    if (!$path || !$root || !str_starts_with(realpath($path) ?: '', rtrim(realpath($root) ?: '', '/') . '/') || !is_file($path)) { return NULL; }
    $dimensions = @getimagesize($path);
    if (!$dimensions || !in_array($dimensions['mime'] ?? '', ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], TRUE) || $dimensions[0] * $dimensions[1] > 16000000 || filesize($path) > 20971520) { $this->limited = TRUE; return NULL; }
    $extension = $dimensions['mime'] === 'image/png' ? 'png' : 'jpg';
    $folder = 'temporary://je-ai-mail';
    if (!$filesystem->prepareDirectory($folder, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) { return NULL; }
    $destination = $folder . '/' . hash('sha256', $file->uuid() . ':' . filemtime($path) . ':' . filesize($path)) . '.' . $extension;
    $target = $filesystem->realpath($destination);
    if (!$target || !is_file($target)) {
      $image = \Drupal::service('image.factory')->get($uri);
      if (!$image->isValid() || !$image->scale(1000, 1000) || !$image->convert($extension) || !$image->save($destination)) { return NULL; }
      $target = $filesystem->realpath($destination);
    }
    $size = $target ? filesize($target) : FALSE;
    if ($size === FALSE || $size > 1048576 || $this->bytes + $size > 5242880) { $this->limited = TRUE; return NULL; }
    $this->bytes += $size;
    $prepared_dimensions = @getimagesize($target);
    if (!$prepared_dimensions) { return NULL; }
    return $this->images[$file->uuid()] = ['uri' => $destination, 'filename' => 'beitragsbild-' . $file->id() . '.' . $extension, 'filemime' => $extension === 'png' ? 'image/png' : 'image/jpeg', 'cid' => 'je-ai-article-' . $file->uuid(), 'alt' => mb_substr(strip_tags($alt), 0, 300), 'width' => $prepared_dimensions[0]];
  }

}
