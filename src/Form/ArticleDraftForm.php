<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeForm;
use Drupal\je_ai_submission\Service\EditorialFlow;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** Speichert die geprüfte Übernahme über den gewählten nativen Abschlussweg. */
final class ArticleDraftForm extends NodeForm {

  /** Ergänzt die normale Oberfläche um den Hinweis zur ersten Speicherung. */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    if (!$form_state->get('je_ai_token')) {
      $form_state->set('je_ai_token', \Drupal::routeMatch()->getParameter('token'));
    }
    $form['je_ai_notice'] = ['#type' => 'item', '#markup' => EditorialFlow::isDirect() ? $this->t('Der KI-Vorschlag ist übernommen, aber noch nicht gespeichert. Prüfe den Text und ergänze Bilder. Du kannst veröffentlichen oder als Entwurf speichern.') : $this->t('Der KI-Vorschlag ist übernommen, aber noch nicht gespeichert. Prüfe den Text und ergänze Bilder. Speichere einen Entwurf und reiche ihn anschließend zur Prüfung ein.'), '#weight' => -100];
    $form['moderation_state']['#access'] = FALSE;
    return $form;
  }

  /** Beschriftet den regulären Speicherknopf eindeutig. */
  protected function actions(array $form, FormStateInterface $form_state): array {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Als Entwurf speichern');
    return $actions;
  }

  /** Verhindert doppelte Übernahmen und erhält alle normalen Drupal-Prüfungen. */
  public function save(array $form, FormStateInterface $form_state): void {
    $token = $form_state->get('je_ai_token');
    $drafts = \Drupal::service('je_ai_submission.drafts');
    $lock = \Drupal::lock();
    if (!$token || !$lock->acquire('je_ai_submission:' . $token, 30)) {
      throw new AccessDeniedHttpException('Diese Übernahme wird bereits gespeichert.');
    }
    try {
      $entry = $drafts->get($token);
      if (!$entry) {
        throw new AccessDeniedHttpException('Die Vorschau ist abgelaufen.');
      }
      if (isset($entry['saved_node'])) {
        $form_state->setRedirect('entity.node.edit_form', ['node' => $entry['saved_node']]);
        return;
      }
      EditorialFlow::buildEntity('node', $this->entity, $form, $form_state);
      parent::save($form, $form_state);
      $drafts->markSaved($token, (int) $this->entity->id());
      $form_state->setRedirect($this->entity->isPublished() ? 'entity.node.canonical' : 'entity.node.edit_form', ['node' => $this->entity->id()]);
      return;
    }
    finally {
      $lock->release('je_ai_submission:' . $token);
    }
  }

}
