<?php

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\je_ai_submission\Access\SubmissionAccess;
use Drupal\je_ai_submission\Service\AiTextGatewayInterface;
use Drupal\je_ai_submission\Service\ArticleAssistant;

/** Meldet eine verletzte Anforderung der Drupal-Anbindung. */
function je_ai_check(bool $condition, string $message): void {
  if (!$condition) {
    throw new RuntimeException($message);
  }
}
$transaction = \Drupal::database()->startTransaction();
$switcher = \Drupal::service('account_switcher');
$switcher->switchTo(\Drupal\user\Entity\User::load(2));
try {
  $user = \Drupal::currentUser();
  $access = new SubmissionAccess();
  je_ai_check($access->access($user)->isAllowed(), 'Redaktion kann den KI-Einstieg nicht nutzen.');
  je_ai_check(!$user->hasPermission('administer je ai submission') && !$user->hasPermission('administer ai providers'), 'Redaktion darf KI-Anbieter konfigurieren.');
  $gateway = new class implements AiTextGatewayInterface {
    /** Liefert ausschließlich lokale Prüfdaten statt eines API-Aufrufs. */
    public function generate(string $system, string $input, array $schema): string {
      return json_encode(['title' => 'Interner KI-Integrationstest', 'content' => "## Bericht\n\nEin lokaler Prüftext ohne Versand.", 'excerpt' => 'Vorschautext zur Prüfung.', 'tags' => ['Radfahren']], JSON_THROW_ON_ERROR);
    }
  };
  $assistant = new ArticleAssistant($gateway);
  $count = \Drupal::database()->select('node')->countQuery()->execute()->fetchField();
  $result = $assistant->suggest(['title' => '', 'text' => 'Prüfdaten', 'excerpt' => '', 'tags' => [], 'generate_excerpt' => TRUE, 'generate_tags' => TRUE], ['instructions' => 'Sachlich'], 'Keine Emojis.', ['Radfahren']);
  je_ai_check(\Drupal::database()->select('node')->countQuery()->execute()->fetchField() === $count, 'Vorschau hat bereits einen Beitrag gespeichert.');
  $drafts = \Drupal::service('je_ai_submission.drafts');
  $route_access = \Drupal::service('access_manager')->checkNamedRoute('je_ai_submission.log', [], $user);
  je_ai_check($route_access, 'Redaktion kann das KI-Protokoll nicht lesen.');
  je_ai_check(\Drupal::service('access_manager')->checkNamedRoute('je_ai_submission.log_detail', ['log' => 1], $user), 'Redaktion kann Protokolldetails nicht lesen.');
  $settings = \Drupal::configFactory()->getEditable('je_ai_submission.settings');
  $original_logging = $settings->get('log_content');
  $original_limit = $settings->get('log_limit');
  $settings->set('log_content', TRUE)->save();
  $audit = \Drupal::service('je_ai_submission.audit');
  $log_id = $audit->record('Prüfung', 'generate', 123, ['title' => 'Test', 'text' => '<script>Prüftext</script>', 'excerpt' => '', 'tags' => []], $result);
  $row = \Drupal::database()->select('je_ai_submission_log', 'l')->fields('l')->condition('id', $log_id)->execute()->fetchObject();
  je_ai_check($row && (int) $row->uid === 2 && str_contains($row->comparison, 'Prüftext'), 'Aktivierter Textvergleich fehlt.');
  $settings->set('log_content', FALSE)->save();
  $metadata_id = $audit->record('Prüfung', 'revise', 25, ['text' => 'Nicht speichern'], NULL, '', NULL, 'local-test');
  $stored = \Drupal::database()->select('je_ai_submission_log', 'l')->fields('l', ['comparison'])->condition('id', $metadata_id)->execute()->fetchObject();
  je_ai_check($stored->comparison === NULL, 'Deaktivierter Textvergleich wurde dennoch gespeichert.');
  $settings->set('log_content', $original_logging)->save();
  $token = $drafts->prepare($result, strtotime('2025-03-14 09:00:00 Europe/Berlin'), $log_id, 'Veranstaltung');
  $node = $drafts->buildNode($drafts->get($token));
  je_ai_check($node->isNew() && !$node->isPublished() && $node->get('moderation_state')->value === 'draft', 'Übernahme ist kein ungespeicherter Entwurf.');
  je_ai_check((int) $node->getOwnerId() === 2 && date('Y', $node->getCreatedTime()) === '2025', 'Autor oder historisches Beitragsdatum verloren.');
  je_ai_check($node->body->summary === 'Vorschautext zur Prüfung.' && str_contains($node->body->value, '<h2>Bericht</h2>'), 'Textformat oder Vorschautext verloren.');
  $violations = $node->validate();
  je_ai_check(count($violations) === 0, 'Entwurf ist nicht Drupal-gültig: ' . (string) $violations);
  $form_object = \Drupal::entityTypeManager()->getFormObject('node', 'je_ai_submission')->setEntity($node);
  $form_state = new \Drupal\Core\Form\FormState();
  $form_state->setFormObject($form_object);
  $form_state->set('je_ai_token', $token);
  $form = $form_object->buildForm([], $form_state);
  je_ai_check(isset($form['body'], $form['field_article_image'], $form['field_gallery_images'], $form['field_tags']), 'Native Beitrags- oder Medienfelder fehlen.');
  $form_object->save($form, $form_state);
  je_ai_check($node->id() && !$node->isPublished(), 'Entwurf ohne Beitragsbild konnte nicht regulär gespeichert werden.');
  $node->set('moderation_state', 'published');
  try {
    $node->save();
    throw new RuntimeException('Veröffentlichung ohne Beitragsbild wurde nicht blockiert.');
  }
  catch (\Drupal\Core\Entity\EntityStorageException $error) {
    je_ai_check(str_contains($error->getMessage(), 'Beitragsbild'), 'Unerwarteter Speicherfehler.');
  }
  $linked_node = \Drupal::database()->select('je_ai_submission_log', 'l')->fields('l', ['nid'])->condition('id', $log_id)->execute()->fetchField();
  je_ai_check((int) $linked_node === (int) $node->id(), 'Protokoll ist nicht mit gespeichertem Entwurf verknüpft.');
  $second_node = $drafts->buildNode(['suggestion' => $result, 'date' => $node->getCreatedTime()]);
  $second_form_object = \Drupal::entityTypeManager()->getFormObject('node', 'je_ai_submission')->setEntity($second_node);
  $second_form_object->save($form, $form_state);
  je_ai_check($second_node->isNew(), 'Doppelte Übernahme erzeugt einen weiteren Beitrag.');
  je_ai_check($drafts->get($token)['saved_node'] === (int) $node->id(), 'Wiederholte Übernahme ist nicht markiert.');
  je_ai_check(\Drupal::service('je_ai_submission.submissions')->get((int) $node->id())['style_label'] === 'Veranstaltung', 'Beitragsart geht bei der ersten Speicherung verloren.');
  $switcher->switchBack();
  $switcher->switchTo(\Drupal\user\Entity\User::load(1));
  je_ai_check($drafts->get($token) === NULL, 'Fremdes Konto kann eine Übergabe lesen.');
  je_ai_check(\Drupal::service('access_manager')->checkNamedRoute('je_ai_submission.log', [], \Drupal::currentUser()), 'Admin kann Protokoll nicht lesen.');
  $settings_form = \Drupal::formBuilder()->getForm(\Drupal\je_ai_submission\Form\SettingsForm::class);
  je_ai_check($settings_form['connection']['model_choice']['#type'] === 'select', 'Modellauswahl ist kein Dropdown.');
  $current_model = \Drupal::config('je_ai_submission.settings')->get('model_id');
  je_ai_check(isset($settings_form['connection']['model_choice']['#options'][$current_model]), 'Aktuelles Modell fehlt ohne geladene Liste.');
  foreach (\Drupal::config('je_ai_submission.settings')->get('styles') as $style) {
    je_ai_check(mb_strlen($style['instructions']) <= 10000, 'Überlange Stilgruppe bremst Einstellungen.');
  }
  $model_list = \Drupal::service('je_ai_submission.models')->stored('local_test_provider');
  je_ai_check($model_list === ['models' => [], 'updated' => 0], 'Lokaler Modellkatalog benötigt Anbieter oder liefert keine sichere Vorgabe.');
  $controller = new \Drupal\je_ai_submission\Controller\LogController();
  $detail = $controller->detail((string) $log_id);
  $html = (string) \Drupal::service('renderer')->renderInIsolation($detail);
  je_ai_check(str_contains($html, '&lt;script&gt;') && !str_contains($html, '<script>Prüftext'), 'Textvergleich gibt aktives HTML aus.');
  $settings->set('log_limit', 2)->save();
  $audit->trim();
  je_ai_check((int) \Drupal::database()->select('je_ai_submission_log')->countQuery()->execute()->fetchField() <= 2, 'Protokollgrenze greift nicht.');
  $settings->set('log_limit', $original_limit)->save();
  $switcher->switchBack();
  $switcher->switchTo(new AnonymousUserSession());
  je_ai_check(!$access->access(\Drupal::currentUser())->isAllowed(), 'Anonymer Zugriff möglich.');
  $errors = \Drupal::service('config.typed')->createFromNameAndData('je_ai_submission.settings', \Drupal::config('je_ai_submission.settings')->getRawData())->validate();
  je_ai_check(count($errors) === 0, 'Konfigurationsschema ungültig: ' . (string) $errors);
}
finally {
  $switcher->switchBack();
  $transaction->rollBack();
  \Drupal::configFactory()->reset('je_ai_submission.settings');
  \Drupal::entityTypeManager()->getStorage('node')->resetCache();
  \Drupal::entityTypeManager()->getStorage('taxonomy_term')->resetCache();
}
echo "Geprüft: Rollen, lokale Testantwort, ungespeicherte Vorschau, Entwurfsübernahme, Autor/Datum, Textfelder, Bildpflicht und private Übergabe. Keine API-Anfrage; Testdaten zurückgerollt.\n";
