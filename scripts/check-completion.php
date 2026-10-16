<?php

use Drupal\Core\Form\FormState;
use Drupal\file\Entity\File;
use Drupal\je_ai_submission\Controller\LogController;
use Drupal\je_ai_submission\Form\SettingsForm;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/** Meldet verletzte Rechte-, Begriffs- und Bildanforderungen ohne Inhaltsausgabe. */
function je_completion_check(bool $condition, string $message): void {
  if (!$condition) { throw new RuntimeException($message); }
}

$transaction = \Drupal::database()->startTransaction();
$switcher = \Drupal::service('account_switcher');
$switcher->switchTo(User::load(1));
try {
  $settings = \Drupal::config('je_ai_submission.settings');
  $pool_before = $settings->get('preferred_tags');
  $term = Term::create(['vid' => $settings->get('tags_vocabulary'), 'name' => 'Interner Begriffstest ' . bin2hex(random_bytes(3))]);
  $term->save();
  foreach ([0, 1] as $published) {
    $article = Node::create(['type' => 'article', 'title' => 'Interner Häufigkeitstest', 'status' => FALSE, 'moderation_state' => 'draft', 'field_tags' => [$term->id()]]);
    $article->save();
    // Nur Query-Fixture innerhalb der zurückgerollten Transaktion, kein Publikationstest.
    if ($published) { \Drupal::database()->update('node_field_data')->fields(['status' => 1])->condition('nid', $article->id())->execute(); }
  }
  $terms = \Drupal::service('je_ai_submission.tags')->frequent(1000);
  je_completion_check(($terms[$term->id()]['count'] ?? -1) === 1, 'Häufigkeit zählt Entwürfe oder Revisionen mit.');
  $form_state = new FormState();
  $form_state->setValue('preferred_tags', implode("\n", $pool_before));
  $form_state->setValue(['frequent_tags', 'selected'], [$term->id() => $term->id()]);
  $form_state->setUserInput(['preferred_tags' => implode("\n", $pool_before)]);
  $form = [];
  SettingsForm::create(\Drupal::getContainer())->addFrequentTags($form, $form_state);
  je_completion_check(str_contains($form_state->getValue('preferred_tags'), $term->label()) && \Drupal::config('je_ai_submission.settings')->get('preferred_tags') === $pool_before, 'Poolübernahme fehlt oder speichert ungefragt.');

  $role = Role::create(['id' => 'je_test_individuelle_rolle', 'label' => 'Individuelle Prüfrolle']);
  $role->grantPermission('view je ai submission log')->save();
  $reader = User::create(['name' => 'je-interne-pruefung-' . bin2hex(random_bytes(3)), 'status' => 1, 'roles' => [$role->id()]]);
  $reader->save();
  $access = \Drupal::service('access_manager');
  je_completion_check($access->checkNamedRoute('je_ai_submission.log', [], $reader), 'Eigene Rolle kann Protokoll nicht lesen.');
  je_completion_check(!$access->checkNamedRoute('je_ai_submission.settings', [], $reader) && !$access->checkNamedRoute('je_ai_submission.log_delete', ['log' => '1'], $reader), 'Leserecht erlaubt Einstellungen oder Löschen.');
  $switcher->switchBack();
  $switcher->switchTo($reader);
  $id = \Drupal::service('je_ai_submission.audit')->record('Interner Test', 'generate', 1, [], NULL);
  $build = LogController::create(\Drupal::getContainer())->detail((string) $id);
  je_completion_check($build['delete']['#access'] === FALSE, 'Löschlink für reine Leser sichtbar.');
  $switcher->switchBack();
  $switcher->switchTo(User::load(1));
  $role->grantPermission('delete je ai submission log')->save();
  $reader = User::load($reader->id());
  je_completion_check($access->checkNamedRoute('je_ai_submission.log_delete', ['log' => (string) $id], $reader), 'Gesondertes Löschrecht funktioniert nicht.');
  je_completion_check(!$access->checkNamedRoute('je_ai_submission.settings', [], $reader), 'Löschrecht öffnet Einstellungen.');
  $role->revokePermission('view je ai submission log')->save();
  $reader = User::load($reader->id());
  je_completion_check(!$access->checkNamedRoute('je_ai_submission.log_delete', ['log' => (string) $id], $reader), 'Löschen ohne zusätzliches Leserecht möglich.');
  je_completion_check($access->checkNamedRoute('je_ai_submission.log', [], User::load(2)), 'Gewünschtes Protokoll-Leserecht der Redaktion fehlt.');

  $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['filemime' => 'image/jpeg']);
  $samples = [];
  foreach ($files as $file) { if (str_starts_with($file->getFileUri(), 'public://')) { $samples[] = $file; if (count($samples) === 3) break; } }
  je_completion_check(count($samples) === 3, 'Drei öffentliche Bild-Fixtures fehlen.');
  $media = [];
  foreach ($samples as $file) {
    $image = Media::create(['bundle' => 'image', 'name' => 'Interne Bildprüfung', 'status' => 1, 'field_media_image' => ['target_id' => $file->id(), 'alt' => 'Internes Testbild']]);
    $image->save();
    $media[] = $image;
  }
  $private = File::create(['uri' => 'private://interne-bildpruefung.png', 'filename' => 'interne-bildpruefung.png', 'filemime' => 'image/png']);
  $private->save();
  $body = '<p>Vor dem Inline-Bild.</p><img src="' . \Drupal::service('file_url_generator')->generateAbsoluteString($samples[1]->getFileUri()) . '" data-entity-type="file" data-entity-uuid="' . $samples[1]->uuid() . '" alt="Inline-Testbild"><p>Nach dem Inline-Bild.</p><img src="https://example.invalid/fremd.jpg"><img src="file:///etc/passwd"><img data-entity-uuid="' . $private->uuid() . '" src="/system/files/interne-bildpruefung.png">';
  $node = Node::create(['type' => 'article', 'title' => 'Interne Bildmailprüfung', 'status' => FALSE, 'moderation_state' => 'draft', 'body' => ['value' => $body, 'format' => 'basic_html'], 'field_article_image' => [$media[0]->id()], 'field_gallery_images' => [$media[1]->id(), $media[2]->id()]]);
  $node->save();
  $preview = \Drupal::service('je_ai_submission.submissions')->preview($node, []);
  je_completion_check(str_contains($preview['excerpt'], 'Inline-Bild. Nach'), 'Automatischer Vorschautext verbindet benachbarte Absätze ohne Leerzeichen.');
  je_completion_check(count($preview['images']) === 3 && $preview['featured'] && count($preview['gallery']) === 1, 'Bildfelder, Inline-Bild oder Deduplizierung fehlerhaft.');
  $html = (string) $preview['content'];
  je_completion_check(str_contains($html, 'cid:je-ai-article-') && !str_contains($html, 'example.invalid') && !str_contains($html, '/etc/passwd') && !str_contains($html, $private->uuid()), 'Unsichere oder externe Bildquelle übernommen.');
  foreach ($preview['images'] as $image) {
    $dimensions = getimagesize(\Drupal::service('file_system')->realpath($image['uri']));
    je_completion_check($dimensions[0] <= 1000 && $dimensions[1] <= 1000, 'Mailbild nicht verkleinert.');
  }
  $mail = \Drupal::service('plugin.manager.mail')->mail('je_ai_submission', 'submitted', 'test@example.invalid', 'de', ['submission' => $preview], NULL, FALSE);
  je_completion_check(count($mail['params']['images']) >= 4 && str_contains((string) $mail['body'], 'cid:je-ai-article-'), 'Artikelbilder fehlen in der formatierten Mail.');
  $captured = NULL;
  $listener = static function (\Symfony\Component\Mailer\Event\MessageEvent $event) use (&$captured): void { $captured = $event->getMessage(); };
  $dispatcher = \Drupal::service('event_dispatcher');
  $dispatcher->addListener(\Symfony\Component\Mailer\Event\MessageEvent::class, $listener);
  try {
    $mail['symfony_mailer_lite_transport'] = 'disabled';
    $plugin = \Drupal::service('plugin.manager.mail')->getInstance(['module' => 'je_ai_submission', 'key' => 'submitted']);
    je_completion_check($plugin->mail($mail) === TRUE && $captured instanceof \Symfony\Component\Mime\Email && count($captured->getAttachments()) === count($mail['params']['images']), 'CID-Bilder sind keine gültigen MIME-Bestandteile.');
  }
  finally { $dispatcher->removeListener(\Symfony\Component\Mailer\Event\MessageEvent::class, $listener); }
  if (getenv('LF_MAIL_PREVIEW_PATH')) {
    $html = (string) $mail['body'];
    foreach ($mail['params']['images'] as $image) {
      $html = str_replace('cid:' . $image['cid'], 'data:' . $image['filemime'] . ';base64,' . base64_encode(file_get_contents($image['uri'])), $html);
    }
    file_put_contents(getenv('LF_MAIL_PREVIEW_PATH'), $html);
  }
}
finally {
  $switcher->switchBack();
  $transaction->rollBack();
  foreach (['node', 'taxonomy_term', 'user', 'user_role', 'media', 'file'] as $type) { \Drupal::entityTypeManager()->getStorage($type)->resetCache(); }
}
echo "Geprüft: veröffentlichte Nutzungshäufigkeiten, ungespeicherte Poolübernahme, individuelle Rollen mit getrennten Lese-/Lösch-/Einstellungsrechten und lesende Redaktion; Beitrags-/Inline-/Galeriebilder, Deduplizierung, Größen und Ausschluss privater/externer Quellen. Keine KI-Anfrage oder externe Mail; Daten zurückgerollt.\n";
