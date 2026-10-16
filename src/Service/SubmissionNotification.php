<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Service;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/** Reicht gespeicherte KI-Entwürfe ausdrücklich ein und merkt jeden Versand. */
final class SubmissionNotification {

  /** Merkt die Herkunft unabhängig von der Aufbewahrungsgrenze des KI-Protokolls. */
  public function register(int $nid, int $log_id, string $style_label = ''): void {
    $store = \Drupal::keyValue('je_ai_submission.submissions');
    if (!$store->has((string) $nid)) {
      if (!$style_label && $log_id) { $style_label = (string) \Drupal::database()->select('je_ai_submission_log', 'l')->fields('l', ['style'])->condition('id', $log_id)->execute()->fetchField(); }
      $store->set((string) $nid, ['log_id' => $log_id, 'style_label' => mb_substr(trim(strip_tags($style_label)), 0, 128), 'submitted' => 0, 'deliveries' => []]);
    }
  }

  /** Erkennt auch vor Einführung der Benachrichtigung gespeicherte KI-Entwürfe. */
  public function get(int $nid): ?array {
    $entry = \Drupal::keyValue('je_ai_submission.submissions')->get((string) $nid);
    if (!$entry) {
      $log_id = \Drupal::database()->select('je_ai_submission_log', 'l')->fields('l', ['id'])->condition('nid', $nid)->orderBy('id', 'DESC')->range(0, 1)->execute()->fetchField();
      if ($log_id) {
        $this->register($nid, (int) $log_id);
        $entry = \Drupal::keyValue('je_ai_submission.submissions')->get((string) $nid);
      }
    }
    return $entry;
  }

  /** Lädt die jüngste Revision statt einer möglicherweise älteren Veröffentlichung. */
  public function latest(int $nid): ?NodeInterface {
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $vid = $storage->getLatestRevisionId($nid);
    return $vid ? $storage->loadRevision($vid) : NULL;
  }

  /** Verknüpft Modulzugriff, Beitragszugriff und native Moderationsberechtigung. */
  public function access(NodeInterface $node, AccountInterface $account): AccessResult {
    if (EditorialFlow::isDirect()) { return AccessResult::forbidden()->setCacheMaxAge(0); }
    $node = $this->latest((int) $node->id()) ?? $node;
    $entry = $this->get((int) $node->id());
    $allowed = $node->bundle() === \Drupal::config('je_ai_submission.settings')->get('content_type')
      && $account->hasPermission('use je ai submission') && $node->access('update', $account) && $entry;
    if ($allowed && empty($entry['submitted'])) {
      $allowed = !$node->isPublished() && $this->transitionAllowed($node, $account);
    }
    return AccessResult::allowedIf((bool) $allowed)->setCacheMaxAge(0);
  }

  /** Prüft einen erlaubten Übergang in einen tatsächlich unveröffentlichten Zustand. */
  private function transitionAllowed(NodeInterface $node, AccountInterface $account): bool {
    $workflow = \Drupal::service('content_moderation.moderation_information')->getWorkflowForEntity($node);
    $target = \Drupal::config('je_ai_submission.settings')->get('review_state') ?? 'review';
    if (!$workflow || !$workflow->getTypePlugin()->hasState($target) || $workflow->getTypePlugin()->getState($target)->isPublishedState()) {
      return FALSE;
    }
    foreach (\Drupal::service('content_moderation.state_transition_validation')->getValidTransitions($node, $account) as $transition) {
      if ($transition->to()->id() === $target) { return TRUE; }
    }
    return FALSE;
  }

