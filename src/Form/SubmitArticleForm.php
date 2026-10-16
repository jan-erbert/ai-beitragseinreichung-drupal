<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/** Bestätigt das Einreichen ausschließlich des bereits gespeicherten Beitrags. */
final class SubmitArticleForm extends ConfirmFormBase {

  private int $nid;

  /** {@inheritdoc} */
  public function getFormId(): string { return 'je_ai_submission_submit_article'; }

  /** {@inheritdoc} */
  public function getQuestion() { return $this->t('Diesen Beitrag zur Prüfung einreichen?'); }

  /** {@inheritdoc} */
  public function getCancelUrl(): Url { return Url::fromRoute('entity.node.edit_form', ['node' => $this->nid]); }

  /** {@inheritdoc} */
  public function getConfirmText() { return $this->t('Zur Prüfung einreichen und benachrichtigen'); }

  /** Zeigt gespeicherte Daten; ungespeicherte Editoränderungen werden nicht versandt. */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL): array {
    $this->nid = (int) $node->id();
    $service = \Drupal::service('je_ai_submission.submissions');
    $latest = $service->latest($this->nid);
    $entry = $service->get($this->nid);
    $form = parent::buildForm($form, $form_state);
    $form['description']['#markup'] = $this->t('Versendet wird der zuletzt gespeicherte Stand. Bitte Änderungen im Editor vorher als Entwurf speichern. Der Beitrag bleibt unveröffentlicht.');
    $form['revision'] = ['#type' => 'value', '#value' => (int) $latest->getRevisionId()];
    $form['title'] = ['#type' => 'item', '#title' => $this->t('Beitrag'), '#plain_text' => $latest->label(), '#weight' => -10];
    $form['#cache']['max-age'] = 0;
    if (!empty($entry['submitted'])) {
      $statuses = array_count_values(array_column($entry['deliveries'], 'status'));
      $form['description']['#markup'] = $this->t('Bereits eingereicht. Erfolgreiche Zustellungen: @sent. Fehlgeschlagen: @failed. Unklarer Versandstatus: @unknown. Wegen geändertem oder gesperrtem Empfängerkonto übersprungen: @skipped. Erfolgreiche Zustellungen werden nicht wiederholt. Unklare Fälle bitte durch die Administration anhand der Mailserver-Protokolle prüfen lassen.', ['@sent' => $statuses['sent'] ?? 0, '@failed' => $statuses['failed'] ?? 0, '@unknown' => $statuses['sending'] ?? 0, '@skipped' => $statuses['skipped'] ?? 0]);
      $form['actions']['submit']['#value'] = $this->t('Nur fehlgeschlagene Zustellungen erneut versuchen');
      $form['actions']['submit']['#access'] = !empty($statuses['failed']) || !empty($statuses['pending']);
    }
    return $form;
  }

  /** Startet die geschützte Einreichung und meldet den tatsächlichen Versandstatus. */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    try {
      $entry = \Drupal::service('je_ai_submission.submissions')->submit($this->nid, (int) $form_state->getValue('revision'));
      $statuses = array_count_values(array_column($entry['deliveries'], 'status'));
      if (!empty($statuses['failed']) || !empty($statuses['sending']) || !empty($statuses['skipped'])) {
        $this->messenger()->addWarning($this->t('Der Beitrag ist zur Prüfung eingereicht. Nicht alle Benachrichtigungen wurden bestätigt. Den Versandstatus kannst du über „Einreichung und Versandstatus“ prüfen.'));
      }
      else { $this->messenger()->addStatus($this->t('Der Beitrag ist zur Prüfung eingereicht. Die Benachrichtigung wurde an den Mailtransport übergeben.')); }
      $form_state->setRedirect('entity.node.edit_form', ['node' => $this->nid]);
    }
    catch (\DomainException $error) { $this->messenger()->addError($error->getMessage()); $form_state->setRebuild(); }
    catch (\Throwable $error) {
      \Drupal::logger('je_ai_submission')->error('Einreichung fehlgeschlagen. Klasse @class.', ['@class' => get_class($error)]);
      $this->messenger()->addError($this->t('Die Einreichung konnte nicht abgeschlossen werden. Bitte den gespeicherten Beitrags- und Versandstatus prüfen und die Administration informieren.'));
      $form_state->setRebuild();
    }
  }

}
