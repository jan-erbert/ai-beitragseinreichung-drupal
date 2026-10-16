<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Service;

/** Trennt den redaktionellen Ablauf von der Anbieteranbindung. */
interface AiTextGatewayInterface {

  /** Liefert eine strukturierte Antwort, ohne Inhalte selbst zu speichern. */
  public function generate(string $system, string $input, array $schema): string;

}
