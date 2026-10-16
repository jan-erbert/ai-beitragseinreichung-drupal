<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;

/** Hält ein begrenztes Admin-Protokoll getrennt von technischen Systemlogs. */
final class AuditLog {

  public function __construct(
    private readonly Connection $database,
    private readonly ConfigFactoryInterface $config,
    private readonly AccountProxyInterface $account,
  ) {}

  /** Speichert nur erlaubte Metadaten und ausdrücklich aktivierte Textvergleiche. */
  public function record(string $style, string $operation, int $duration, array $source, ?array $result, string $change = '', ?array $previous = NULL, string $reference = ''): int {
    $settings = $this->config->get('je_ai_submission.settings');
    $comparison = $settings->get('log_content') ? json_encode(['source' => $source, 'result' => $result, 'change' => $change, 'previous' => $previous], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) : NULL;
    $id = (int) $this->database->insert('je_ai_submission_log')->fields([
      'created' => time(), 'uid' => $this->account->id(),
      'provider' => $settings->get('provider_id'), 'model' => $settings->get('model_id'),
      'style' => mb_substr($style, 0, 128), 'operation' => $operation,
      'success' => $result !== NULL ? 1 : 0, 'duration_ms' => max(0, $duration),
      'comparison' => $comparison, 'reference' => $reference,
    ])->execute();
    $this->trim();
    return $id;
  }

  /** Begrenzt gespeicherte Vorgänge entsprechend der Admin-Einstellung. */
  public function trim(): void {
    $limit = max(1, min(500, (int) ($this->config->get('je_ai_submission.settings')->get('log_limit') ?? 100)));
    $boundary = $this->database->select('je_ai_submission_log', 'l')->fields('l', ['id'])->orderBy('id', 'DESC')->range($limit, 1)->execute()->fetchField();
    if ($boundary !== FALSE) {
      $this->database->delete('je_ai_submission_log')->condition('id', $boundary, '<=')->execute();
    }
  }

  /** Verknüpft den erfolgreich gespeicherten Entwurf mit seinem KI-Vorgang. */
  public function linkDraft(int $id, int $node_id): void {
    $this->database->update('je_ai_submission_log')->fields(['nid' => $node_id])->condition('id', $id)->condition('uid', $this->account->id())->execute();
  }

}
