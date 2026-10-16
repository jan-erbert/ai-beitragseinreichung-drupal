<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\node\NodeInterface;
use League\CommonMark\Environment\Environment;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;

/** Übergibt geprüfte Vorschläge kontogebunden an den normalen Beitragseditor. */
final class DraftTransfer {

  public function __construct(
    private readonly PrivateTempStoreFactory $tempstore,
    private readonly EntityTypeManagerInterface $entities,
    private readonly AccountProxyInterface $account,
    private readonly ConfigFactoryInterface $config,
  ) {}

  /** Wandelt begrenztes Markdown ohne aktive HTML-Ausgabe um. */
  public static function toHtml(string $markdown): string {
    $environment = new Environment(['html_input' => 'strip', 'allow_unsafe_links' => FALSE, 'max_nesting_level' => 20, 'table' => ['max_autocompleted_cells' => 1000]]);
    $environment->addExtension(new CommonMarkCoreExtension());
    $environment->addExtension(new TableExtension());
    $converter = new MarkdownConverter($environment);
    return preg_replace('/<img\b[^>]*>/i', '', (string) $converter->convert($markdown));
  }

  /** Legt nur eine kurzlebige Übergabe an, noch keinen Beitrag. */
  public function prepare(array $suggestion, int $date, int $log_id = 0, string $style_label = ''): string {
    $token = bin2hex(random_bytes(16));
    $this->tempstore->get('je_ai_submission')->set($token, ['suggestion' => $suggestion, 'date' => $date, 'log_id' => $log_id, 'style_label' => mb_substr(trim(strip_tags($style_label)), 0, 128)]);
    return $token;
  }

  /** Liest ausschließlich die eigene Übergabe. */
  public function get(string $token): ?array {
    return $this->tempstore->get('je_ai_submission')->get($token);
  }

  /** Verhindert eine zweite Beitragserstellung aus derselben Übergabe. */
  public function markSaved(string $token, int $node_id): void {
    $entry = $this->get($token);
    $this->tempstore->get('je_ai_submission')->set($token, ['saved_node' => $node_id]);
    \Drupal::service('je_ai_submission.submissions')->register($node_id, (int) ($entry['log_id'] ?? 0), (string) ($entry['style_label'] ?? ''));
    if (!empty($entry['log_id'])) {
      try {
        \Drupal::service('je_ai_submission.audit')->linkDraft($entry['log_id'], $node_id);
      }
      catch (\Throwable $error) {
        \Drupal::logger('je_ai_submission')->error('KI-Protokoll konnte nicht mit Entwurf verknüpft werden. Fehlerklasse @class.', ['@class' => get_class($error)]);
      }
    }
  }

  /** Baut einen ungespeicherten Drupal-Entwurf mit normalen Beitragsfeldern. */
  public function buildNode(array $entry): NodeInterface {
    $settings = $this->config->get('je_ai_submission.settings');
    $suggestion = ArticleSuggestion::parse(json_encode($entry['suggestion'], JSON_THROW_ON_ERROR));
    $node = $this->entities->getStorage('node')->create([
      'type' => $settings->get('content_type'),
      'uid' => $this->account->id(),
      'title' => $suggestion['title'],
      'created' => $entry['date'],
      'status' => FALSE,
      'moderation_state' => 'draft',
      $settings->get('body_field') => [
        'value' => VisualStyles::render($suggestion),
        'summary' => $suggestion['excerpt'],
        'format' => $settings->get('text_format'),
      ],
    ]);
    $terms = [];
    $vocabulary = $settings->get('tags_vocabulary');
    foreach ($suggestion['tags'] as $name) {
      $existing = $this->entities->getStorage('taxonomy_term')->loadByProperties(['vid' => $vocabulary, 'name' => $name]);
      if ($existing) {
        $terms[] = ['target_id' => reset($existing)->id()];
      }
      elseif ($this->entities->getAccessControlHandler('taxonomy_term')->createAccess($vocabulary, $this->account)) {
        $terms[] = ['entity' => $this->entities->getStorage('taxonomy_term')->create(['vid' => $vocabulary, 'name' => $name])];
      }
    }
    $node->set($settings->get('tags_field'), $terms);
    return $node;
  }

}
