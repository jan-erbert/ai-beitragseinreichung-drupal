<?php

use Drupal\Core\Mail\MailManager;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/** Prüft eine Invariante ohne Beitragstexte oder Adressen auszugeben. */
function je_notification_check(bool $condition, string $message): void {
  if (!$condition) { throw new RuntimeException($message); }
}

$database = \Drupal::database();
$transaction = $database->startTransaction();
$original_mail = \Drupal::service('plugin.manager.mail');
$settings = \Drupal::config('je_ai_submission.settings')->getRawData();
$switcher = \Drupal::service('account_switcher');
$switcher->switchTo(User::load(1));
$fake = new class($original_mail) extends MailManager {
  public array $messages = [];
  public bool $succeed = TRUE;

  public function __construct(private readonly object $original) {}

  /** Formatiert tatsächlich über Drupal, versendet aber nie nach außen. */
  public function mail($module, $key, $to, $langcode, $params = [], $reply = NULL, $send = TRUE) {
    $message = $this->original->mail($module, $key, $to, $langcode, $params, $reply, FALSE);
    $message['result'] = $this->succeed;
    $this->messages[] = $message;
    return $message;
  }
};
\Drupal::getContainer()->set('plugin.manager.mail', $fake);
try {
  \Drupal::configFactory()->getEditable('je_ai_submission.settings')->set('completion_action', 'review')->set('notify_enabled', TRUE)->set('notify_users', [2])->set('notify_author', TRUE)->save();
  $node = Node::create(['type' => 'article', 'title' => 'Interne Einreichungsprüfung', 'uid' => 2, 'status' => FALSE, 'moderation_state' => 'draft', 'body' => ['value' => '<p>Zu prüfender Beitrag ohne KI-Aufruf.</p>', 'format' => 'basic_html', 'summary' => 'Gespeicherter Vorschautext.']]);
  $node->save();
  $service = \Drupal::service('je_ai_submission.submissions');
  $service->register((int) $node->id(), 0, 'Veranstaltung');
  $unrelated = Node::create(['type' => 'article', 'title' => 'Normaler Entwurf', 'uid' => 2, 'status' => FALSE, 'moderation_state' => 'draft']);
  $unrelated->save();
  je_notification_check(!$service->access($unrelated, User::load(2))->isAllowed(), 'Normaler Entwurf wird fälschlich als KI-Einreichung behandelt.');
  je_notification_check(!$fake->messages, 'Entwurfsspeicherung sendet ungefragt.');
  je_notification_check($service->access($node, User::load(2))->isAllowed(), 'Redaktion kann eigenen KI-Entwurf nicht einreichen.');
  je_notification_check(!$service->access($node, new \Drupal\Core\Session\AnonymousUserSession())->isAllowed(), 'Anonymer Zugriff möglich.');
  try {
    $service->submit((int) $node->id(), (int) $node->getRevisionId() + 1);
    throw new LogicException('Veraltete Bestätigung akzeptiert.');
  }
  catch (DomainException $error) { je_notification_check(str_contains($error->getMessage(), 'inzwischen geändert'), 'Falscher Fehler für veraltete Bestätigung.'); }
  $fake->succeed = FALSE;
  $entry = $service->submit((int) $node->id(), (int) $node->getRevisionId());
  je_notification_check(count($fake->messages) === 1 && count($entry['deliveries']) === 1 && reset($entry['deliveries'])['status'] === 'failed', 'Versandfehler oder Autoren-Deduplizierung fehlerhaft.');
  $latest = $service->latest((int) $node->id());
  je_notification_check(!$latest->isPublished() && $latest->moderation_state->value === 'review', 'Einreichung veröffentlicht oder Prüfstatus fehlt.');
  $fake->succeed = TRUE;
  $entry = $service->submit((int) $node->id(), 0);
  je_notification_check(count($fake->messages) === 2 && reset($entry['deliveries'])['status'] === 'sent', 'Fehlgeschlagener Versand nicht wiederholt.');
  $service->submit((int) $node->id(), 0);
  je_notification_check(count($fake->messages) === 2, 'Erfolgreicher Versand wird doppelt ausgeführt.');
  $mail = $fake->messages[1];
  je_notification_check(str_contains((string) $mail['body'], 'Gespeicherter Vorschautext.') && str_contains((string) $mail['plain'], 'Zu prüfender Beitrag') && str_contains((string) $mail['body'], 'Veranstaltung') && str_contains((string) $mail['plain'], 'Veranstaltung'), 'HTML/Text/Branding oder Testumleitung fehlerhaft.');
  $entry['deliveries'][array_key_first($entry['deliveries'])]['status'] = 'sending';
  \Drupal::keyValue('je_ai_submission.submissions')->set((string) $node->id(), $entry);
  $service->submit((int) $node->id(), 0);
  je_notification_check(count($fake->messages) === 2, 'Unklare Zustellung wird ungeprüft wiederholt.');
  $preview = $service->preview($latest, $entry);
  je_notification_check($preview['meta']['Beitragsart'] === 'Veranstaltung', 'Beitragsart fehlt in der Mail.');
  $saved_label = $entry['style_label'];
  $without_log = $entry;
  $without_log['log_id'] = 0;
  je_notification_check($service->preview($latest, $without_log)['meta']['Beitragsart'] === $saved_label, 'Beitragsart hängt noch vom Protokoll ab.');

  $standard = $original_mail->mail('je_ai_submission', 'preview', 'test@example.invalid', 'de', ['submission' => $preview], NULL, FALSE);
  je_notification_check(str_contains((string) $standard['body'], 'cid:je-ai-mail-header') && !str_contains((string) $standard['body'], 'cid:lf-mail-header') && count($standard['params']['images']) === 1, 'Modulstandard verwendet eine Website-Vorlage oder falsche Headerbilder.');
  $uri = DRUPAL_ROOT . '/' . \Drupal::service('extension.list.module')->getPath('je_ai_submission') . '/assets/ai-beitragseinreichung-mobile.png';
  $header = \Drupal\file\Entity\File::create(['uri' => $uri, 'filename' => 'header-test.png', 'filemime' => 'image/png', 'uid' => 1]);
  $header->save();
  \Drupal::configFactory()->getEditable('je_ai_submission.settings')->set('mail_header_uuid', $header->uuid())->save();
  $custom = ['headers' => [], 'params' => [], 'body' => []];
  je_ai_submission_mail('submitted', $custom, ['submission' => $preview]);
  je_notification_check($custom['params']['images'][0]['uri'] === $uri, 'Eigener Header wird nicht über die Datei-UUID gewählt.');
  $default = ['headers' => [], 'params' => [], 'body' => []];
  je_ai_submission_mail('preview', $default, ['submission' => $preview]);
  je_notification_check(str_ends_with($default['params']['images'][0]['uri'], '/assets/ai-beitragseinreichung.png'), 'Standardprüfung übernimmt fälschlich einen individuellen Header.');

  \Drupal::configFactory()->getEditable('je_ai_submission.settings')->set('completion_action', 'publish')->set('notify_users', [1])->set('notify_author', TRUE)->save();
  $drafts = \Drupal::service('je_ai_submission.drafts');
  $token = $drafts->prepare(['title' => 'Interne Veröffentlichungsmail-Prüfung', 'content' => 'Gespeicherte Veröffentlichung ohne KI-Aufruf.', 'excerpt' => 'Veröffentlichte Vorschau.', 'tags' => []], time());
  $article = $drafts->buildNode($drafts->get($token));
  $media_ids = \Drupal::entityQuery('media')->condition('bundle', 'image')->condition('status', 1)->accessCheck(FALSE)->range(0, 1)->execute();
  je_notification_check((bool) $media_ids, 'Kein Prüf-Bild vorhanden.');
  $article->set('field_article_image', reset($media_ids));
  $object = \Drupal::entityTypeManager()->getFormObject('node', 'je_ai_submission')->setEntity($article);
  $state = (new \Drupal\Core\Form\FormState())->setFormObject($object);
  $state->set('je_ai_token', $token);
  $form = $object->buildForm([], $state);
  je_ai_submission_form_node_form_alter($form, $state);
  $button = $form['actions']['je_ai_publish'];
  $state->setTriggeringElement($button);
  $before = count($fake->messages);
  $object->save($form, $state);
  je_notification_check($article->isPublished() && count($fake->messages) === $before, 'Mail läuft vor dem abgeschlossenen Speichern.');
  try {
    $service->published((int) $article->id(), (int) $article->getRevisionId() + 1);
    throw new LogicException('Fremde Veröffentlichungsrevision akzeptiert.');
  }
  catch (DomainException) {}
  $callback = $button['#submit'][array_key_last($button['#submit'])];
  je_notification_check($callback === [\Drupal\je_ai_submission\Service\SubmissionNotification::class, 'afterPublication'], 'Nachgelagerter Veröffentlichungsversand fehlt am Speicherknopf.');
  $callback($form, $state);
  $publication = $service->get((int) $article->id())['publication'];
  $mail = $fake->messages[array_key_last($fake->messages)];
  je_notification_check(count($fake->messages) === $before + 1 && count($publication['deliveries']) === 1 && reset($publication['deliveries'])['status'] === 'sent', 'Veröffentlichungsmail oder Autoren-Deduplizierung fehlt.');
  $reroute = \Drupal::config('lf_test.mail_settings')->get('reroute_address');
  je_notification_check($mail['to'] === $reroute && reset($publication['deliveries'])['transport_to'] === $reroute, 'Tatsächliche Testumleitung nicht nachvollziehbar.');
  je_notification_check(str_contains($mail['subject'], 'veröffentlicht') && str_contains((string) $mail['body'], 'Ein neuer Beitrag wurde veröffentlicht.') && !str_contains((string) $mail['body'], 'noch nicht veröffentlicht'), 'Veröffentlichungsmail enthält irreführenden Prüftext.');
  $callback($form, $state);
  je_notification_check(count($fake->messages) === $before + 1, 'Erneutes Veröffentlichen versendet doppelt.');
  $saved = $service->get((int) $article->id());
  $key = array_key_first($saved['publication']['deliveries']);
  $saved['publication']['deliveries'][$key]['status'] = 'failed';
  \Drupal::keyValue('je_ai_submission.submissions')->set((string) $article->id(), $saved);
  $service->published((int) $article->id());
  je_notification_check(count($fake->messages) === $before + 2, 'Fehlgeschlagene Veröffentlichungsbenachrichtigung kann nicht wiederholt werden.');
  $saved = $service->get((int) $article->id());
  $saved['publication']['deliveries'][$key]['status'] = 'sending';
  \Drupal::keyValue('je_ai_submission.submissions')->set((string) $article->id(), $saved);
  $service->published((int) $article->id());
  je_notification_check(count($fake->messages) === $before + 2, 'Unklarer Veröffentlichungsversand wird ungeprüft wiederholt.');
  $other = Node::create(['type' => 'article', 'title' => 'Interner Autorenversandtest', 'uid' => 2, 'moderation_state' => 'published', 'field_article_image' => reset($media_ids), 'body' => ['value' => '<p>Lokaler Autorenversandtest.</p>', 'format' => 'basic_html']]);
  $other->save();
  $service->register((int) $other->id(), 0);
  $notice = $service->published((int) $other->id());
  je_notification_check(count($notice['publication']['deliveries']) === 2 && count($fake->messages) === $before + 4, 'Separater Autor erhält keine zusätzliche Benachrichtigung.');
}
finally {
  \Drupal::getContainer()->set('plugin.manager.mail', $original_mail);
  \Drupal::configFactory()->getEditable('je_ai_submission.settings')->setData($settings)->save();
  $switcher->switchBack();
  $transaction->rollBack();
  \Drupal::entityTypeManager()->getStorage('node')->resetCache();
  \Drupal::entityTypeManager()->getStorage('file')->resetCache();
}
echo "Geprüft: keine Mail beim Entwurf, Rechte, Revisionskonflikt, unveröffentlichter Prüfstatus, Versandfehler/Wiederholung, Dublettenschutz, HTML/Text, direkte Veröffentlichung nach Speicherung, beide Empfängerfälle und Testumleitung. Keine externe Zustellung; Daten zurückgerollt.\n";