  /** Bestimmt aktive konfigurierte Konten und dedupliziert ihre Adressen. */
  private function recipients(NodeInterface $node): array {
    $settings = \Drupal::config('je_ai_submission.settings');
    if (!$settings->get('notify_enabled')) {
      throw new \DomainException('Die Einreichungsbenachrichtigung ist noch nicht aktiviert. Bitte die Administration informieren.');
    }
    $ids = array_slice($settings->get('notify_users') ?? [], 0, 20);
    if ($settings->get('notify_author')) { $ids[] = $node->getOwnerId(); }
    $recipients = [];
    foreach (\Drupal::entityTypeManager()->getStorage('user')->loadMultiple(array_unique($ids)) as $user) {
      $email = trim($user->getEmail() ?? '');
      if ($user->isActive() && \Drupal::service('email.validator')->isValid($email)) {
        $recipients[mb_strtolower($email)] = ['uid' => (int) $user->id(), 'email' => $email, 'status' => 'pending'];
      }
    }
    if (!$recipients) { throw new \DomainException('Es ist kein aktives Empfängerkonto mit gültiger E-Mail-Adresse eingerichtet.'); }
    return $recipients;
  }

  /** Erstellt eine sichere Mailvorschau aus der tatsächlich eingereichten Revision. */
  public function preview(NodeInterface $node, array $entry): array {
    $settings = \Drupal::config('je_ai_submission.settings');
    $field = $node->get($settings->get('body_field'))->first();
    $content = Xss::filter((string) check_markup($field->value ?? '', $field->format ?? 'basic_html'), ['p', 'br', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'strong', 'em', 'b', 'i', 'blockquote', 'a', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'hr', 'img', 'figure', 'figcaption']);
    $media = (new MailImages())->build($node, $content);
    $summary = trim(strip_tags($field->summary ?? ''));
    if (!$summary) {
      $plain = strip_tags(preg_replace('@</?(?:p|div|h[1-6]|li|br|tr|blockquote)\b[^>]*>@i', ' ', $content));
      $summary = \Drupal\Component\Utility\Unicode::truncate(trim(preg_replace('/\s+/u', ' ', html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8'))), 280, TRUE, TRUE);
    }
    $tags = [];
    foreach ($node->get($settings->get('tags_field'))->referencedEntities() as $term) { $tags[] = $term->label(); }
    $published = ($entry['event'] ?? 'review') === 'published';
    $meta = ['Status' => $published ? 'Veröffentlicht' : 'Zur Prüfung eingereicht', 'Autor' => $node->getOwner()?->getDisplayName() ?? 'Gelöschtes Konto', 'Beitragsdatum' => \Drupal::service('date.formatter')->format($node->getCreatedTime(), 'custom', 'd.m.Y H:i'), 'Schlagwörter' => implode(', ', $tags), 'KI-Unterstützung' => 'Ja'];
    foreach ($node->getFieldDefinitions() as $name => $definition) {
      if ($definition->getType() === 'entity_reference' && $definition->getSetting('target_type') === 'taxonomy_term' && $name !== $settings->get('tags_field') && !$node->get($name)->isEmpty()) {
        $meta[$definition->getLabel()] = implode(', ', array_map(static fn($term): string => $term->label(), $node->get($name)->referencedEntities()));
      }
    }
    $style_label = $entry['style_label'] ?? '';
    if (!empty($entry['log_id'])) {
      $log = \Drupal::database()->select('je_ai_submission_log', 'l')->fields('l', ['model', 'style'])->condition('id', $entry['log_id'])->execute()->fetchObject();
      if ($log) {
        $meta['KI-Modell'] = $log->model;
        if (!$style_label) { $style_label = $log->style; }
      }
    }
    if ($style_label) { $meta['Beitragsart'] = mb_substr(trim(strip_tags($style_label)), 0, 128); }
    return ['event' => $published ? 'published' : 'review', 'event_label' => $published ? 'Neuer veröffentlichter Beitrag' : 'Neue Beitragseinreichung', 'intro' => $published ? 'Ein neuer Beitrag wurde veröffentlicht.' : 'Ein neuer Beitrag wurde zur Prüfung eingereicht. Er ist gespeichert und noch nicht veröffentlicht.', 'action_label' => $published ? 'Beitrag bearbeiten' : 'Beitrag jetzt prüfen', 'title' => $node->label(), 'site' => \Drupal::config('system.site')->get('name'), 'meta' => array_filter($meta), 'excerpt' => $summary, 'url' => $node->toUrl('edit-form', ['absolute' => TRUE])->toString(), 'submitted' => $entry['submitted'] ?? 0] + $media;
  }

  /** Speichert Prüfung und Versandzustand; erfolgreiche Empfänger bleiben gesperrt. */
  public function submit(int $nid, int $expected_revision): array {
    $lock = \Drupal::lock();
    if (!$lock->acquire('je_ai_submission.notify:' . $nid, 1800)) { throw new \DomainException('Die Einreichung wird bereits verarbeitet. Bitte später erneut prüfen.'); }
    try {
      $node = $this->latest($nid);
      if (!$node || !$this->access($node, \Drupal::currentUser())->isAllowed()) { throw new \DomainException('Dieser Beitrag kann mit deinem Konto nicht eingereicht werden.'); }
      if (!\Drupal::config('je_ai_submission.settings')->get('notify_enabled')) { throw new \DomainException('Die Einreichungsbenachrichtigung ist deaktiviert.'); }
      $entry = $this->get($nid);
      $store = \Drupal::keyValue('je_ai_submission.submissions');
      if (empty($entry['submitted'])) {
        if ((int) $node->getRevisionId() !== $expected_revision) { throw new \DomainException('Der Beitrag wurde inzwischen geändert. Bitte die Bestätigungsseite neu öffnen.'); }
        $recipients = $this->recipients($node);
        $transaction = \Drupal::database()->startTransaction();
        try {
          $node->set('moderation_state', \Drupal::config('je_ai_submission.settings')->get('review_state') ?? 'review');
          $node->setNewRevision(TRUE);
          $node->setRevisionUserId(\Drupal::currentUser()->id());
          $node->setRevisionCreationTime(time());
          $node->setRevisionLogMessage('Über JE KI-Beiträge ausdrücklich zur Prüfung eingereicht.');
          if ($node->validate()->count()) { throw new \DomainException('Bitte die Pflichtfelder und Moderationsrechte im Beitragseditor prüfen.'); }
          $node->save();
          $entry += ['submitted_by' => (int) \Drupal::currentUser()->id()];
          $entry['submitted'] = time();
          $entry['revision'] = (int) $node->getRevisionId();
          $entry['deliveries'] = $recipients;
          $store->set((string) $nid, $entry);
          unset($transaction);
        }
        catch (\Throwable $error) { if (isset($transaction)) { $transaction->rollBack(); } throw $error; }
      }
      return $this->deliver($nid, $entry);
    }
    finally { $lock->release('je_ai_submission.notify:' . $nid); }
  }

  /** Benachrichtigt erst nach erfolgreicher Speicherung im regulären Editor. */
  public static function afterPublication(array &$form, FormStateInterface $form_state): void {
    $node = $form_state->getFormObject()->getEntity();
    try {
      $entry = \Drupal::service('je_ai_submission.submissions')->published((int) $node->id(), (int) $node->getRevisionId());
      $statuses = array_count_values(array_column($entry['publication']['deliveries'] ?? [], 'status'));
      if (!empty($statuses['failed']) || !empty($statuses['pending']) || !empty($statuses['sending']) || !empty($statuses['skipped'])) {
        \Drupal::messenger()->addWarning(t('Der Beitrag ist veröffentlicht, aber nicht alle Benachrichtigungen wurden bestätigt. Fehlgeschlagene Zustellungen können durch erneutes ausdrückliches Veröffentlichen wiederholt werden. Unklare Zustellungen muss die Administration prüfen.'));
      }
    }
    catch (\Throwable $error) {
      \Drupal::logger('je_ai_submission')->error('Veröffentlichungsbenachrichtigung für Beitrag @nid fehlgeschlagen; Klasse @class.', ['@nid' => $node->id(), '@class' => get_class($error)]);
      \Drupal::messenger()->addWarning(t('Der Beitrag ist veröffentlicht, die Benachrichtigung konnte aber nicht abgeschlossen werden. Bitte Empfängereinstellungen und Mailtransport prüfen.'));
    }
  }

  /** Merkt die erste direkte Veröffentlichung getrennt von einer früheren Prüfung. */
  public function published(int $nid, ?int $expected_revision = NULL): array {
    $lock = \Drupal::lock();
    if (!$lock->acquire('je_ai_submission.notify:' . $nid, 1800)) { throw new \DomainException('Die Benachrichtigung wird bereits verarbeitet.'); }
    try {
      $node = $this->latest($nid);
      $entry = $this->get($nid);
      if (!EditorialFlow::isDirect() || !$node || !$node->isPublished() || !$entry || $node->bundle() !== \Drupal::config('je_ai_submission.settings')->get('content_type') || !$node->access('update') || !\Drupal::currentUser()->hasPermission('use je ai submission')) {
        throw new \DomainException('Dieser Beitrag kann nicht als Veröffentlichung benachrichtigt werden.');
      }
      if (!\Drupal::config('je_ai_submission.settings')->get('notify_enabled')) { return $entry; }
      if (empty($entry['publication'])) {
        if ($expected_revision !== NULL && (int) $node->getRevisionId() !== $expected_revision) { throw new \DomainException('Der Beitrag wurde inzwischen geändert. Bitte den Versandstatus prüfen.'); }
        $entry['publication'] = ['event' => 'published', 'submitted' => time(), 'revision' => (int) $node->getRevisionId(), 'submitted_by' => (int) \Drupal::currentUser()->id(), 'deliveries' => $this->recipients($node)];
        \Drupal::keyValue('je_ai_submission.submissions')->set((string) $nid, $entry);
      }
      return $this->deliver($nid, $entry, TRUE);
    }
    finally { $lock->release('je_ai_submission.notify:' . $nid); }
  }

  /** Versendet die eingefrorene Fassung ohne erfolgreiche oder unklare Wiederholungen. */
  private function deliver(int $nid, array $entry, bool $publication = FALSE): array {
    $store = \Drupal::keyValue('je_ai_submission.submissions');
    $notice = &$entry;
    if ($publication) { $notice = &$entry['publication']; }
    $node = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($notice['revision']);
    if (!$node) { throw new \DomainException('Die benachrichtigte Revision ist nicht mehr vorhanden.'); }
    $preview = $this->preview($node, $notice + ['log_id' => $entry['log_id'] ?? 0, 'style_label' => $entry['style_label'] ?? '']);
    foreach ($notice['deliveries'] as $key => &$delivery) {
      if (!in_array($delivery['status'], ['pending', 'failed'], TRUE)) { continue; }
      $user = \Drupal::entityTypeManager()->getStorage('user')->load($delivery['uid']);
      if (!$user || !$user->isActive() || strcasecmp($user->getEmail(), $delivery['email']) !== 0) {
        $delivery['status'] = 'skipped';
        $store->set((string) $nid, $entry);
        continue;
      }
      // Vor SMTP merken: Bei einem Prozessabbruch ist die Zustellung unklar.
      $delivery['status'] = 'sending';
      $store->set((string) $nid, $entry);
      try {
        $message = \Drupal::service('plugin.manager.mail')->mail('je_ai_submission', 'submitted', $delivery['email'], $user->getPreferredLangcode(), ['submission' => $preview]);
        $delivery['status'] = !empty($message['result']) ? 'sent' : 'failed';
        if (is_string($message['to'] ?? NULL)) { $delivery['transport_to'] = $message['to']; }
      }
      catch (\Throwable $error) {
        $delivery['status'] = 'failed';
        \Drupal::logger('je_ai_submission')->error('Beitragsbenachrichtigung für Beitrag @nid fehlgeschlagen; Klasse @class.', ['@nid' => $nid, '@class' => get_class($error)]);
      }
      $delivery['attempted'] = time();
      $store->set((string) $nid, $entry);
    }
    unset($delivery, $notice);
    return $entry;
  }

}
