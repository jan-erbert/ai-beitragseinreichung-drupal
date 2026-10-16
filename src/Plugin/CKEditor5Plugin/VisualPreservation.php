<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginElementsSubsetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\EditorInterface;

/** Erhält KI-only-Klassen über die native General-HTML-Unterstützung. */
final class VisualPreservation extends CKEditor5PluginDefault implements CKEditor5PluginElementsSubsetInterface {
  use CKEditor5PluginConfigurableTrait;

  /** {@inheritdoc} */
  public function defaultConfiguration(): array { return ['enabled' => FALSE, 'elements' => '']; }

  /** {@inheritdoc} */
  public function getElementsSubset(): array {
    $elements = $this->configuration['elements'] ?? '';
    return is_array($elements) ? $elements : array_values(array_filter(preg_split('/(?<=>)\s*(?=<)/', trim($elements))));
  }

  /** {@inheritdoc} */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    return ['htmlSupport' => ['allow' => HTMLRestrictions::fromString(implode(' ', $this->getElementsSubset()))->toGeneralHtmlSupportConfig()]];
  }

  /** {@inheritdoc} */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    return ['notice' => ['#type' => 'item', '#plain_text' => $this->t('Die zusätzlichen Klassen werden aus dem Symbolpool von JE KI-Beiträge verwaltet. Sie bleiben beim Bearbeiten erhalten, ohne im manuellen Stildialog angeboten zu werden.')]];
  }

  /** {@inheritdoc} */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {}

  /** {@inheritdoc} */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {}
}
