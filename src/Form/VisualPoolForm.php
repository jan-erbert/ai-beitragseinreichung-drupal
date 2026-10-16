<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\je_ai_submission\Service\IconCatalog;
use Drupal\je_ai_submission\Service\VisualStyles;

/** Verwaltet ausschließlich den lokalen, explizit freigegebenen Symbolvorrat. */
final class VisualPoolForm extends ConfigFormBase {

  /** {@inheritdoc} */
  public function getFormId(): string { return 'je_ai_submission_visual_pool'; }

  /** {@inheritdoc} */
  protected function getEditableConfigNames(): array { return ['je_ai_submission.settings']; }

  /** Zeigt Vorschauen und getrennte Freigaben ohne Anbieteranfrage. */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#attached']['library'][] = 'je_ai_submission/editorial';
    $form['intro'] = ['#type' => 'item', '#plain_text' => $this->t('Lokaler Lucide-Katalog 1.58.0. Symbolnamen wie bike oder search eingeben. Vor dem Speichern prüfen; Vorschau und Prüfung sind ohne KI-Aufruf möglich. Die KI nutzt nur global, im Pool und in der jeweiligen Stilgruppe freigegebene Einträge. Farben und Website-Gestaltung bleiben separat.')];
    $form['back'] = ['#type' => 'link', '#title' => $this->t('Zurück zu Einstellungen und Stilgruppen'), '#url' => Url::fromRoute('je_ai_submission.settings')];
    $form['rows'] = ['#tree' => TRUE];
    $rows = $form_state->getValue('rows') ?? $this->config('je_ai_submission.settings')->get('visual_pool') ?? [];
    $rows = array_values(array_filter($rows, static fn($row): bool => (bool) array_filter(array_intersect_key($row, array_flip(['id', 'icon', 'label', 'description'])))));
    if (count($rows) < 80) { $rows[] = ['id' => '', 'icon' => '', 'label' => '', 'description' => '', 'manual' => TRUE, 'ai' => FALSE, 'inline' => TRUE, 'heading' => TRUE]; }
    foreach ($rows as $index => $row) {
      $form['rows'][$index] = ['#type' => 'details', '#title' => $row['label'] ?: $this->t('Neues Symbol'), '#open' => !$row['label'] || $form_state->hasAnyErrors()];
      if (IconCatalog::has((string) $row['icon'])) {
        $form['rows'][$index]['preview'] = ['#type' => 'html_tag', '#tag' => 'img', '#attributes' => ['src' => IconCatalog::preview($row['icon']), 'alt' => '', 'width' => '32', 'height' => '32']];
      }
      $form['rows'][$index]['id'] = ['#type' => 'textfield', '#title' => $this->t('Feste Kennung'), '#maxlength' => 64, '#default_value' => $row['id'], '#description' => $this->t('Leer für Symbolnamen als Kennung. Später beibehalten, damit Stilgruppen und gespeicherte Texte zugeordnet bleiben.')];
      $form['rows'][$index]['icon'] = ['#type' => 'textfield', '#title' => $this->t('Lucide-Symbolname'), '#maxlength' => 80, '#default_value' => $row['icon'], '#description' => $this->t('Zum Beispiel search. Nur im lokalen Katalog vorhandene Namen, kein SVG-Code und keine URL.')];
      $form['rows'][$index]['label'] = ['#type' => 'textfield', '#title' => $this->t('Bezeichnung für Redaktion und KI'), '#maxlength' => 100, '#default_value' => $row['label']];
      $form['rows'][$index]['description'] = ['#type' => 'textarea', '#title' => $this->t('Wann passt dieses Symbol?'), '#maxlength' => 1000, '#rows' => 2, '#default_value' => $row['description']];
      foreach (['manual' => 'Im manuellen Editor verfügbar', 'ai' => 'Für KI-Auswahl freigeben', 'inline' => 'Im Text verwendbar', 'heading' => 'Für Zwischenüberschriften verwendbar'] as $key => $label) { $form['rows'][$index][$key] = ['#type' => 'checkbox', '#title' => $this->t($label), '#default_value' => !empty($row[$key])]; }
    }
    $form['check'] = ['#type' => 'submit', '#name' => 'visual_check', '#value' => $this->t('Symbolnamen und Vorschauen prüfen'), '#submit' => ['::checkPool']];
    return parent::buildForm($form, $form_state);
  }

  /** Prüft den gesamten Pool atomar, bevor Einstellungen geändert werden. */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
    try { $form_state->set('normalized_pool', VisualStyles::normalize($form_state->getValue('rows') ?? [])); }
    catch (\InvalidArgumentException $error) { $form_state->setErrorByName('rows', $error->getMessage()); }
  }

  /** Zeigt ausschließlich ungespeicherte, lokal geprüfte Vorschauen. */
  public function checkPool(array &$form, FormStateInterface $form_state): void {
    $this->messenger()->addStatus($this->t('Symbolnamen geprüft. Vorschauen aktualisiert; noch nicht gespeichert.'));
    $form_state->setRebuild();
  }

  /** Speichert den Pool und führt ausschließlich native Editorstile nach. */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('je_ai_submission.settings')->set('visual_pool', $form_state->get('normalized_pool'))->save();
    VisualStyles::syncEditor();
    parent::submitForm($form, $form_state);
  }

}
