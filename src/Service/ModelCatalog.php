<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\State\StateInterface;

/** Trennt die lokale Modellauswahl von ausdrücklich ausgelösten Anbieterabfragen. */
final class ModelCatalog {

  public function __construct(
    private readonly AiProviderPluginManager $providers,
    private readonly StateInterface $state,
  ) {}

  /** Liest nur die gespeicherte Liste, ohne den Anbieter aufzurufen. */
  public function stored(string $provider): array {
    return $this->state->get('je_ai_submission.models.' . $provider, ['models' => [], 'updated' => 0]);
  }

  /** Aktualisiert ausdrücklich die Modellliste über Drupal AI; Fehler erhalten die alte Liste. */
  public function refresh(string $provider): array {
    $instance = $this->providers->createInstance($provider);
    if (!$instance->isUsable('chat')) {
      throw new \RuntimeException('Der Anbieterzugang ist nicht eingerichtet.');
    }
    $models = $instance->getConfiguredModels('chat');
    $safe = [];
    foreach ($models as $id => $name) {
      // Der OpenAI-Chatfilter enthält auch reine Medien- und Spezialmodelle.
      if ($provider === 'openai' && preg_match('/audio|image|realtime|transcri|tts|search|instruct|codex|live|whisper/i', (string) $id)) {
        continue;
      }
      if (is_string($id) && preg_match('/^[a-zA-Z0-9_.:\/-]{1,120}$/D', $id)) {
        $safe[$id] = mb_substr((string) $name, 0, 200);
      }
    }
    if (!$safe) {
      throw new \RuntimeException('Der Anbieter hat keine Chat-Modelle geliefert.');
    }
    ksort($safe, SORT_NATURAL | SORT_FLAG_CASE);
    $entry = ['models' => $safe, 'updated' => time()];
    $this->state->set('je_ai_submission.models.' . $provider, $entry);
    return $entry;
  }

}
