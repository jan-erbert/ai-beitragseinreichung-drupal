<?php

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormState;
use Drupal\je_ai_submission\Service\AiTextGatewayInterface;
use Drupal\je_ai_submission\Service\ArticleAssistant;
use Drupal\je_ai_submission\Service\ReleaseNotes;
use Drupal\je_ai_submission\Form\SettingsForm;
use Drupal\je_ai_submission\Form\SubmissionForm;
use Drupal\je_ai_submission\Form\ReleaseForm;
use Drupal\je_ai_submission\Form\LogDeleteForm;

/** Meldet eine verletzte redaktionelle Anforderung ohne Beitragsdaten auszugeben. */
function je_editorial_check(bool $condition, string $message): void {
  if (!$condition) { throw new RuntimeException($message); }
}
$transaction = \Drupal::database()->startTransaction();
$switcher = \Drupal::service('account_switcher');
$switcher->switchTo(\Drupal\user\Entity\User::load(2));
$original_assistant = \Drupal::service('je_ai_submission.assistant');
try {
  $account = \Drupal::currentUser();
  foreach (['je_ai_submission.style_export' => ['style' => 'all'], 'je_ai_submission.log_delete' => ['log' => '1']] as $route => $parameters) {
    je_editorial_check(!\Drupal::service('access_manager')->checkNamedRoute($route, $parameters, $account), 'Redaktion kann Adminaktion aufrufen.');
  }
  je_editorial_check(\Drupal::service('access_manager')->checkNamedRoute('je_ai_submission.release', [], $account), 'Redaktion kann Releasehinweis nicht öffnen.');
  $release = new ReleaseForm();
  $state = new FormState();
  $form = $release->buildForm([], $state, TRUE);
  $html = (string) \Drupal::service('renderer')->renderInIsolation($form);
  je_editorial_check(str_contains($html, '<dialog') && str_contains($html, '/ai-beitragseinreichung-drupal/wiki'), 'Dialog oder Drupal-Wiki fehlt.');
  $release->submitForm($form, $state);
  je_editorial_check(\Drupal::service('user.data')->get('je_ai_submission', 2, 'release_seen') === ReleaseNotes::VERSION, 'Versionsbestätigung nicht kontogebunden gespeichert.');
  $gateway = new class implements AiTextGatewayInterface {
    /** Simuliert einen Anbieterfehler ausschließlich lokal. */
    public function generate(string $system, string $input, array $schema): string { throw new RuntimeException('Lokaler Prüffehler.'); }
  };
  \Drupal::getContainer()->set('je_ai_submission.assistant', new ArticleAssistant($gateway));
  $submission = new SubmissionForm();
  $state = new FormState();
  $style = \Drupal::config('je_ai_submission.settings')->get('styles')[0]['id'];
  $state->setValue('source', ['title' => 'Interner Prüftext', 'text' => 'Lokale Prüfung ohne Versand.', 'style' => $style, 'date' => new DrupalDateTime('2025-01-01'), 'tags' => 'Test', 'excerpt' => '', 'generate_excerpt' => TRUE, 'generate_tags' => TRUE]);
  $minimum_words = (int) (\Drupal::config('je_ai_submission.settings')->get('min_input_words') ?? 30);
  foreach ([$minimum_words - 1 => TRUE, $minimum_words => FALSE] as $count => $expect_error) {
    $validation = new FormState();
    $validation->setValue('source', ['text' => implode(' ', array_fill(0, $count, 'Läuferinnen')), 'excerpt' => '', 'tags' => '']);
    $validation->setValue('change', '');
    $form = [];
    $submission->validateForm($form, $validation);
    je_editorial_check(isset($validation->getErrors()['source][text']) === $expect_error, 'Mindestwortgrenze wird nicht korrekt geprüft.');
    $validation->clearErrors();
  }
  $validation = new FormState();
  $validation->setValue('source', ['text' => str_repeat('Test ', $minimum_words), 'additional_instructions' => str_repeat('x', 2001)]);
  $form = [];
  $submission->validateForm($form, $validation);
  je_editorial_check(isset($validation->getErrors()['source][additional_instructions']), 'Hinweis-Limit greift serverseitig nicht.');
  $validation->clearErrors();
  $markup = $submission->buildForm([], new FormState());
  foreach (['beitrag-einreichen', 'gute-beitraege'] as $page) {
    je_editorial_check(str_ends_with($markup['help'][$page]['#url']->toString(), '/ai-beitragseinreichung-drupal/wiki/' . $page), 'Passende Formular-Anleitung fehlt.');
  }
  je_editorial_check(isset($markup['source']['additional_instructions']) && $markup['progress']['#tag'] === 'dialog', 'Hinweisfeld oder Verarbeitungsdialog fehlt.');
  $form = [];
  $submission->generate($form, $state);
  je_editorial_check($state->get('generation_failed') === TRUE && !$state->get('suggestion'), 'Fehleranimation oder fehlgeschlagene Vorschau falsch behandelt.');
  $failure = $submission->buildForm([], $state);
  je_editorial_check($failure['failure']['#attributes']['data-je-lottie'] === 'error-animation.json', 'Originale Fehleranimation fehlt.');
  $s = \Drupal::config('je_ai_submission.settings');
  $before = $s->get('styles');
  $s_direct = SettingsForm::create(\Drupal::getContainer());
  $state = new FormState();
  $state->setUserInput(['styles' => $before]);
  $trigger = ['#je_action' => 'duplicate', '#je_index' => 0];
  $state->setTriggeringElement($trigger);
  $form = [];
  $s_direct->manageStyle($form, $state);
  $draft = $state->get('style_draft');
  je_editorial_check(count($draft) === count($before) + 1 && $draft[0]['id'] !== $draft[1]['id'], 'Duplizieren verliert Kennung oder Stiltext.');
  je_editorial_check(\Drupal::config('je_ai_submission.settings')->get('styles') === $before, 'Gruppenaktion speichert ungefragt.');
  $trigger = ['#je_action' => 'remove', '#je_index' => 1];
  $state->setTriggeringElement($trigger);
  $s_direct->manageStyle($form, $state);
  je_editorial_check($state->get('style_draft') === $before, 'Entfernen der Kopie verändert vorhandene Gruppen.');
  $switcher->switchBack();
  $switcher->switchTo(\Drupal\user\Entity\User::load(1));
  je_editorial_check(\Drupal::service('access_manager')->checkNamedRoute('je_ai_submission.log_delete', ['log' => '1'], \Drupal::currentUser()), 'Admin kann Löschbestätigung nicht öffnen.');
  $settings_help = SettingsForm::create(\Drupal::getContainer())->buildForm([], new FormState());
  je_editorial_check(str_ends_with($settings_help['help']['settings']['#url']->toString(), '/ai-beitragseinreichung-drupal/wiki/einstellungen'), 'Einstellungs-Anleitung fehlt.');
  $count = \Drupal::database()->select('node')->countQuery()->execute()->fetchField();
  $id = \Drupal::service('je_ai_submission.audit')->record('Lokale Prüfung', 'generate', 1, ['text' => 'Nur Test'], NULL);
  $delete = new LogDeleteForm();
  $state = new FormState();
  $form = $delete->buildForm([], $state, (string) $id);
  je_editorial_check(str_contains((string) $delete->getQuestion(), 'löschen'), 'Löschbestätigung fehlt.');
  $delete->submitForm($form, $state);
  je_editorial_check(!\Drupal::database()->select('je_ai_submission_log', 'l')->condition('id', $id)->countQuery()->execute()->fetchField(), 'Bestätigter Einzeleintrag bleibt erhalten.');
  je_editorial_check(\Drupal::database()->select('node')->countQuery()->execute()->fetchField() === $count, 'Protokolllöschung verändert Beiträge.');
}
finally {
  \Drupal::getContainer()->set('je_ai_submission.assistant', $original_assistant);
  $switcher->switchBack();
  $transaction->rollBack();
}
echo "Geprüft: Release/Wiki/Versionsbestätigung, Adminrouten, lokale Fehleranimation, reversible Gruppenaktionen und bestätigte Einzellöschung. Keine API-Anfrage; Daten zurückgerollt.\n";
