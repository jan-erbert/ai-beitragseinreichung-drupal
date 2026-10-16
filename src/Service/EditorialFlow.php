<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** Verbindet die gewählte Abschlussaktion mit nativen Drupal-Übergangsrechten. */
final class EditorialFlow {

  /** Bestehende Installationen behalten ohne bewusste Auswahl den Prüfweg. */
  public static function isDirect(): bool {
    return \Drupal::config('je_ai_submission.settings')->get('completion_action') === 'publish';
  }

  /** Liefert ausschließlich einen nativ erlaubten veröffentlichten Zielzustand. */
  public static function publishedState(NodeInterface $node, AccountInterface $account): ?string {
    if (!\Drupal::service('content_moderation.moderation_information')->getWorkflowForEntity($node)) { return NULL; }
    $allowed = [];
    foreach (\Drupal::service('content_moderation.state_transition_validation')->getValidTransitions($node, $account) as $transition) {
      if ($transition->to()->isPublishedState()) {
        $allowed[] = $transition->to()->id();
      }
    }
    return in_array('published', $allowed, TRUE) ? 'published' : ($allowed[0] ?? NULL);
  }

  /** Bietet genau den gewählten Abschlussweg zusätzlich zum Entwurf an. */
  public static function alter(array &$form, FormStateInterface $form_state): void {
    $node = $form_state->getFormObject()->getEntity();
    if (!isset($form['actions']['submit'])) { return; }
    $form['moderation_state']['#access'] = FALSE;
    $form['status']['#access'] = FALSE;
    $form['#entity_builders'][] = [self::class, 'buildEntity'];
    $form['actions']['submit']['#value'] = t('Als Entwurf speichern');
    $form['actions']['submit']['#je_ai_action'] = 'draft';
    if (!self::isDirect()) { return; }
    $state = self::publishedState($node, \Drupal::currentUser());
    if ($state === NULL) { return; }
    $form['actions']['je_ai_publish'] = $form['actions']['submit'];
    $form['actions']['je_ai_publish']['#name'] = 'je_ai_publish';
    $form['actions']['je_ai_publish']['#value'] = t('Veröffentlichen');
    $form['actions']['je_ai_publish']['#je_ai_action'] = 'publish';
    $form['actions']['je_ai_publish']['#button_type'] = 'primary';
    $form['actions']['je_ai_publish']['#weight'] = -10;
    $form['actions']['je_ai_publish']['#attributes']['data-je-ai-publish'] = '1';
    $form['actions']['je_ai_publish']['#submit'][] = [SubmissionNotification::class, 'afterPublication'];
    unset($form['actions']['submit']['#button_type']);
  }

  /** Setzt den Zielzustand vor Drupals vollständiger Inhaltsvalidierung. */
  public static function buildEntity(string $entity_type, NodeInterface $node, array &$form, FormStateInterface $form_state): void {
    $action = $form_state->getTriggeringElement()['#je_ai_action'] ?? 'draft';
    if ($action === 'publish') {
      $state = self::isDirect() ? self::publishedState($node, \Drupal::currentUser()) : NULL;
      if ($state === NULL) { throw new AccessDeniedHttpException('Dieser Veröffentlichungsweg ist nicht freigegeben.'); }
      $node->set('moderation_state', $state);
      $node->setPublished();
    }
    else {
      $node->set('moderation_state', 'draft');
      $node->setUnpublished();
    }
  }
}
