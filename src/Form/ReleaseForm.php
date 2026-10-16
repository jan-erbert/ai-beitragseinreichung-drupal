<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\je_ai_submission\Access\SubmissionAccess;
use Drupal\je_ai_submission\Service\ReleaseNotes;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** Zeigt Neuerungen einmal je Version und Konto sowie auf Wunsch erneut. */
final class ReleaseForm extends FormBase {

  /** {@inheritdoc} */
  public function getFormId(): string { return 'je_ai_release_notice'; }

  /** Baut denselben Hinweis als modalen Ersthinweis oder normale Seite. */
  public function buildForm(array $form, FormStateInterface $form_state, bool $modal = FALSE): array {
    $form['#attached']['library'][] = 'je_ai_submission/enhancements';
    $form['#attached']['drupalSettings']['jeAi']['animations'] = base_path() . \Drupal::service('extension.list.module')->getPath('je_ai_submission') . '/assets/lottie/';
    $form['#cache'] = ['max-age' => 0];
    $form['#attributes']['class'][] = 'je-ai-release';
    if ($modal) {
      $form['#prefix'] = \Drupal\Core\Render\Markup::create('<dialog class="je-ai-release-dialog" aria-label="Was ist neu?" data-je-release>');
      $form['#suffix'] = \Drupal\Core\Render\Markup::create('</dialog>');
    }
    $form['content'] = ReleaseNotes::content();
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = ['#type' => 'submit', '#value' => $this->t('Verstanden'), '#button_type' => 'primary'];
    return $form;
  }

  /** Bestätigt ausschließlich für das angemeldete berechtigte Konto. */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if (!(new SubmissionAccess())->access($this->currentUser())->isAllowed()) {
      throw new AccessDeniedHttpException();
    }
    \Drupal::service('user.data')->set('je_ai_submission', $this->currentUser()->id(), 'release_seen', ReleaseNotes::VERSION);
    $form_state->setRedirect('je_ai_submission.prepare');
  }
}
