<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\Core\Config\ConfigFactoryInterface;

/** Verwendet ausschließlich die normale Drupal-AI-Anbieteranbindung. */
final class DrupalAiGateway implements AiTextGatewayInterface {

  public function __construct(
    private readonly AiProviderPluginManager $providers,
    private readonly ConfigFactoryInterface $config,
  ) {}

  /** {@inheritdoc} */
  public function generate(string $system, string $input, array $schema): string {
    $settings = $this->config->get('je_ai_submission.settings');
    $provider_id = $settings->get('provider_id');
    $model = $settings->get('model_id');
    if (!$settings->get('enabled') || !$provider_id || !$model) {
      throw new \RuntimeException('Die KI-Beitragsfunktion ist noch nicht vollständig eingerichtet.');
    }
    $provider = $this->providers->createInstance($provider_id);
    if (!$provider->isUsable('chat')) {
      throw new \RuntimeException('Der KI-Anbieter ist noch nicht eingerichtet.');
    }
    $request = new ChatInput([new ChatMessage('user', $input)]);
    $request->setSystemPrompt($system);
    $request->setChatStructuredJsonSchema(['name' => 'article_suggestion', 'strict' => TRUE, 'schema' => $schema]);
    $response = $provider->chat($request, $model, ['je_ai_submission']);
    return $response->getNormalized()->getText();
  }

}
