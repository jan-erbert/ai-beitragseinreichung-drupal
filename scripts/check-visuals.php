<?php

/** Prüft Symbolpool, Stilgruppen, Editor und Mail ohne KI oder externe Zustellung. */
use Drupal\je_ai_submission\Service\VisualStyles;
use Drupal\je_ai_submission\Service\ArticleSuggestion;
use Drupal\je_ai_submission\Service\DraftTransfer;
use Drupal\je_ai_submission\Form\VisualPoolForm;
use Drupal\Core\Form\FormState;

function je_visual_check(bool $condition, string $message): void {
  if (!$condition) { throw new RuntimeException($message); }
}
$settings = \Drupal::configFactory()->getEditable('je_ai_submission.settings');
$original = $settings->getRawData();
$transaction = \Drupal::database()->startTransaction();
$switcher = \Drupal::service('account_switcher');
$switcher->switchTo(\Drupal\user\Entity\User::load(1));
try {
  $pool = VisualStyles::defaults();
  $pool[] = ['id' => 'search', 'icon' => 'search', 'label' => 'Suche', 'description' => 'Hinweise zur Suche.', 'manual' => TRUE, 'ai' => TRUE, 'inline' => TRUE, 'heading' => TRUE];
  $group = ['id' => 'visual_test', 'label' => 'Lokale Prüfung', 'instructions' => 'Sachlich.', 'visual_enabled' => TRUE, 'visual_symbols' => ['information', 'search'], 'visual_instructions' => 'Nur passende Symbole.', 'visual_lead' => TRUE, 'visual_table' => TRUE];
  $settings->set('visual_pool', $pool)->set('visual_enabled', TRUE)->set('styles', [$group])->save();
  $settings_form = \Drupal::formBuilder()->getForm(\Drupal\je_ai_submission\Form\SettingsForm::class);
  $picker = $settings_form['presentation']['styles'][0]['visual_symbols'];
  je_visual_check($picker['#type'] === 'checkboxes' && $picker['#parents'] === ['styles', 0, 'visual_symbols'], 'Symbolkacheln verändern den Formularwertpfad.');
  je_visual_check($settings_form['connection']['provider_id']['#parents'] === ['provider_id'] && $settings_form['logging']['limits']['min_input_words']['#parents'] === ['limits', 'min_input_words'], 'Gliederung verändert Einstellungswerte.');
  je_visual_check(isset($settings_form['presentation']['visual_pool_link']['#url']) && str_contains((string) $picker['#options']['search'], '<img'), 'Pool-Link oder Symbolvorschau fehlt.');
  $normalize = new ReflectionMethod(\Drupal\je_ai_submission\Form\SettingsForm::class, 'visualGroup');
  $checked = $normalize->invoke(NULL, ['visual_symbols' => ['information' => 'information', 'besonderes' => 0, 'search' => 'search']]);
  je_visual_check($checked['visual_symbols'] === ['information', 'search'], 'Abgewählte Checkboxen bleiben gespeichert.');
  $context = VisualStyles::context($group);
  je_visual_check(count($context['symbols']) === 2, 'Stilgruppenauswahl wird nicht eingegrenzt.');
  $pool[2]['ai'] = FALSE;
  $settings->set('visual_pool', $pool)->save();
  je_visual_check(count(VisualStyles::context($group)['symbols']) === 1, 'KI-Sperre im Pool wird ignoriert.');
  $pool[2]['ai'] = TRUE;
  $settings->set('visual_pool', $pool)->save();
  VisualStyles::syncEditor();
  $definitions = VisualStyles::definitions();
  $suggestion = ['title' => 'Lokaler Gestaltungstest', 'content' => "Ein kurzer Einstieg.\n\n## Informationen\n\nZeit 23:40.\n\n| Name | Zeit |\n| --- | --- |\n| Test | 23:40 |", 'excerpt' => 'Vorschau', 'tags' => [], 'visual_style' => 'visual_test', 'presentation' => [['style_id' => 'information', 'position' => 'heading', 'target' => 'Informationen', 'occurrence' => 1], ['style_id' => 'search', 'position' => 'inline', 'target' => '23:40', 'occurrence' => 1], ['style_id' => 'besonderes', 'position' => 'heading', 'target' => 'Informationen', 'occurrence' => 1]]];
  $suggestion = ArticleSuggestion::parse(json_encode($suggestion));
  $html = VisualStyles::render($suggestion);
  je_visual_check(str_contains($html, $definitions['information:heading']['class']) && str_contains($html, $definitions['search:inline']['class']) && str_contains($html, $definitions['_table']['class']), 'Gestaltung fehlt.');
  $filtered = (string) check_markup($html, $settings->get('text_format'));
  je_visual_check(str_contains($filtered, $definitions['information:heading']['class']) && str_contains($filtered, $definitions['search:inline']['class']), 'Nativer Filter entfernt die Stile.');
  $node = \Drupal::service('je_ai_submission.drafts')->buildNode(['suggestion' => $suggestion, 'date' => time()]);
  je_visual_check($node->isNew() && !$node->isPublished() && $node->body->value === $html, 'Editorübernahme verliert Gestaltung oder speichert ungefragt.');
  $message = ['params' => ['submission' => ['content' => $filtered]]];
  VisualStyles::mail($message);
  je_visual_check(count($message['params']['images'] ?? []) === 2 && str_contains((string) $message['params']['submission']['content'], 'cid:je-ai-symbol-search'), 'Moduleigene Mail-Symbole fehlen.');
  $neutral_mail = ['params' => ['submission' => ['content' => '<p class="je-ai-lead">Einleitung</p><table class="je-ai-results"><tr><th>Name</th></tr><tr><td>Test</td></tr></table>']]];
  VisualStyles::mail($neutral_mail);
  je_visual_check(str_contains((string) $neutral_mail['params']['submission']['content'], 'background:#f3f6f7') && str_contains((string) $neutral_mail['params']['submission']['content'], 'font-size:18px'), 'Neutrale Mailgestaltung fehlt.');
  $pool[2]['manual'] = FALSE;
  $settings->set('visual_pool', $pool)->save();
  VisualStyles::syncEditor();
  $editor = \Drupal\editor\Entity\Editor::load($settings->get('text_format'));
  $editor_settings = $editor->getSettings();
  je_visual_check(!in_array('<strong class="je-ai-inline-search">', array_column($editor_settings['plugins']['ckeditor5_style']['styles'], 'element'), TRUE) && str_contains($editor_settings['plugins']['je_ai_submission_visual_preservation']['elements'], '<strong class="je-ai-inline-search">'), 'KI-only-Klasse wird im Menü angeboten oder beim Bearbeiten verloren.');
  $errors = \Drupal::service('config.typed')->createFromNameAndData('editor.editor.' . $settings->get('text_format'), $editor->toArray())->validate();
  je_visual_check(count($errors) === 0, 'KI-only-Editorkonfiguration ungültig: ' . (string) $errors);
  $settings->set('visual_enabled', FALSE)->save();
  je_visual_check(!VisualStyles::context($group) && !str_contains(VisualStyles::render($suggestion), 'class='), 'Globale Abschaltung wird ignoriert.');
  $settings->set('visual_enabled', TRUE)->save();
  $disabled = $group;
  $disabled['visual_enabled'] = FALSE;
  je_visual_check(!VisualStyles::context($disabled), 'Stilgruppenabschaltung wird ignoriert.');
  $form = \Drupal::formBuilder()->getForm(VisualPoolForm::class);
  je_visual_check(isset($form['rows'], $form['check']), 'Poolverwaltung fehlt.');
  $typed = \Drupal::service('config.typed')->createFromNameAndData('je_ai_submission.settings', $settings->getRawData())->validate();
  je_visual_check(count($typed) === 0, 'Konfigurationsschema fehlerhaft: ' . (string) $typed);
  $switcher->switchBack();
  $switcher->switchTo(\Drupal\user\Entity\User::load(2));
  je_visual_check(!\Drupal::service('access_manager')->checkNamedRoute('je_ai_submission.visual_pool', [], \Drupal::currentUser()), 'Redakteur darf Symbolpool verwalten.');
  echo "Geprüft: dreifache Freigabe, nativer Editor/HTML-Filter, ungespeicherte Übernahme, PNG-Mailbilder, Schema und Poolrechte. Keine KI-Anfrage oder externe Mail.\n";
}
finally {
  $switcher->switchBack();
  $transaction->rollBack();
  \Drupal::configFactory()->reset();
  \Drupal::entityTypeManager()->getStorage('editor')->resetCache();
  \Drupal::entityTypeManager()->getStorage('filter_format')->resetCache();
}
