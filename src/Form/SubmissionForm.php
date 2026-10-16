<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\je_ai_submission\Service\ArticleSuggestion;
use Drupal\je_ai_submission\Service\DraftTransfer;

/** Bietet einen zusätzlichen Einstieg mit KI-Vorschau vor dem Beitragseditor. */
final class SubmissionForm extends FormBase {

  /** {@inheritdoc} */
  public function getFormId(): string {
    return 'je_ai_submission_form';
  }

  /** Baut eine schlichte Vorbereitung und die überprüfbare Textvorschau. */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('je_ai_submission.settings');
    $minimum_words = max(1, (int) ($config->get('min_input_words') ?? 30));
    $preview = $form_state->get('suggestion');
    $ready = $config->get('enabled') && $config->get('model_id');
    $form['#attributes']['class'][] = 'je-ai-submission';
    $form['#attached']['library'][] = 'je_ai_submission/enhancements';
    $form['#attached']['drupalSettings']['jeAi']['animations'] = base_path() . \Drupal::service('extension.list.module')->getPath('je_ai_submission') . '/assets/lottie/';
    $form['#prefix'] = '<div id="je-ai-submission-wrapper">';
    $form['#suffix'] = '</div>';
    $asset_path = \Drupal::service('extension.list.module')->getPath('je_ai_submission') . '/assets/';
    $form['brand'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['je-ai-brand']],
      'picture' => [
        '#type' => 'html_tag', '#tag' => 'picture',
        'mobile' => ['#type' => 'html_tag', '#tag' => 'source', '#attributes' => ['media' => '(max-width: 768px)', 'srcset' => base_path() . $asset_path . 'ai-beitragseinreichung-mobile.png']],
        'image' => ['#theme' => 'image', '#uri' => $asset_path . 'ai-beitragseinreichung.png', '#alt' => 'AI Beitragseinreichung – Jan Erbert – Web Development'],
      ],
    ];
    if (!$ready) {
      $form['setup'] = ['#type' => 'item', '#markup' => $this->t('Die KI-Anbindung ist noch nicht freigeschaltet. Ein Administrator muss Anbieterzugang und Modell einrichten.')];
    }
    $form['source'] = ['#type' => 'details', '#title' => $this->t('Ausgangstext'), '#open' => !$preview, '#tree' => TRUE];
    $form['source']['title'] = ['#type' => 'textfield', '#title' => $this->t('Arbeitstitel'), '#maxlength' => 120, '#description' => $this->t('Optional. Wenn er fehlt, schlägt die KI einen passenden Titel aus deinem Text vor.')];
    $form['source']['text'] = ['#type' => 'textarea', '#title' => $this->t('Text oder Stichpunkte'), '#rows' => 10, '#required' => TRUE, '#description' => $this->t('Mindestens @minimum Wörter. Alle wichtigen Namen, Ergebnisse, Orte und Termine eintragen. Die KI soll keine fehlenden Angaben ergänzen.', ['@minimum' => $minimum_words])];
    $styles = [];
    $group_icons = [];
    foreach ($config->get('styles') as $style) {
      $styles[$style['id']] = $style['label'];
      $group_icons[$style['id']] = $style['icon'] ?? 'file-text';
    }
    $form['source']['additional_instructions'] = ['#type' => 'textarea', '#title' => $this->t('Zusätzliche Hinweise für die KI'), '#rows' => 3, '#maxlength' => 2000, '#description' => $this->t('Optional, zum Beispiel: Den gemeinsamen Abschluss besonders hervorheben oder kurze Absätze verwenden. Gilt für diesen Beitrag, auch bei Überarbeitungen. Fakten und redaktionelle Grundregeln bleiben verbindlich.')];
    $form['source']['style'] = ['#type' => 'select', '#title' => $this->t('Beitragsart'), '#options' => $styles, '#required' => TRUE, '#attributes' => ['data-je-icon-select' => '1', 'data-je-group-icons' => json_encode($group_icons)]];
    \Drupal\je_ai_submission\Service\StyleGroups::attach($form, array_values($group_icons));
    $form['source']['date'] = ['#type' => 'datetime', '#title' => $this->t('Beitragsdatum'), '#default_value' => new DrupalDateTime(), '#required' => TRUE, '#description' => $this->t('Für einen nachgetragenen Bericht das ursprüngliche Beitragsdatum einstellen. Daraus entsteht die Jahreszuordnung.')];
    $form['source']['tags'] = ['#type' => 'textfield', '#title' => $this->t('Schlagwörter'), '#maxlength' => 800, '#attributes' => ['data-je-tags' => '1'], '#description' => $this->t('Optional, mit Komma trennen. Vorhandene Schreibweisen werden bevorzugt.')];
    $form['source']['year'] = ['#type' => 'container', '#attributes' => ['class' => ['je-ai-year'], 'data-je-year' => '1'], 'text' => ['#plain_text' => $this->t('Jahr aus Beitragsdatum: @year – wird automatisch zugeordnet, kein zusätzliches Schlagwort.', ['@year' => ($form_state->getValue(['source', 'date']) instanceof DrupalDateTime ? $form_state->getValue(['source', 'date']) : new DrupalDateTime())->format('Y')])]];
    $form['source']['excerpt'] = ['#type' => 'textarea', '#title' => $this->t('Vorschautext'), '#states' => ['visible' => [':input[name="source[generate_excerpt]"]' => ['checked' => FALSE]]], '#rows' => 2, '#maxlength' => 1000, '#description' => $this->t('Optional für Nachrichtenübersicht und Newsletter. Ohne eigenen oder KI-Vorschautext greift die vorhandene automatische Kürzung.')];
    $form['source']['generate_excerpt'] = ['#type' => 'checkbox', '#title' => $this->t('Vorschautext von der KI vorschlagen lassen'), '#default_value' => TRUE, '#attributes' => ['data-je-ai-option' => '1']];
    $form['source']['ai_active'] = ['#type' => 'container', '#attributes' => ['class' => ['je-ai-active']], 'motion' => ['#type' => 'container', '#attributes' => ['class' => ['je-ai-motion'], 'data-je-lottie' => 'ai-enabled-animation.json', 'aria-hidden' => 'true']], 'label' => ['#plain_text' => $this->t('KI-Unterstützung für diesen Vorschlag')]];
    $form['source']['generate_tags'] = ['#type' => 'checkbox', '#title' => $this->t('Zusätzliche Schlagwörter von der KI vorschlagen lassen'), '#default_value' => TRUE, '#attributes' => ['data-je-ai-option' => '1']];
    if ($preview) {
      $form['preview'] = ['#type' => 'container', '#attributes' => ['class' => ['je-ai-preview']]];
      $form['preview']['motion'] = ['#type' => 'container', '#attributes' => ['class' => ['je-ai-motion', 'je-ai-motion--result'], 'data-je-lottie' => $form_state->get('generation_failed') ? 'error-animation.json' : 'success-animation.json', 'aria-hidden' => 'true']];
      $form['preview']['heading'] = ['#type' => 'html_tag', '#tag' => 'h2', '#value' => $this->t('Vorschau')];
      $form['preview']['title'] = ['#type' => 'html_tag', '#tag' => 'h3', '#value' => \Drupal\Component\Utility\Html::escape($preview['title'])];
      $form['preview']['excerpt'] = ['#type' => 'item', '#title' => $this->t('Vorschautext'), '#plain_text' => $preview['excerpt'] ?: $this->t('Wird automatisch aus dem Beitragstext gekürzt.'), '#attributes' => ['class' => ['je-ai-preview__summary']]];
      $form['preview']['body'] = ['#type' => 'processed_text', '#text' => \Drupal\je_ai_submission\Service\VisualStyles::render($preview), '#format' => $config->get('text_format'), '#prefix' => '<div class="je-ai-preview__body">', '#suffix' => '</div>'];
      $form['preview']['tags'] = ['#type' => 'item', '#title' => $this->t('Schlagwörter'), '#plain_text' => implode(', ', $preview['tags'])];
      $form['change'] = ['#type' => 'textarea', '#title' => $this->t('Änderungswunsch an die KI'), '#rows' => 2, '#maxlength' => 2000, '#description' => $this->t('Zum Beispiel: Kürze den Einstieg und stelle die Ergebnisse übersichtlicher dar.')];
    }
    if ($form_state->get('generation_failed') && !$preview) {
      $form['failure'] = ['#type' => 'container', '#attributes' => ['class' => ['je-ai-motion'], 'data-je-lottie' => 'error-animation.json', 'aria-hidden' => 'true']];
    }
    $form['progress'] = [
      '#type' => 'html_tag', '#tag' => 'dialog',
      '#attributes' => ['class' => ['je-ai-progress'], 'aria-label' => $this->t('KI-Vorschlag wird erstellt'), 'hidden' => TRUE],
      'motion' => ['#type' => 'container', '#attributes' => ['class' => ['je-ai-motion'], 'data-je-lottie' => 'ki-animation.json', 'aria-hidden' => 'true']],
      'status' => ['#type' => 'container', '#attributes' => ['role' => 'status', 'aria-live' => 'polite'],
        'heading' => ['#type' => 'html_tag', '#tag' => 'h2', '#value' => $this->t('Dein Vorschlag entsteht')],
        'text' => ['#type' => 'html_tag', '#tag' => 'p', '#value' => $this->t('Die KI verarbeitet deine Angaben. Bitte einen Moment warten.')],
      ],
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['generate'] = ['#type' => 'submit', '#value' => $preview ? $this->t('Vorschlag überarbeiten') : $this->t('KI-Vorschlag erstellen'), '#submit' => ['::generate'], '#ajax' => ['callback' => '::ajaxRefresh', 'wrapper' => 'je-ai-submission-wrapper', 'event' => 'click', 'progress' => ['type' => 'none']], '#attributes' => ['data-je-ai-generate' => '1'], '#disabled' => !$ready];
    if ($preview) {
      $form['actions']['accept'] = ['#type' => 'submit', '#value' => $this->t('Im Beitragseditor weiterbearbeiten'), '#button_type' => 'primary', '#attributes' => ['data-je-ai-accept' => '1'], '#submit' => ['::submitForm']];
    }
    $form['actions']['normal'] = ['#type' => 'link', '#title' => $this->t('Beitrag ohne KI erstellen'), '#url' => Url::fromRoute('node.add', ['node_type' => $config->get('content_type')])];
    return $form;
  }

  /** Prüft Eingaben unabhängig vom KI-Anbieter. */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $source = $form_state->getValue('source');
    $minimum_words = max(1, (int) ($this->config('je_ai_submission.settings')->get('min_input_words') ?? 30));
    $word_count = preg_match_all("/[\\p{L}\\p{N}]+(?:['’-][\\p{L}\\p{N}]+)*/u", strip_tags($source['text'] ?? ''));
    if ($word_count < $minimum_words) {
      $form_state->setErrorByName('source][text', $this->t('Bitte mindestens @minimum Wörter als Text oder Stichpunkte eingeben. Aktuell: @count Wörter. So erhält die KI genügend Informationen für einen Beitrag.', ['@minimum' => $minimum_words, '@count' => $word_count]));
    }
    if (mb_strlen($source['text'] ?? '') > $this->config('je_ai_submission.settings')->get('max_input_chars')) {
      $form_state->setErrorByName('source][text', $this->t('Der Ausgangstext ist zu lang. Bitte kürze ihn.'));
    }
    if (trim($source['text'] ?? '') === '' || mb_strlen($source['excerpt'] ?? '') > 1000 || mb_strlen($form_state->getValue('change') ?? '') > 2000) {
      $form_state->setErrorByName('source][text', $this->t('Bitte einen nicht leeren Ausgangstext, höchstens 1000 Zeichen Vorschautext und höchstens 2000 Zeichen Änderungswunsch eingeben.'));
    }
    if (mb_strlen($source['additional_instructions'] ?? '') > 2000) {
      $form_state->setErrorByName('source][additional_instructions', $this->t('Bitte höchstens 2000 Zeichen für die zusätzlichen KI-Hinweise eingeben.'));
    }
    $tags = array_filter(array_map('trim', explode(',', $source['tags'] ?? '')));
    if (count($tags) > 12 || array_filter($tags, static fn(string $tag): bool => mb_strlen($tag) > 80 || strip_tags($tag) !== $tag)) {
      $form_state->setErrorByName('source][tags', $this->t('Bitte höchstens zwölf Schlagwörter mit jeweils maximal 80 Zeichen eintragen.'));
    }
  }

  /** Erstellt einen Vorschlag ohne Beitragsspeicherung oder Veröffentlichung. */
  public function generate(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('je_ai_submission.settings');
    $input = $form_state->getValue('source');
    $styles = array_column($config->get('styles'), NULL, 'id');
    $style = $styles[\Drupal\je_ai_submission\Service\StyleGroups::resolve($input['style'])] ?? NULL;
    if (!$style) {
      $this->messenger()->addError($this->t('Die gewählte Beitragsart ist nicht mehr vorhanden.'));
      $form_state->setRebuild();
      return;
    }
    $flood = \Drupal::service('flood');
    $identifier = (string) $this->currentUser()->id();
    if (!$flood->isAllowed('je_ai_submission.generate', $config->get('max_requests_per_hour'), 3600, $identifier)) {
      $this->messenger()->addError($this->t('Das stündliche Limit für KI-Vorschläge ist erreicht. Dein Text bleibt erhalten.'));
      $form_state->setRebuild();
      return;
    }
    $flood->register('je_ai_submission.generate', 3600, $identifier);
    $source = ['additional_instructions' => trim($input['additional_instructions'] ?? ''), 'title' => trim($input['title']), 'text' => trim($input['text']), 'tags' => ArticleSuggestion::mergeTags(array_filter(explode(',', $input['tags']), static fn(string $tag): bool => !preg_match('/^\s*\d{4}\s*$/', $tag))), 'excerpt' => trim($input['excerpt']), 'generate_excerpt' => (bool) $input['generate_excerpt'], 'generate_tags' => (bool) $input['generate_tags']];
    $existing = $config->get('tag_use_existing') ? array_column(\Drupal::service('je_ai_submission.tags')->frequent(80), 'name') : [];
    $previous = $form_state->get('suggestion');
    $change = trim($form_state->getValue('change') ?? '');
    $operation = $previous ? 'revise' : 'generate';
    $started = microtime(TRUE);
    $result = NULL;
    $reference = '';
    try {
      $result = \Drupal::service('je_ai_submission.assistant')->suggest($source, $style, $config->get('base_instructions'), ArticleSuggestion::mergeTags($config->get('preferred_tags'), $existing), $form_state->get('suggestion'), trim($form_state->getValue('change') ?? ''), ['limit' => $config->get('tag_limit'), 'include_location' => $config->get('tag_include_location'), 'instructions' => $config->get('tag_instructions')], \Drupal\je_ai_submission\Service\VisualStyles::context($style));
      $form_state->set('generation_failed', FALSE);
      $form_state->set('suggestion', $result);
      $form_state->set('suggestion_style_label', $style['label']);
      $form_state->set('suggestion_date', $input['date']->getTimestamp());
      $form_state->set('suggestion_signature', self::sourceSignature($input));
      $this->messenger()->addStatus($this->t('Der Vorschlag ist bereit. Bitte prüfe insbesondere Namen, Termine und Ergebnisse. Es wurde noch kein Beitrag gespeichert.'));
      \Drupal::logger('je_ai_submission')->notice('KI-Vorschlag erstellt. Konto @uid, Anbieter @provider, Modell @model, Stil @style.', ['@uid' => $identifier, '@provider' => $config->get('provider_id'), '@model' => $config->get('model_id'), '@style' => $style['id']]);
    }
    catch (\Throwable $error) {
      $form_state->set('generation_failed', TRUE);
      $reference = bin2hex(random_bytes(4));
      \Drupal::logger('je_ai_submission')->error('KI-Vorschlag fehlgeschlagen. Referenz @reference, Fehlerklasse @class, Code @code.', ['@reference' => $reference, '@class' => get_class($error), '@code' => $error->getCode()]);
      $this->messenger()->addError($this->t('Der KI-Vorschlag konnte nicht erstellt werden. Dein Ausgangstext und ein vorhandener Vorschlag bleiben erhalten. Bitte prüfe die Anbindung oder versuche es später erneut. Referenz: @reference', ['@reference' => $reference]));
    }
    try {
      $log_id = \Drupal::service('je_ai_submission.audit')->record($style['label'], $operation, (int) round((microtime(TRUE) - $started) * 1000), $source, $result, $change, $previous, $reference);
      if ($result !== NULL) {
        $form_state->set('suggestion_log_id', $log_id);
      }
    }
    catch (\Throwable $error) {
      \Drupal::logger('je_ai_submission')->error('KI-Redaktionsprotokoll konnte nicht gespeichert werden. Fehlerklasse @class.', ['@class' => get_class($error)]);
      $this->messenger()->addWarning($this->t('Der Vorgang konnte nicht im KI-Protokoll gespeichert werden. Ein vorhandener Vorschlag bleibt nutzbar.'));
    }
    $form_state->setRebuild();
  }

  /** Gibt das erneuerte Formular einschließlich Vorschau und Meldungen zurück. */
  public function ajaxRefresh(array &$form, FormStateInterface $form_state): array {
    return $form;
  }

  /** Übergibt die angezeigte Vorschau an einen ungespeicherten Drupal-Entwurf. */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $suggestion = $form_state->get('suggestion');
    $date = $form_state->get('suggestion_date');
    if (!$suggestion || !$date) {
      return;
    }
    if ($form_state->get('suggestion_signature') !== self::sourceSignature($form_state->getValue('source'))) {
      $this->messenger()->addWarning($this->t('Der Ausgangstext wurde nach der Vorschau geändert. Erstelle zuerst einen neuen Vorschlag oder stelle die ursprünglichen Eingaben wieder her.'));
      $form_state->setRebuild();
      return;
    }
    $token = \Drupal::service('je_ai_submission.drafts')->prepare($suggestion, $date, (int) $form_state->get('suggestion_log_id'), (string) ($form_state->get('suggestion_style_label') ?? ''));
    $form_state->setRedirect('je_ai_submission.review', ['token' => $token]);
  }

  /** Vergleicht den Eingabestand unabhängig von Formularobjekten. */
  private static function sourceSignature(array $source): string {
    $source['date'] = $source['date']->getTimestamp();
    return hash('sha256', json_encode($source, JSON_THROW_ON_ERROR));
  }

}
