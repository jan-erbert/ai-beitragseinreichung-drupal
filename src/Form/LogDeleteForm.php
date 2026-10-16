<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** Löscht einen einzelnen Protokolleintrag erst nach bestätigtem POST mit Lese- und Löschrecht. */
final class LogDeleteForm extends ConfirmFormBase {
  private int $logId;

  /** {@inheritdoc} */
  public function getFormId(): string { return 'je_ai_log_delete'; }

  /** {@inheritdoc} */
  public function getQuestion() { return $this->t('Diesen KI-Protokolleintrag löschen?'); }

  /** {@inheritdoc} */
  public function getDescription() { return $this->t('Gespeicherte Textvergleiche dieses Vorgangs werden entfernt. Der zugehörige Beitrag bleibt erhalten. Diese Aktion lässt sich nicht rückgängig machen.'); }

  /** {@inheritdoc} */
  public function getCancelUrl(): Url { return Url::fromRoute('je_ai_submission.log'); }

  /** {@inheritdoc} */
  public function getConfirmText() { return $this->t('Protokolleintrag löschen'); }

  /** Prüft die ID vor der Bestätigung, ohne Daten zu verändern. */
  public function buildForm(array $form, FormStateInterface $form_state, string $log = ''): array {
    $this->logId = (int) $log;
    if (!\Drupal::database()->select('je_ai_submission_log', 'l')->condition('id', $this->logId)->countQuery()->execute()->fetchField()) {
      throw new NotFoundHttpException();
    }
    return parent::buildForm($form, $form_state);
  }

  /** Entfernt ausschließlich den bestätigten Datensatz, niemals den Beitrag. */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    \Drupal::database()->delete('je_ai_submission_log')->condition('id', $this->logId)->execute();
    $this->messenger()->addStatus($this->t('Der KI-Protokolleintrag wurde gelöscht.'));
    $form_state->setRedirect('je_ai_submission.log');
  }
}
