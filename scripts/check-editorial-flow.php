<?php

use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\je_ai_submission\Service\EditorialFlow;
use Drupal\user\Entity\User;

/** Prüft erlaubte Abschlusswege ohne bleibende Beiträge oder Mailversand. */
function je_flow_check(bool $condition, string $message): void {
  if (!$condition) { throw new RuntimeException($message); }
}

$database = \Drupal::database();
$transaction = $database->startTransaction();
$settings = \Drupal::config('je_ai_submission.settings')->getRawData();
$switcher = \Drupal::service('account_switcher');
$switcher->switchTo(User::load(2));
try {
  \Drupal::configFactory()->getEditable('je_ai_submission.settings')->set('completion_action', 'publish')->save();
  $drafts = \Drupal::service('je_ai_submission.drafts');
  $submissions = \Drupal::service('je_ai_submission.submissions');
  $suggestion = ['title' => 'Interner Abschlussweg-Test', 'content' => 'Lokaler Test ohne KI-Aufruf oder Mailversand.', 'excerpt' => '', 'tags' => []];
  $token = $drafts->prepare($suggestion, time());
  $node = $drafts->buildNode($drafts->get($token));
  $object = \Drupal::entityTypeManager()->getFormObject('node', 'je_ai_submission')->setEntity($node);
  $state = (new FormState())->setFormObject($object);
  $state->set('je_ai_token', $token);
  $form = $object->buildForm([], $state);
  je_ai_submission_form_node_form_alter($form, $state);
  je_flow_check(isset($form['actions']['je_ai_publish']) && !isset($form['actions']['je_ai_submit']), 'Direkter Modus bietet nicht genau den Veröffentlichungsweg.');
  je_flow_check((string) $form['actions']['submit']['#value'] === 'Als Entwurf speichern', 'Entwurfsweg fehlt.');
  $button = $form['actions']['je_ai_publish'];
  $state->setTriggeringElement($button);
  EditorialFlow::buildEntity('node', $node, $form, $state);
  je_flow_check(count($node->validate()) > 0, 'Website-Bildpflicht wird beim Veröffentlichungsziel nicht geprüft.');
  je_flow_check(EditorialFlow::publishedState($node, new AnonymousUserSession()) === NULL, 'Veröffentlichungsrecht wird umgangen.');
  $media_ids = \Drupal::entityQuery('media')->condition('bundle', 'image')->condition('status', 1)->accessCheck(FALSE)->range(0, 1)->execute();
  je_flow_check((bool) $media_ids, 'Kein vorhandenes Prüf-Bild verfügbar.');
  $node->set('field_article_image', reset($media_ids));
  je_flow_check(count($node->validate()) === 0, 'Veröffentlichbarer Beitrag mit Bild ist ungültig.');
  $object->save($form, $state);
  $published = $submissions->latest((int) $node->id());
  je_flow_check($published->isPublished() && $published->get('moderation_state')->value === 'published', 'Direkte erste Veröffentlichung fehlgeschlagen.');
  $count = $database->select('node')->countQuery()->execute()->fetchField();
  $object->save($form, $state);
  je_flow_check($database->select('node')->countQuery()->execute()->fetchField() === $count, 'Doppelte Übernahme erzeugt zweiten Beitrag.');
  je_flow_check(!$submissions->access($published, User::load(2))->isAllowed(), 'Prüfweg ist trotz direktem Modus aktiv.');

  $token = $drafts->prepare($suggestion, time());
  $node = $drafts->buildNode($drafts->get($token));
  $object = \Drupal::entityTypeManager()->getFormObject('node', 'je_ai_submission')->setEntity($node);
  $state = (new FormState())->setFormObject($object);
  $state->set('je_ai_token', $token);
  $form = $object->buildForm([], $state);
  je_ai_submission_form_node_form_alter($form, $state);
  $button = $form['actions']['submit'];
  $state->setTriggeringElement($button);
  $object->save($form, $state);
  je_flow_check(!$node->isPublished() && $node->get('field_article_image')->isEmpty(), 'Entwurf ohne Bild wurde verhindert oder veröffentlicht.');

  \Drupal::configFactory()->getEditable('je_ai_submission.settings')->set('completion_action', 'review')->save();
  $object = \Drupal::entityTypeManager()->getFormObject('node', 'default')->setEntity($node);
  $state = (new FormState())->setFormObject($object);
  $form = $object->buildForm([], $state);
  je_ai_submission_form_node_form_alter($form, $state);
  je_flow_check(!isset($form['actions']['je_ai_publish']) && isset($form['actions']['je_ai_submit']), 'Prüfmodus zeigt beide oder keinen Abschlussweg.');
  je_flow_check($submissions->access($node, User::load(2))->isAllowed(), 'Prüfweg lässt sich nicht wieder aktivieren.');
  $trigger = ['#je_ai_action' => 'publish'];
  $state->setTriggeringElement($trigger);
  try {
    EditorialFlow::buildEntity('node', $node, $form, $state);
    throw new RuntimeException('Veraltetes Veröffentlichungsformular wird nach Moduswechsel akzeptiert.');
  }
  catch (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {}
}
finally {
  \Drupal::configFactory()->getEditable('je_ai_submission.settings')->setData($settings)->save();
  $switcher->switchBack();
  $transaction->rollBack();
  \Drupal::configFactory()->reset('je_ai_submission.settings');
}
echo "Geprüft: direkte Erstveröffentlichung mit Bild, Bildpflicht, Entwurf ohne Bild, native Rechte, exklusive Modi, Moduswechsel und Doppelübernahme. Keine KI-Anfrage oder Mail; Testdaten zurückgerollt.\n";
