<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Markup;
use Drupal\je_ai_submission\Service\IconCatalog;
use Drupal\je_ai_submission\Service\StyleGroups;

/** Hält Anbieterwahl und redaktionelle Vorgaben ausschließlich beim Admin. */
final class SettingsForm extends ConfigFormBase {

  /** {@inheritdoc} */
  public function getFormId(): string {
    return 'je_ai_submission_settings';
  }

  /** {@inheritdoc} */
  protected function getEditableConfigNames(): array {
    return ['je_ai_submission.settings'];
  }

  /** Zeigt keine Schlüsselwerte an, sondern verlinkt die native Anbieterpflege. */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $settings = $this->config('je_ai_submission.settings');
    $form['#attributes']['class'][] = 'je-ai-submission';
    $form['#prefix'] = '<div id="je-ai-settings-wrapper">';
    $form['#suffix'] = '</div>';
    $form['#attached']['library'][] = 'je_ai_submission/enhancements';
    $form['#attached']['drupalSettings']['jeAi']['animations'] = base_path() . \Drupal::service('extension.list.module')->getPath('je_ai_submission') . '/assets/lottie/';
    $form['guide'] = ['#type' => 'item', '#markup' => $this->t('Redaktionelle Vorgaben und KI-Anbindung. Änderungen an Stilgruppen und Regeln werden erst mit „Konfiguration speichern“ übernommen.')];
    $form['enabled'] = ['#type' => 'checkbox', '#title' => $this->t('KI-Beitragserstellung aktivieren'), '#default_value' => $settings->get('enabled')];
    $permissions = ['use je ai submission' => $this->t('KI nutzen'), 'view je ai submission log' => $this->t('Protokoll ansehen'), 'delete je ai submission log' => $this->t('Protokoll löschen'), 'administer je ai submission' => $this->t('Einstellungen')];
    $role_rows = [];
    foreach (\Drupal\user\Entity\Role::loadMultiple() as $role) {
      $row = [$role->label()];
      foreach ($permissions as $permission => $label) { $row[] = ($role->isAdmin() || $role->hasPermission($permission)) ? $this->t('Ja') : $this->t('Nein'); }
      $role_rows[] = $row;
    }
    $form['roles'] = ['#type' => 'details', '#title' => $this->t('Zugriff und Rollen'), '#open' => FALSE];
    $form['roles']['notice'] = ['#type' => 'item', '#plain_text' => $this->t('Die Rechte werden zentral in Drupal verwaltet und können jeder eigenen Rolle zugewiesen werden. Diese Übersicht zeigt die Freigaben je Rolle; mehrere Rollen eines Kontos ergänzen sich. Beitrags-, Textformat- und Moderationsrechte gelten zusätzlich. Protokoll-Leserechte erlauben Zugriff auf alle gespeicherten Textvergleiche. Löschen benötigt zusätzlich das Löschrecht.')];
    $form['roles']['table'] = ['#prefix' => '<div class="je-ai-role-table">', '#suffix' => '</div>', '#type' => 'table', '#header' => array_merge([$this->t('Rolle')], array_values($permissions)), '#rows' => $role_rows];
    $form['roles']['permissions'] = ['#type' => 'link', '#title' => $this->t('Berechtigungen in Drupal bearbeiten'), '#url' => Url::fromRoute('user.admin_permissions'), '#access' => $this->currentUser()->hasPermission('administer permissions')];
    $form['notifications'] = ['#type' => 'details', '#title' => $this->t('Einreichung und Benachrichtigungen'), '#tree' => TRUE, '#open' => TRUE];
    $form['notifications']['completion_action'] = ['#type' => 'select', '#title' => $this->t('Abschluss im Beitragseditor'), '#options' => ['review' => $this->t('Zur Prüfung einreichen'), 'publish' => $this->t('Direkt veröffentlichen')], '#default_value' => $settings->get('completion_action') ?? 'review', '#required' => TRUE, '#description' => $this->t('Es wird nur der gewählte Abschlussweg angeboten. Als Entwurf speichern bleibt immer möglich. Direkte Veröffentlichung benötigt normale Drupal-Veröffentlichungsrechte und alle Pflichtangaben der Website. Die aktivierten Benachrichtigungen werden beim gewählten Abschluss versandt, beim Entwurfsspeichern nicht.')];
    $form['notifications']['notify_enabled'] = ['#type' => 'checkbox', '#title' => $this->t('Benachrichtigung nach Veröffentlichung oder Einreichung versenden'), '#default_value' => $settings->get('notify_enabled') ?? FALSE];
    $form['notifications']['notify_users'] = ['#type' => 'entity_autocomplete', '#title' => $this->t('Benachrichtigte Benutzerkonten'), '#target_type' => 'user', '#tags' => TRUE, '#default_value' => \Drupal::entityTypeManager()->getStorage('user')->loadMultiple($settings->get('notify_users') ?? []), '#description' => $this->t('Bis zu 20 Konten. Versand einzeln an die hinterlegten Adressen aktiver Konten; keine offenen Empfängerlisten. Änderungen speichern.')];
    $form['notifications']['notify_author'] = ['#type' => 'checkbox', '#title' => $this->t('Autor zusätzlich benachrichtigen'), '#default_value' => $settings->get('notify_author') ?? FALSE];
    $form['notifications']['review_state'] = ['#type' => 'textfield', '#title' => $this->t('Kennung des Moderationszustands zur Prüfung'), '#default_value' => $settings->get('review_state') ?? 'review', '#required' => TRUE, '#maxlength' => 64, '#description' => $this->t('Muss im Workflow des Beitragstyps als unveröffentlichter Zustand bestehen, beispielsweise review. Der Übergang benötigt normale Drupal-Moderationsrechte. Entwurf speichern löst keine Mail aus.')];
    $header = $settings->get('mail_header_uuid') ? \Drupal::service('entity.repository')->loadEntityByUuid('file', $settings->get('mail_header_uuid')) : NULL;
    $form['notifications']['mail_header'] = ['#type' => 'managed_file', '#title' => $this->t('Eigene Mailheader-Grafik'), '#default_value' => $header ? [$header->id()] : [], '#upload_location' => 'public://je-ai-submission-mail/', '#upload_validators' => ['FileExtension' => ['extensions' => 'png jpg jpeg'], 'FileSizeLimit' => ['fileLimit' => 2097152], 'FileIsImage' => []], '#description' => $this->t('Optional: PNG oder JPEG bis 2 MB, empfohlen etwa 1200 Pixel breit. Ohne eigene Grafik nutzt die Standardvorlage das AI-Beitragseinreichung-Banner aus dem Modul. Die Datei liegt in den Website-Uploads und wird nicht mit dem Modul veröffentlicht. Eine eigene Website-Mailvorlage kann das gesamte Layout unabhängig davon ersetzen.')];
    $providers = [];
    foreach (\Drupal::service('ai.provider')->getProvidersForOperationType('chat', FALSE) as $id => $definition) {
      $providers[$id] = (string) $definition['label'];
    }
    $provider_id = $form_state->getValue('provider_id') ?? $settings->get('provider_id');
    $catalog = \Drupal::service('je_ai_submission.models')->stored($provider_id);
    $options = $catalog['models'];
    $current = $settings->get('model_id');
    if ($provider_id === $settings->get('provider_id') && $current && !isset($options[$current])) {
      $options[$current] = $this->t('@model (aktuell gespeichert)', ['@model' => $current]);
    }
    $options['__custom'] = $this->t('Andere Modellkennung eingeben');
    $form['provider_id'] = ['#type' => 'select', '#title' => $this->t('KI-Anbieter'), '#options' => $providers, '#default_value' => $settings->get('provider_id'), '#required' => TRUE, '#ajax' => ['callback' => '::ajaxRefresh', 'wrapper' => 'je-ai-settings-wrapper']];
    $form['model_choice'] = ['#type' => 'select', '#title' => $this->t('KI-Modell'), '#options' => $options, '#default_value' => isset($options[$current]) ? $current : '__custom', '#required' => TRUE, '#description' => $this->t('Gespeicherte Chat-Modellliste des Anbieters. Nicht jedes Chat-Modell unterstützt die benötigten strukturierten Antworten. Das bisherige Modell bleibt erhalten; ein Modellwechsel erfolgt erst beim Speichern.')];
    $form['model_custom'] = ['#type' => 'textfield', '#title' => $this->t('Eigene Modellkennung'), '#default_value' => $current, '#maxlength' => 120, '#states' => ['visible' => [':input[name="model_choice"]' => ['value' => '__custom']]], '#description' => $this->t('Für neue Modelle, die in der Liste noch fehlen. Die Kennung muss beim ausgewählten Anbieter verfügbar sein und strukturierte Antworten unterstützen.')];
    $form['model_refresh'] = ['#type' => 'submit', '#name' => 'model_refresh', '#value' => $this->t('Modellliste aktualisieren'), '#submit' => ['::refreshModels'], '#limit_validation_errors' => [['provider_id']], '#ajax' => ['callback' => '::ajaxRefresh', 'wrapper' => 'je-ai-settings-wrapper', 'progress' => ['type' => 'throbber', 'message' => $this->t('Modellliste wird geladen …')]]];
    $form['model_updated'] = ['#type' => 'item', '#plain_text' => $catalog['updated'] ? $this->t('Gespeicherte Liste vom @date. Beim Öffnen oder Speichern erfolgt keine Modellabfrage.', ['@date' => \Drupal::service('date.formatter')->format($catalog['updated'], 'custom', 'd.m.Y H:i')]) : $this->t('Noch keine Liste geladen. Die aktuelle Modellkennung bleibt verfügbar. Nur „Modellliste aktualisieren“ fragt den Anbieter ab.')];
    if (\Drupal::moduleHandler()->moduleExists('ai_provider_openai')) {
      $form['provider_setup'] = ['#type' => 'link', '#title' => $this->t('OpenAI-Zugang in Drupal AI einrichten'), '#url' => Url::fromRoute('ai_provider_openai.settings_form')];
    }
    $form['connection_test'] = ['#type' => 'submit', '#name' => 'connection_test', '#value' => $this->t('Verbindung jetzt testen'), '#submit' => ['::testConnection'], '#limit_validation_errors' => [['provider_id'], ['model_choice'], ['model_custom']], '#ajax' => ['callback' => '::ajaxRefresh', 'wrapper' => 'je-ai-settings-wrapper', 'progress' => ['type' => 'throbber', 'message' => $this->t('Strukturierte Antwort wird getestet …')]], '#description' => $this->t('Sendet nur einen kurzen technischen Testtext, keine Beitragsinhalte. Ein API-Aufruf kann Kosten verursachen. Speichert keine Einstellungen.')];
    $form['connection_note'] = ['#type' => 'item', '#plain_text' => $this->t('Der Verbindungstest sendet ausschließlich einen kurzen technischen Testtext. Er kann API-Kosten verursachen und speichert keine Einstellungen.')];
    $form['base_instructions'] = ['#type' => 'textarea', '#title' => $this->t('Grundstil'), '#default_value' => $settings->get('base_instructions'), '#rows' => 4, '#maxlength' => 20000, '#required' => TRUE];
    $form['preferred_tags'] = ['#type' => 'textarea', '#title' => $this->t('Bevorzugte Schlagwörter'), '#attributes' => ['data-je-tags' => '1', 'data-je-tag-pool' => '1'], '#maxlength' => 16200, '#default_value' => implode("\n", $settings->get('preferred_tags')), '#rows' => 5, '#description' => $this->t('Begriffe einzeln oder mit Komma hinzufügen und mit Enter bestätigen. Kacheln lassen sich einzeln entfernen. Änderungen erst mit „Konfiguration speichern“ übernehmen. Maximal 200 Begriffe mit je 80 Zeichen; Jahreszahlen werden aus dem Beitragsdatum zugeordnet. Ohne JavaScript: ein Begriff pro Zeile.')];
    $options = [];
    $pool = array_map('mb_strtolower', array_filter(array_map('trim', preg_split('/\R/', $form_state->getUserInput()['preferred_tags'] ?? implode("\n", $settings->get('preferred_tags'))))));
    $frequent = \Drupal::service('je_ai_submission.tags')->frequent(50);
    foreach ($frequent as $id => $term) { $options[$id] = $this->formatPlural($term['count'], '@name · 1 veröffentlichter Beitrag', '@name · @count veröffentlichte Beiträge', ['@name' => $term['name']]); }
    $form['frequent_tags'] = ['#type' => 'details', '#title' => $this->t('Vorhandene Schlagwörter in den KI-Pool übernehmen'), '#tree' => TRUE, '#open' => $form_state->get('tag_pool_open') ?? FALSE];
    $form['frequent_tags']['help'] = ['#type' => 'item', '#plain_text' => $this->t('Die 50 am häufigsten verwendeten Begriffe, sortiert nach der Anzahl veröffentlichter Beiträge. Bereits enthaltene Begriffe sind markiert. Die Auswahl ergänzt zunächst nur das Formular; erst „Konfiguration speichern“ speichert den KI-Pool. Entwürfe und Revisionen erhöhen die Zahlen nicht.')];
    $form['frequent_tags']['search'] = ['#type' => 'search', '#title' => $this->t('Angezeigte Schlagwörter filtern'), '#attributes' => ['data-je-tag-search' => '1']];
    $form['frequent_tags']['selected'] = ['#type' => 'checkboxes', '#title' => $this->t('Schlagwörter auswählen'), '#options' => $options];
    foreach ($frequent as $id => $term) {
      if (in_array(mb_strtolower($term['name']), $pool, TRUE)) { $form['frequent_tags']['selected'][$id] = ['#disabled' => TRUE, '#title' => $options[$id] . ' · ' . $this->t('bereits im Pool')]; }
    }
    $form['frequent_tags']['add'] = ['#type' => 'submit', '#name' => 'tag_pool_add', '#value' => $this->t('Ausgewählte Schlagwörter übernehmen'), '#submit' => ['::addFrequentTags'], '#limit_validation_errors' => [['frequent_tags', 'selected'], ['preferred_tags']], '#ajax' => ['callback' => '::ajaxRefresh', 'wrapper' => 'je-ai-settings-wrapper']];
    $form['tag_rules'] = ['#type' => 'details', '#title' => $this->t('Schlagwortregeln für die KI'), '#open' => FALSE, '#tree' => TRUE];
    $form['tag_rules']['tag_limit'] = ['#type' => 'number', '#title' => $this->t('Maximale zusätzliche KI-Schlagwörter'), '#default_value' => $settings->get('tag_limit') ?? 8, '#min' => 1, '#max' => 12, '#required' => TRUE];
    $form['tag_rules']['tag_use_existing'] = ['#type' => 'checkbox', '#title' => $this->t('Vorhandene Schlagwörter als Orientierung berücksichtigen'), '#default_value' => $settings->get('tag_use_existing') ?? TRUE];
    $form['tag_rules']['tag_include_location'] = ['#type' => 'checkbox', '#title' => $this->t('Eindeutig erkennbaren Ort als Schlagwort berücksichtigen'), '#default_value' => $settings->get('tag_include_location') ?? TRUE, '#description' => $this->t('Nur wenn der Ort aus dem Ausgangstext hervorgeht. Kein Erfinden oder Ableiten unbekannter Orte.')];
    $form['tag_rules']['tag_instructions'] = ['#type' => 'textarea', '#title' => $this->t('Zusätzliche Schlagwortregeln'), '#rows' => 3, '#maxlength' => 4000, '#default_value' => $settings->get('tag_instructions'), '#description' => $this->t('Wird bei jeder KI-Erstellung und Überarbeitung berücksichtigt. Die Regeln dürfen keine fehlenden Fakten ergänzen lassen.')];
    $form['visual_enabled'] = ['#type' => 'checkbox', '#title' => $this->t('Gestaltungsstile durch die KI verwenden'), '#default_value' => $settings->get('visual_enabled') ?? FALSE, '#description' => $this->t('Standardmäßig deaktiviert. Zusätzlich je Stilgruppe freigeben. Die manuelle Auswahl bleibt unabhängig verfügbar.')];
    $form['visual_pool_link'] = ['#type' => 'link', '#title' => $this->t('Symbolpool verwalten und Vorschauen ansehen'), '#url' => Url::fromRoute('je_ai_submission.visual_pool')];
    $visual_options = [];
    foreach ($settings->get('visual_pool') ?? [] as $symbol) {
      $preview = IconCatalog::preview($symbol['icon']);
      $image = $preview ? '<img src="' . Html::escape($preview) . '" width="28" height="28" alt="" loading="lazy">' : '';
      $label = Html::escape($symbol['label']);
      $status = empty($symbol['ai']) ? '<small>' . Html::escape((string) $this->t('KI-Freigabe im Pool fehlt')) . '</small>' : '';
      $visual_options[$symbol['id']] = Markup::create($image . '<span>' . $label . $status . '</span>');
    }
    $form['styles'] = ['#type' => 'details', '#title' => $this->t('Stilgruppen'), '#open' => TRUE, '#tree' => TRUE];
    $styles = $form_state->get('style_draft') ?? $settings->get('styles');
    $icons = StyleGroups::icons($settings->get('visual_pool') ?? [], $styles);
    $icon_options = $icons + ['__custom' => $this->t('Anderen Lucide-Namen eingeben')];
    StyleGroups::attach($form, array_keys($icons));
    $styles[] = ['id' => '', 'label' => '', 'instructions' => ''];
    foreach ($styles as $index => $style) {
      $form['styles'][$index] = ['#type' => 'details', '#title' => StyleGroups::label($style['label'] ?: (string) $this->t('Neue Stilgruppe'), $style['icon'] ?? 'file-text'), '#open' => $form_state->get('style_open_index') === $index];
      $form['styles'][$index]['id'] = ['#type' => 'hidden', '#value' => $style['id']];
      $form['styles'][$index]['label'] = ['#type' => 'textfield', '#title' => $this->t('Name'), '#default_value' => $style['label'], '#maxlength' => 100];
      $form['styles'][$index]['icon_choice'] = ['#type' => 'select', '#title' => $this->t('Symbol der Beitragsart'), '#options' => $icon_options, '#default_value' => $style['icon'] ?? 'file-text', '#attributes' => ['data-je-icon-select' => '1'], '#description' => $this->t('Erkennungszeichen in Einstellungen und Beitragsauswahl. Unabhängig von den im Artikel erlaubten Symbolen.')];
      $form['styles'][$index]['icon_custom'] = ['#type' => 'textfield', '#title' => $this->t('Weiterer Lucide-Symbolname'), '#default_value' => '', '#maxlength' => 80, '#states' => ['visible' => [':input[name="styles[' . $index . '][icon_choice]"]' => ['value' => '__custom']]], '#description' => $this->t('Zum Beispiel search. Wird beim Speichern lokal gegen den mitgelieferten Katalog geprüft.')];
      $form['styles'][$index]['instructions'] = ['#type' => 'textarea', '#title' => $this->t('Stilvorgaben'), '#default_value' => $style['instructions'], '#maxlength' => 10000, '#rows' => 3];
      $form['styles'][$index]['visual_enabled'] = ['#type' => 'checkbox', '#title' => $this->t('Gestaltung für diese Stilgruppe aktivieren'), '#default_value' => $style['visual_enabled'] ?? FALSE];
      $form['styles'][$index]['visual_instructions'] = ['#type' => 'textarea', '#title' => $this->t('Aufbau und Einsatz der Symbole'), '#rows' => 3, '#maxlength' => 4000, '#default_value' => $style['visual_instructions'] ?? '', '#description' => $this->t('Zum Beispiel: Aussagekräftige Zwischenüberschriften mit passenden Symbolen; belegte Podestplätze und Zeiten hervorheben. Farben und Abstände stammen aus der Website.')];
      $form['styles'][$index]['visual_symbols'] = ['#type' => 'checkboxes', '#title' => $this->t('Erlaubte Symbole dieser Stilgruppe'), '#options' => $visual_options, '#default_value' => $style['visual_symbols'] ?? [], '#attributes' => ['class' => ['je-ai-symbol-choices']], '#description' => $this->t('Symbole einzeln an- oder abwählen. Die Auswahl bleibt erhalten. Zusätzlich muss jedes Symbol im Pool für die KI freigegeben sein.')];
      $form['styles'][$index]['pool_link'] = ['#type' => 'link', '#title' => $this->t('Symbole im Pool verwalten'), '#url' => Url::fromRoute('je_ai_submission.visual_pool'), '#attributes' => ['target' => '_blank', 'rel' => 'noopener'], '#suffix' => '<p class="description">' . $this->t('Öffnet einen neuen Tab. Änderungen an dieser Stilgruppe vorher speichern.') . '</p>'];
      $form['styles'][$index]['visual_lead'] = ['#type' => 'checkbox', '#title' => $this->t('Einleitung hervorheben'), '#default_value' => $style['visual_lead'] ?? FALSE];
      $form['styles'][$index]['visual_table'] = ['#type' => 'checkbox', '#title' => $this->t('Mehrere Ergebnisse als gestaltete Tabelle'), '#default_value' => $style['visual_table'] ?? FALSE];
      if ($style['id']) {
        $form['styles'][$index]['controls'] = ['#type' => 'container', '#attributes' => ['class' => ['je-ai-style-actions']]];
        foreach (['up' => 'Nach oben', 'down' => 'Nach unten', 'duplicate' => 'Duplizieren', 'remove' => 'Entfernen'] as $action => $label) {
          $form['styles'][$index]['controls'][$action] = ['#type' => 'submit', '#name' => 'style_' . $action . '_' . $index, '#value' => $this->t($label), '#je_action' => $action, '#je_index' => $index, '#submit' => ['::manageStyle'], '#limit_validation_errors' => [], '#ajax' => ['callback' => '::ajaxRefresh', 'wrapper' => 'je-ai-settings-wrapper']];
        }
        if (in_array($style['id'], array_column($settings->get('styles'), 'id'), TRUE)) {
          $form['styles'][$index]['controls']['export'] = ['#type' => 'link', '#title' => $this->t('Gespeicherte Gruppe exportieren'), '#url' => Url::fromRoute('je_ai_submission.style_export', ['style' => $style['id']])];
        }
      }

    }
    $form['transfer'] = ['#type' => 'details', '#title' => $this->t('Stilgruppen importieren / exportieren'), '#open' => (bool) $form_state->get('style_import_preview')];
    $form['transfer']['export'] = ['#type' => 'link', '#title' => $this->t('Alle gespeicherten Stilgruppen exportieren'), '#url' => Url::fromRoute('je_ai_submission.style_export')];
    $form['transfer']['help'] = ['#type' => 'item', '#plain_text' => $this->t('Kompatibel mit den JSON-Exporten des Original-Plugins, einzeln oder gesammelt. Importe ergänzen bestehende Gruppen; gleiche Namen erhalten einen Zusatz. Erst prüfen, dann übernehmen und zuletzt Konfiguration speichern. Exporte enthalten den gespeicherten Stand.')];
    $form['transfer']['file'] = ['#type' => 'html_tag', '#tag' => 'input', '#attributes' => ['type' => 'file', 'accept' => '.json,application/json', 'aria-label' => 'Stilgruppen-JSON auswählen', 'data-je-style-file' => '1']];
    $form['transfer']['style_json'] = ['#type' => 'textarea', '#title' => $this->t('Import-JSON'), '#rows' => 3, '#maxlength' => 512000, '#attributes' => ['data-je-style-json' => '1'], '#description' => $this->t('Datei auswählen oder JSON hier einfügen. Höchstens 500 KB.')];
    $form['transfer']['style_import_check'] = ['#type' => 'submit', '#name' => 'style_import_check', '#value' => $this->t('Import prüfen'), '#submit' => ['::checkImport'], '#limit_validation_errors' => [], '#ajax' => ['callback' => '::ajaxRefresh', 'wrapper' => 'je-ai-settings-wrapper']];
    if ($pending = $form_state->get('style_import_preview')) {
      $form['transfer']['import_preview'] = ['#type' => 'details', '#title' => $this->t('Importvorschau – noch nicht übernommen'), '#open' => TRUE];
      foreach ($pending as $index => $row) {
        $form['transfer']['import_preview'][$index] = ['#type' => 'details', '#title' => $row['label'], 'text' => ['#type' => 'item', '#plain_text' => $row['instructions']]];
      }
      $form['transfer']['style_import_apply'] = ['#type' => 'submit', '#name' => 'style_import_apply', '#value' => $this->t('Geprüfte Gruppen übernehmen'), '#submit' => ['::applyImport'], '#limit_validation_errors' => [], '#ajax' => ['callback' => '::ajaxRefresh', 'wrapper' => 'je-ai-settings-wrapper']];
    }
    $form['logging'] = ['#type' => 'details', '#title' => $this->t('KI-Protokoll'), '#open' => TRUE, '#tree' => TRUE];
    $form['logging']['log_content'] = ['#type' => 'checkbox', '#title' => $this->t('Ausgangstext und KI-Ergebnis im Admin-Protokoll speichern'), '#default_value' => $settings->get('log_content'), '#description' => $this->t('Speichert auch den vorherigen Vorschlag und Änderungswunsch zum Vergleich. Nur Konten mit ausdrücklich vergebenem Protokoll-Leserecht können die Vergleiche lesen. Ausschalten betrifft zukünftige Vorgänge; vorhandene Vergleiche bleiben bis zur Aufbewahrungsgrenze erhalten.')];
    $form['logging']['log_limit'] = ['#type' => 'number', '#title' => $this->t('Maximal gespeicherte KI-Vorgänge'), '#min' => 1, '#max' => 500, '#default_value' => $settings->get('log_limit') ?? 100, '#required' => TRUE, '#description' => $this->t('Ältere Vorgänge werden automatisch entfernt. Standard: 100.')];
    $form['limits'] = ['#type' => 'details', '#title' => $this->t('Nutzungsgrenzen'), '#open' => FALSE, '#tree' => TRUE];
    $form['limits']['max_requests_per_hour'] = ['#type' => 'number', '#title' => $this->t('KI-Anfragen je Konto und Stunde'), '#min' => 1, '#max' => 200, '#default_value' => $settings->get('max_requests_per_hour'), '#required' => TRUE];
    $form['limits']['min_input_words'] = ['#type' => 'number', '#title' => $this->t('Mindestanzahl Wörter im Ausgangstext'), '#min' => 1, '#max' => 1000, '#default_value' => $settings->get('min_input_words') ?? 30, '#required' => TRUE, '#description' => $this->t('Unterhalb dieser Grenze wird keine KI-Anfrage gesendet. Standard: 30 Wörter.')];
    $form['limits']['max_input_chars'] = ['#type' => 'number', '#title' => $this->t('Maximale Zeichen im Ausgangstext'), '#min' => 100, '#max' => 100000, '#default_value' => $settings->get('max_input_chars'), '#required' => TRUE];
    $form['visual_pool_link']['#attributes']['class'] = ['button', 'button--small'];
    $form['visual_pool_link']['#attributes']['target'] = '_blank';
    $form['visual_pool_link']['#attributes']['rel'] = 'noopener';
    $form['visual_pool_link']['#suffix'] = '<p class="description">' . $this->t('Öffnet einen neuen Tab. Der Pool hat eine eigene Speicherung; Änderungen hier zuvor speichern.') . '</p>';
    $sections = [
      'connection' => ['KI-Anbindung', ['enabled', 'provider_id', 'model_choice', 'model_custom', 'model_refresh', 'model_updated', 'provider_setup', 'connection_test', 'connection_note']],
      'writing' => ['Schreibstil und Schlagwörter', ['base_instructions', 'preferred_tags', 'frequent_tags', 'tag_rules']],
      'presentation' => ['Stilgruppen und Symbole', ['visual_enabled', 'visual_pool_link', 'styles', 'transfer']],
    ];
    $weight = 0;
    $form['guide']['#weight'] = $weight++;
    foreach ($sections as $key => [$title, $fields]) {
      $form[$key] = ['#type' => 'fieldset', '#title' => $this->t($title), '#tree' => FALSE, '#weight' => $weight++, '#attributes' => ['class' => ['je-ai-settings-section']]];
      foreach ($fields as $field) {
        if (isset($form[$field])) {
          $form[$key][$field] = $form[$field];
          unset($form[$field]);
        }
      }
    }
    $form['presentation']['styles']['#type'] = 'container';
    unset($form['presentation']['styles']['#title'], $form['presentation']['styles']['#open']);
    $form['notifications']['#type'] = 'fieldset';
    $form['notifications']['#weight'] = $weight++;
    $form['notifications']['#attributes']['class'][] = 'je-ai-settings-section';
    $form['logging']['#type'] = 'fieldset';
    $form['logging']['#title'] = $this->t('Protokoll und Nutzungsgrenzen');
    $form['logging']['#weight'] = $weight++;
    $form['logging']['#attributes']['class'][] = 'je-ai-settings-section';
    $form['logging']['limits'] = $form['limits'];
    $form['logging']['limits']['#parents'] = ['limits'];
    unset($form['limits']);
    $form['roles']['#weight'] = $weight++;
    $form['actions']['#weight'] = $weight;
    return parent::buildForm($form, $form_state);
  }

  /** Prüft Stilkennungen und Zugang vor Aktivierung ohne kostenpflichtigen Testaufruf. */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
    $trigger = $form_state->getTriggeringElement()['#name'] ?? '';
    if (in_array($trigger, ['model_refresh', 'provider_id', 'connection_test', 'tag_pool_add'], TRUE) || str_starts_with($trigger, 'style_')) {
      return;
    }
    $choice = $form_state->getValue('model_choice');
    $model = trim($choice === '__custom' ? ($form_state->getValue('model_custom') ?? '') : ($choice ?? ''));
    $form_state->setValue('model_id', $model);
    $notifications = $form_state->getValue('notifications') ?? [];
    if (!in_array($notifications['completion_action'] ?? 'review', ['review', 'publish'], TRUE)) {
      $form_state->setErrorByName('notifications][completion_action', $this->t('Bitte einen gültigen Abschlussweg auswählen.'));
    }
    if (($notifications['completion_action'] ?? 'review') === 'publish') {
      $example = \Drupal::entityTypeManager()->getStorage('node')->create(['type' => $this->config('je_ai_submission.settings')->get('content_type')]);
      $workflow = \Drupal::service('content_moderation.moderation_information')->getWorkflowForEntity($example);
      if (!$workflow || !array_filter($workflow->getTypePlugin()->getStates(), static fn($state): bool => $state->isPublishedState())) {
        $form_state->setErrorByName('notifications][completion_action', $this->t('Für direkte Veröffentlichung wird ein veröffentlichter Zustand im Workflow des Beitragstyps benötigt.'));
      }
    }
    if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $notifications['review_state'] ?? '')) {
      $form_state->setErrorByName('notifications][review_state', $this->t('Bitte eine gültige Zustandskennung eingeben.'));
    }
    if (count($notifications['notify_users'] ?? []) > 20) {
      $form_state->setErrorByName('notifications][notify_users', $this->t('Bitte höchstens 20 Empfängerkonten auswählen.'));
    }
    if (!empty($notifications['notify_enabled']) && empty($notifications['notify_users']) && empty($notifications['notify_author'])) {
      $form_state->setErrorByName('notifications][notify_users', $this->t('Bitte mindestens ein Empfängerkonto oder die Autorenbestätigung auswählen.'));
    }
    if (($notifications['completion_action'] ?? 'review') === 'review' && !empty($notifications['notify_enabled'])) {
      $example = \Drupal::entityTypeManager()->getStorage('node')->create(['type' => $this->config('je_ai_submission.settings')->get('content_type')]);
      $workflow = \Drupal::service('content_moderation.moderation_information')->getWorkflowForEntity($example);
      $target = $notifications['review_state'] ?? '';
      if (!$workflow || !$workflow->getTypePlugin()->hasState($target) || $workflow->getTypePlugin()->getState($target)->isPublishedState() || $target === 'draft') {
        $form_state->setErrorByName('notifications][review_state', $this->t('Bitte einen vorhandenen unveröffentlichten Prüfzustand außerhalb des Entwurfszustands auswählen.'));
      }
    }
    if ($model && !preg_match('/^[a-zA-Z0-9_.:\/-]{1,120}$/D', $model)) {
      $form_state->setErrorByName('model_custom', $this->t('Bitte eine gültige Modellkennung eingeben.'));
    }
    if (mb_strlen($form_state->getValue('base_instructions') ?? '') > 20000) {
      $form_state->setErrorByName('base_instructions', $this->t('Der Grundstil darf höchstens 20.000 Zeichen enthalten.'));
    }
    $tags = array_values(array_filter(array_map('trim', preg_split('/\R/', $form_state->getValue('preferred_tags') ?? ''))));
    if (count($tags) > 200 || array_filter($tags, static fn(string $tag): bool => mb_strlen($tag) > 80 || strip_tags($tag) !== $tag || preg_match('/^\d{4}$/D', $tag))) {
      $form_state->setErrorByName('preferred_tags', $this->t('Bitte höchstens 200 Schlagwörter mit je maximal 80 Zeichen ohne HTML oder reine Jahreszahlen eingeben. Das Jahr wird aus dem Beitragsdatum zugeordnet.'));
    }
    $styles = [];
    foreach ($form_state->getValue('styles') as $index => $style) {
      $icon = self::groupIcon($style);
      if (($style['label'] ?? '') && !IconCatalog::has($icon)) { $form_state->setErrorByName('styles][' . $index . '][icon_custom', $this->t('Bitte einen vorhandenen Lucide-Symbolnamen auswählen.')); }
      $visual = self::visualGroup($style);
      $style = array_intersect_key($style, array_flip(['id', 'label', 'instructions']));
      $style = array_map(static fn(string $value): string => trim(str_replace(["\r\n", "\r"], "\n", $value)), $style);
      if (mb_strlen($style['instructions']) > 10000) {
        $form_state->setErrorByName('styles][' . $index . '][instructions', $this->t('Eine Stilgruppe darf höchstens 10.000 Zeichen enthalten. Bitte auf beschädigte oder wiederholte Zeichen prüfen.'));
      }
      if (!array_filter($style)) {
        continue;
      }
      if (!$style['id'] && $style['label']) { $style['id'] = 'style_' . substr(hash('sha256', $style['label'] . $style['instructions']), 0, 12); }
      if (!preg_match('/^[a-z][a-z0-9_]*$/', $style['id']) || !$style['label'] || !$style['instructions'] || isset($styles[$style['id']])) {
        $form_state->setErrorByName('styles][' . $index, $this->t('Jede Stilgruppe benötigt eine eindeutige gültige Kennung, einen Namen und Stilvorgaben.'));
      }
      $styles[$style['id']] = $style + $visual;
    }
    if (count($styles) > 50) {
      $form_state->setErrorByName('styles', $this->t('Höchstens 50 Stilgruppen speichern.'));
    }
    if (mb_strlen($form_state->getValue(['tag_rules', 'tag_instructions']) ?? '') > 4000) {
      $form_state->setErrorByName('tag_rules][tag_instructions', $this->t('Schlagwortregeln bitte auf 4.000 Zeichen begrenzen.'));
    }
    if (!$styles) {
      $form_state->setErrorByName('styles', $this->t('Bitte mindestens eine Stilgruppe festlegen.'));
    }
    $form_state->set('normalized_styles', array_values($styles));
    if ($form_state->getValue('enabled')) {
      if (!trim($form_state->getValue('model_id'))) {
        $form_state->setErrorByName('model_custom', $this->t('Zum Aktivieren bitte eine Modellkennung eintragen.'));
      }
      $provider = \Drupal::service('ai.provider')->createInstance($form_state->getValue('provider_id'));
      if (!$provider->isUsable('chat')) {
        $form_state->setErrorByName('provider_id', $this->t('Der Anbieterzugang ist noch nicht eingerichtet.'));
      }
    }
  }

  /** Ergänzt ausgewählte vorhandene Begriffe nur im ungespeicherten Formular. */
  public function addFrequentTags(array &$form, FormStateInterface $form_state): void {
    $tags = array_filter(array_map('trim', preg_split('/\R/', $form_state->getValue('preferred_tags') ?? '')));
    $available = \Drupal::service('je_ai_submission.tags')->frequent(50);
    foreach (array_filter($form_state->getValue(['frequent_tags', 'selected']) ?? []) as $id) {
      if (isset($available[$id]) && mb_strlen($available[$id]['name']) <= 80 && strip_tags($available[$id]['name']) === $available[$id]['name']) { $tags[] = $available[$id]['name']; }
    }
    $tags = \Drupal\je_ai_submission\Service\ArticleSuggestion::mergeTags($tags);
    if (count($tags) > 200) { $this->messenger()->addError($this->t('Die Auswahl überschreitet die Grenze von 200 Begriffen. Es wurde nichts übernommen.')); }
    else {
      $input = $form_state->getUserInput();
      $input['preferred_tags'] = implode("\n", $tags);
      $input['frequent_tags']['selected'] = [];
      $form_state->setUserInput($input)->setValue('preferred_tags', $input['preferred_tags']);
      $this->messenger()->addStatus($this->t('Auswahl im Formular ergänzt. Bitte Konfiguration speichern, um den KI-Pool dauerhaft zu übernehmen.'));
    }
    $form_state->set('tag_pool_open', TRUE)->setRebuild();
  }

  /** Aktualisiert die Modellliste nur auf ausdrücklichen Klick. */
  public function refreshModels(array &$form, FormStateInterface $form_state): void {
    try {
      $entry = \Drupal::service('je_ai_submission.models')->refresh($form_state->getValue('provider_id'));
      $this->messenger()->addStatus($this->t('@count Chat-Modelle geladen. Die Einstellungen wurden noch nicht gespeichert.', ['@count' => count($entry['models'])]));
    }
    catch (\Throwable $error) {
      \Drupal::logger('je_ai_submission')->warning('Modellliste konnte nicht geladen werden. Fehlerklasse @class.', ['@class' => get_class($error)]);
      $this->messenger()->addError($this->t('Die Modellliste konnte nicht geladen werden. Die bisherige Liste und Modellwahl bleiben erhalten. Bitte den Anbieterzugang prüfen oder später erneut versuchen.'));
    }
    $form_state->setRebuild();
  }

  /** Prüft Anbieter und ausgewähltes Modell mit minimalen synthetischen Daten. */
  public function testConnection(array &$form, FormStateInterface $form_state): void {
    $choice = $form_state->getValue('model_choice');
    $model = $choice === '__custom' ? trim($form_state->getValue('model_custom') ?? '') : $choice;
    try {
      if (!$model || !preg_match('/^[a-zA-Z0-9_.:\/-]{1,120}$/D', $model)) {
        throw new \RuntimeException('Ungültige Modellkennung.');
      }
      $provider = \Drupal::service('ai.provider')->createInstance($form_state->getValue('provider_id'));
      if (!$provider->isUsable('chat')) {
        throw new \RuntimeException('Anbieter nicht eingerichtet.');
      }
      $input = new \Drupal\ai\OperationType\Chat\ChatInput([new \Drupal\ai\OperationType\Chat\ChatMessage('user', 'Interner Verbindungstest. Antworte exakt mit {"ok":true}.')]);
      $input->setChatStructuredJsonSchema(['name' => 'connection_check', 'strict' => TRUE, 'schema' => ['type' => 'object', 'properties' => ['ok' => ['type' => 'boolean']], 'required' => ['ok'], 'additionalProperties' => FALSE]]);
      $started = microtime(TRUE);
      $output = $provider->chat($input, $model, ['je_ai_submission_connection_test'])->getNormalized()->getText();
      if (json_decode($output, TRUE, 8, JSON_THROW_ON_ERROR) !== ['ok' => TRUE]) {
        throw new \RuntimeException('Keine gültige strukturierte Testantwort.');
      }
      $this->messenger()->addStatus($this->t('Verbindung erfolgreich: @model hat eine gültige strukturierte Antwort geliefert (@seconds s). Die Einstellungen wurden nicht gespeichert.', ['@model' => $model, '@seconds' => number_format(microtime(TRUE) - $started, 1, ',', '.')]));
    }
    catch (\Throwable $error) {
      $reference = bin2hex(random_bytes(4));
      \Drupal::logger('je_ai_submission')->warning('Verbindungstest fehlgeschlagen. Referenz @ref, Klasse @class.', ['@ref' => $reference, '@class' => get_class($error)]);
      $this->messenger()->addError($this->t('Verbindungstest fehlgeschlagen. Bitte Anbieterzugang und Unterstützung strukturierter Antworten prüfen. Einstellungen bleiben erhalten. Referenz: @ref', ['@ref' => $reference]));
    }
    $form_state->setRebuild();
  }

  /** Liest Auswahl oder frei eingetragenen, anschließend validierten Katalognamen. */
  private static function groupIcon(array $row): string {
    $choice = $row['icon_choice'] ?? $row['icon'] ?? 'file-text';
    return trim((string) ($choice === '__custom' ? ($row['icon_custom'] ?? '') : $choice));
  }

  /** Erhält Gestaltung auch beim Kopieren, Verschieben und Importieren. */
  private static function visualGroup(array $row): array {
    $symbols = array_values(array_filter($row['visual_symbols'] ?? [], static fn($id): bool => is_string($id) && preg_match('/^[a-z][a-z0-9_-]{0,63}$/D', $id)));
    return ['icon' => self::groupIcon($row), 'visual_enabled' => !empty($row['visual_enabled']), 'visual_instructions' => mb_substr(trim((string) ($row['visual_instructions'] ?? '')), 0, 4000), 'visual_lead' => !empty($row['visual_lead']), 'visual_table' => !empty($row['visual_table']), 'visual_symbols' => array_slice(array_unique($symbols), 0, 80)];
  }

  /** Liest ausschließlich begrenzte Stilfelder aus noch ungespeicherten Eingaben. */
  private function draftStyles(FormStateInterface $form_state): array {
    $rows = $form_state->getUserInput()['styles'] ?? $form_state->get('style_draft') ?? $this->config('je_ai_submission.settings')->get('styles');
    $result = [];
    foreach ($rows as $row) {
      $clean = [];
      foreach (['id', 'label', 'instructions'] as $key) {
        $clean[$key] = is_string($row[$key] ?? NULL) ? trim($row[$key]) : '';
      }
      if (!array_filter($clean)) { continue; }
      if (strlen($clean['id']) > 60 || mb_strlen($clean['label']) > 100 || mb_strlen($clean['instructions']) > 10000 || count($result) >= 50) {
        throw new \InvalidArgumentException('Stilgruppen zu groß oder zu zahlreich.');
      }
      $result[] = $clean + self::visualGroup($row);
    }
    return $result;
  }

  /** Aktualisiert nur den Formularentwurf, nicht die gespeicherte Konfiguration. */
  private function setDraftStyles(FormStateInterface $form_state, array $rows): void {
    $form_state->set('style_draft', array_values($rows));
    $input = $form_state->getUserInput();
    $input['styles'] = array_values($rows);
    $form_state->setUserInput($input);
    $form_state->setRebuild();
  }

  /** Dupliziert, verschiebt oder entfernt eine Gruppe im ungespeicherten Entwurf. */
  public function manageStyle(array &$form, FormStateInterface $form_state): void {
    try {
      $rows = $this->draftStyles($form_state);
      $trigger = $form_state->getTriggeringElement();
      $index = $trigger['#je_index'];
      $action = $trigger['#je_action'];
      if (!isset($rows[$index])) { return; }
      if ($action === 'duplicate' && count($rows) < 50) {
        $copy = $rows[$index];
        $copy['id'] = 'copy_' . bin2hex(random_bytes(6));
        $copy['label'] = mb_substr($copy['label'], 0, 85) . ' – Kopie';
        array_splice($rows, $index + 1, 0, [$copy]);
        $form_state->set('style_open_index', $index + 1);
      }
      elseif ($action === 'remove' && count($rows) > 1) { array_splice($rows, $index, 1); }
      elseif ($action === 'up' && $index > 0) { [$rows[$index - 1], $rows[$index]] = [$rows[$index], $rows[$index - 1]]; $form_state->set('style_open_index', $index - 1); }
      elseif ($action === 'down' && $index < count($rows) - 1) { [$rows[$index + 1], $rows[$index]] = [$rows[$index], $rows[$index + 1]]; $form_state->set('style_open_index', $index + 1); }
      $this->setDraftStyles($form_state, $rows);
      $this->messenger()->addStatus($this->t('Stilgruppen im Formular aktualisiert. Zum dauerhaften Übernehmen bitte Konfiguration speichern.'));
    }
    catch (\InvalidArgumentException $error) { $this->messenger()->addError($this->t('Bitte Größe und Anzahl der Stilgruppen prüfen.')); $form_state->setRebuild(); }
  }

  /** Prüft den Import vollständig und zeigt erst eine Vorschau. */
  public function checkImport(array &$form, FormStateInterface $form_state): void {
    try {
      $rows = $this->draftStyles($form_state);
      $json = $form_state->getUserInput()['style_json'] ?? '';
      $pending = \Drupal\je_ai_submission\Service\StyleTransfer::decode($json, $rows);
      $form_state->set('style_import_preview', $pending)->set('style_import_base', hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR)));
      $this->messenger()->addStatus($this->t('Import geprüft. Bitte die Vorschau ansehen und anschließend bewusst übernehmen.'));
    }
    catch (\Throwable $error) { $form_state->set('style_import_preview', NULL); $this->messenger()->addError($this->t('Import nicht möglich: Datei, Format, Namen und Größen prüfen. Bestehende Gruppen wurden nicht geändert.')); }
    $form_state->setRebuild();
  }

  /** Ergänzt die geprüften Gruppen, ohne bestehende Gruppen zu überschreiben. */
  public function applyImport(array &$form, FormStateInterface $form_state): void {
    try {
      $rows = $this->draftStyles($form_state);
      if ($form_state->get('style_import_base') !== hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR))) {
        throw new \RuntimeException('Stilgruppen inzwischen geändert.');
      }
      $pending = $form_state->get('style_import_preview') ?? [];
      if (!$pending || count($pending) + count($rows) > 50) { throw new \RuntimeException('Ungültige Vorschau.'); }
      $this->setDraftStyles($form_state, array_merge($rows, $pending));
      $form_state->set('style_import_preview', NULL);
      $this->messenger()->addStatus($this->t('Geprüfte Gruppen ergänzt. Erst Konfiguration speichern übernimmt sie dauerhaft.'));
    }
    catch (\Throwable $error) { $this->messenger()->addError($this->t('Bitte den Import nach den Änderungen erneut prüfen.')); $form_state->setRebuild(); }
  }

  /** Liefert die Formularaktualisierung ohne vollständigen Seitenwechsel. */
  public function ajaxRefresh(array &$form, FormStateInterface $form_state): array {
    return $form;
  }

  /** Speichert Konfiguration, niemals API-Schlüssel oder Zugangsdaten. */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $settings = $this->config('je_ai_submission.settings');
    foreach (['enabled', 'provider_id', 'model_id', 'base_instructions'] as $key) {
      $settings->set($key, $key === 'enabled' ? (bool) $form_state->getValue($key) : trim(str_replace(["\r\n", "\r"], "\n", $form_state->getValue($key))));
    }
    $tags = array_values(array_filter(array_map('trim', preg_split('/\R/', $form_state->getValue('preferred_tags')))));
    $settings->set('preferred_tags', \Drupal\je_ai_submission\Service\ArticleSuggestion::mergeTags($tags));
    $settings->set('styles', $form_state->get('normalized_styles'));
    $settings->set('visual_enabled', (bool) $form_state->getValue('visual_enabled'));
    foreach ($form_state->getValue('limits') as $key => $value) {
      $settings->set($key, (int) $value);
    }
    foreach (['tag_limit', 'tag_use_existing', 'tag_include_location', 'tag_instructions'] as $key) {
      $value = $form_state->getValue(['tag_rules', $key]);
      $settings->set($key, $key === 'tag_limit' ? (int) $value : ($key === 'tag_instructions' ? trim(str_replace(["\r\n", "\r"], "\n", $value)) : (bool) $value));
    }
    $settings->set('log_content', (bool) $form_state->getValue(['logging', 'log_content']));
    $settings->set('log_limit', (int) $form_state->getValue(['logging', 'log_limit']));
    $notifications = $form_state->getValue('notifications');
    $settings->set('completion_action', $notifications['completion_action'] ?? 'review');
    $settings->set('notify_enabled', (bool) $notifications['notify_enabled']);
    $settings->set('notify_author', (bool) $notifications['notify_author']);
    $settings->set('notify_users', array_values(array_unique(array_map(static fn(array $item): int => (int) $item['target_id'], $notifications['notify_users'] ?? []))));
    $settings->set('review_state', trim($notifications['review_state']));
    $old_header = $settings->get('mail_header_uuid') ? \Drupal::service('entity.repository')->loadEntityByUuid('file', $settings->get('mail_header_uuid')) : NULL;
    $header = !empty($notifications['mail_header'][0]) ? \Drupal::entityTypeManager()->getStorage('file')->load($notifications['mail_header'][0]) : NULL;
    if ($old_header && (!$header || $header->id() !== $old_header->id())) {
      \Drupal::service('file.usage')->delete($old_header, 'je_ai_submission', 'mail_header', 0);
    }
    if ($header) {
      $header->setPermanent();
      $header->save();
      if (!$old_header || $old_header->id() !== $header->id()) { \Drupal::service('file.usage')->add($header, 'je_ai_submission', 'mail_header', 0); }
    }
    $settings->set('mail_header_uuid', $header ? $header->uuid() : '');
    $settings->save();
    \Drupal\je_ai_submission\Service\VisualStyles::syncEditor();
    \Drupal::service('je_ai_submission.audit')->trim();
    parent::submitForm($form, $form_state);
  }

}
