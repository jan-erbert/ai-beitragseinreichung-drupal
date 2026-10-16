<?php

declare(strict_types=1);

/** Führt isolierte Tests ohne Netzwerkaufrufe aus. */
$root = dirname(__DIR__);
if (!empty($argv[1])) {
  require $argv[1];
}
spl_autoload_register(static function (string $class) use ($root): void {
  $prefix = 'Drupal\\je_ai_submission\\';
  if (str_starts_with($class, $prefix)) {
    require_once $root . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
  }
});

use Drupal\je_ai_submission\Service\AiTextGatewayInterface;
use Drupal\je_ai_submission\Service\ArticleAssistant;
use Drupal\je_ai_submission\Service\ArticleSuggestion;
use Drupal\je_ai_submission\Service\DraftTransfer;
use Drupal\je_ai_submission\Service\StyleTransfer;

/** Meldet einen fehlgeschlagenen Test. */
function check(bool $condition, string $message): void {
  if (!$condition) {
    throw new RuntimeException($message);
  }
}

/** Prüft die bewusste Ablehnung ungültiger Antworten. */
function rejects(callable $callback): void {
  try {
    $callback();
  }
  catch (JsonException|UnexpectedValueException|InvalidArgumentException $error) {
    return;
  }
  throw new RuntimeException('Ungültige Antwort wurde nicht abgelehnt.');
}
$payload = ['title' => 'Ein Rückblick', 'content' => 'Ein vorgegebener Bericht.', 'excerpt' => 'Kurzer Vorschlag.', 'tags' => ['radfahren', 'Radfahren', '2026']];
$gateway = new class implements AiTextGatewayInterface {
  public array $payload;
  public array $input;
  public string $system;

  /** Liefert ausschließlich eine feste Testantwort, ohne KI-Anbieter. */
  public function generate(string $system, string $input, array $schema): string {
    $this->system = $system;
    $this->input = json_decode($input, TRUE, 512, JSON_THROW_ON_ERROR);
    return json_encode($this->payload, JSON_THROW_ON_ERROR);
  }
};
$gateway->payload = $payload;
$assistant = new ArticleAssistant($gateway);
$source = ['title' => 'Original', 'text' => 'Paul lief 5 km in 23:40 Minuten.', 'excerpt' => 'Eigene Vorschau', 'tags' => ['5 km'], 'generate_excerpt' => FALSE, 'generate_tags' => TRUE];
$style = ['id' => 'rueckblick', 'instructions' => 'Sachlich schreiben.'];
$tests = [
  'Stilgruppen WP-Rundlauf' => static function (): void {
    $rows = [['id' => 'test', 'label' => 'Sachlich', 'instructions' => 'Keine Emojis.']];
    $imported = StyleTransfer::decode(StyleTransfer::encode($rows));
    check($imported[0]['label'] === 'Sachlich' && $imported[0]['instructions'] === 'Keine Emojis.', 'WP-Sammelformat verändert Text.');
    check(StyleTransfer::decode(StyleTransfer::encode($rows, TRUE))[0]['label'] === 'Sachlich', 'Einzelformat fehlt.');
    check(StyleTransfer::decode(StyleTransfer::encode($rows), $rows)[0]['label'] === 'Sachlich (2)', 'Namenskonflikt überschreibt vorhandene Gruppe.');
  },
  'Import verwirft defekte Größen und Typen' => static function (): void {
    rejects(static fn() => StyleTransfer::decode(str_repeat('x', 512001)));
    rejects(static fn() => StyleTransfer::decode('{"type":"other","version":1,"data":[]}'));
    rejects(static fn() => StyleTransfer::decode(json_encode(['type' => 'ai-beitragseinreichung-style-group', 'version' => 1, 'data' => ['label' => 'Test', 'stil' => str_repeat('x', 10001)]])));
  },
  'Schlagwortregeln und Obergrenze' => static function () use ($assistant, $source, $style, $gateway): void {
    $result = $assistant->suggest($source, $style, '', [], NULL, '', ['limit' => 1, 'include_location' => TRUE, 'instructions' => 'Ort nur aus der Vorlage.']);
    check(str_contains($gateway->system, 'höchstens 1 zusätzliche') && str_contains($gateway->system, 'Ort nur aus der Vorlage.') && str_contains($gateway->system, 'eindeutig erkennbar'), 'Schlagwortregeln fehlen im Prompt.');
    check(count($result['tags']) <= 2, 'Limit der zusätzlichen Tags greift nicht.');
  },
  'Vollständige Antwort' => static fn() => check(ArticleSuggestion::parse(json_encode($payload))['title'] === 'Ein Rückblick', 'Titel fehlt.'),
  'Defektes JSON' => static fn() => rejects(static fn() => ArticleSuggestion::parse('{invalid')),
  'Fehlende Felder' => static fn() => rejects(static fn() => ArticleSuggestion::parse('{"title":"Test"}')),
  'Falscher Feldtyp' => static fn() => rejects(static fn() => ArticleSuggestion::parse(json_encode(array_replace($payload, ['content' => []])))),
  'HTML im Titel' => static fn() => rejects(static fn() => ArticleSuggestion::parse(json_encode(array_replace($payload, ['title' => '<script>Test</script>'])))),
  'Zusätzliche Antwortfelder' => static fn() => rejects(static fn() => ArticleSuggestion::parse(json_encode($payload + ['publish' => TRUE]))),
  'Doppelte Schlagwörter' => static fn() => check(ArticleSuggestion::mergeTags(['5 km', 'Radfahren'], ['radfahren', '5 km']) === ['5 km', 'Radfahren'], 'Doppelte Begriffe erhalten.'),
  'Manuelle Angaben erhalten' => static function () use ($assistant, $source, $style): void {
    $result = $assistant->suggest($source, $style, 'Keine Emojis.', ['Radfahren']);
    check($result['excerpt'] === 'Eigene Vorschau', 'Eigener Vorschautext überschrieben.');
    check($result['tags'] === ['5 km', 'Radfahren'], 'Schreibweise oder Jahresbehandlung falsch.');
  },
  'Automatik abschaltbar' => static function () use ($assistant, $source, $style): void {
    $result = $assistant->suggest(array_replace($source, ['generate_tags' => FALSE]), $style, '', []);
    check($result['tags'] === ['5 km'], 'Ungewünschte KI-Schlagwörter übernommen.');
  },
  'Änderungswunsch mit Ausgangstext' => static function () use ($assistant, $source, $style, $payload, $gateway): void {
    $with_hints = $source + ['additional_instructions' => 'Gemeinsamen Abschluss hervorheben.'];
    $assistant->suggest($with_hints, $style, '', []);
    check($gateway->input['additional_instructions'] === $with_hints['additional_instructions'] && $gateway->input['requested_change'] === '', 'Zusätzliche Hinweise fehlen bei der ersten Erstellung.');
    $assistant->suggest($with_hints, $style, '', [], $payload, 'Kürzer schreiben.');
    check($gateway->input['additional_instructions'] === $with_hints['additional_instructions'], 'Zusätzliche Hinweise gehen bei der Überarbeitung verloren.');
    check($gateway->input['original_text'] === $source['text'] && $gateway->input['previous_suggestion'] === $payload && $gateway->input['requested_change'] === 'Kürzer schreiben.', 'Ausgangsdaten der Überarbeitung fehlen.');
    check(str_contains($gateway->system, 'keine Emojis') && str_contains($gateway->system, 'Erfinde keine Fakten'), 'Redaktionelle Grundregeln fehlen.');
  },
];
$tests['Lokaler Symbolkatalog und Poolvalidierung'] = static function (): void {
  check(\Drupal\je_ai_submission\Service\IconCatalog::has('search') && \Drupal\je_ai_submission\Service\IconCatalog::has('sport-shoe'), 'Mitgeliefertes Symbol fehlt.');
  check(!\Drupal\je_ai_submission\Service\IconCatalog::has('../search') && !\Drupal\je_ai_submission\Service\IconCatalog::has('unbekannt-test'), 'Ungültiger Symbolname akzeptiert.');
  $pool = \Drupal\je_ai_submission\Service\VisualStyles::defaults();
  check(count(\Drupal\je_ai_submission\Service\VisualStyles::normalize($pool)) === 2, 'Neutrale Standardauswahl fehlt.');
  rejects(static fn() => \Drupal\je_ai_submission\Service\VisualStyles::normalize([array_replace($pool[0], ['icon' => 'https://example.org/icon.svg'])]));
  rejects(static fn() => \Drupal\je_ai_submission\Service\VisualStyles::normalize([array_replace($pool[0], ['label' => '<script>Test</script>'])]));
};
$tests['Gestaltungsschema und getrennte KI-Auswahl'] = static function () use ($gateway, $assistant, $source, $style, $payload): void {
  $visual = ['symbols' => [['id' => 'bike', 'label' => 'Radfahren', 'description' => 'Radtouren', 'inline' => TRUE, 'heading' => TRUE]], 'instructions' => 'Tabellen übersichtlich darstellen.', 'lead' => TRUE, 'results_table' => TRUE];
  $gateway->payload = $payload + ['presentation' => [['style_id' => 'bike', 'position' => 'heading', 'target' => 'Bericht', 'occurrence' => 1]]];
  $result = $assistant->suggest($source, ['id' => 'test'] + $style, '', [], NULL, '', [], $visual);
  check($result['visual_style'] === 'test' && count($result['presentation']) === 1 && $gateway->input['visual']['symbols'][0]['label'] === 'Radfahren', 'Gestaltung und Gruppe fehlen.');
  check(isset(ArticleSuggestion::schema(TRUE)['properties']['presentation']) && !isset(ArticleSuggestion::schema()['properties']['presentation']), 'Deaktivierte Funktion verändert Standardschema.');
  $result = $assistant->suggest($source, $style, '', []);
  check(!isset($result['presentation']) && !isset($result['visual_style']) && !isset($gateway->input['visual']), 'Deaktivierte Funktion übernimmt Gestaltung.');
  $gateway->payload = $payload;
  rejects(static fn() => \Drupal\je_ai_submission\Service\VisualPresentation::validate([['style_id' => 'bike', 'position' => 'inline', 'target' => 'Text', 'occurrence' => 0]]));
  rejects(static fn() => \Drupal\je_ai_submission\Service\VisualPresentation::validate([['style_id' => 'bike', 'position' => 'inline', 'target' => '<script>Test</script>', 'occurrence' => 1]]));
};
$tests['Gestaltung verändert keine Fakten und verwirft fremde Symbole'] = static function (): void {
  $html = '<p>Vier Läufer. Zeit 1:48:09 h.</p><h2>Silber</h2><p>Anke erreicht Platz 2.</p><table><tr><td>1:48:09</td></tr></table>';
  $definitions = ['_lead' => ['class' => 'lead'], '_table' => ['class' => 'results'], 'silber:heading' => ['class' => 'silver-heading'], 'zeit:inline' => ['class' => 'timer']];
  $rows = [['style_id' => 'silber', 'position' => 'heading', 'target' => 'Silber', 'occurrence' => 1], ['style_id' => 'zeit', 'position' => 'inline', 'target' => '1:48:09 h', 'occurrence' => 1], ['style_id' => 'fremd', 'position' => 'inline', 'target' => 'Platz 2', 'occurrence' => 1]];
  $rendered = \Drupal\je_ai_submission\Service\VisualPresentation::apply($html, $rows, $definitions, ['lead' => TRUE, 'results_table' => TRUE]);
  check(strip_tags($rendered) === strip_tags($html), 'Gestaltung hat Text oder Ergebnis verändert.');
  check(str_contains($rendered, '<h2 class="silver-heading">') && str_contains($rendered, '<strong class="timer">1:48:09 h</strong>') && str_contains($rendered, '<table class="results">'), 'Gestaltung nicht angewendet.');
  check(!str_contains($rendered, 'fremd'), 'Unbekannter Stil übernommen.');
};
$tests['Stilgruppen exportieren ihre Gestaltungsregeln'] = static function (): void {
  $group = ['id' => 'test', 'label' => 'Test', 'instructions' => 'Sachlich.', 'visual_enabled' => TRUE, 'visual_symbols' => ['bike'], 'visual_instructions' => 'Passende Überschriften.', 'visual_lead' => TRUE, 'visual_table' => FALSE];
  $result = StyleTransfer::decode(StyleTransfer::encode([$group]))[0];
  check($result['visual_symbols'] === ['bike'] && $result['visual_enabled'] && $result['visual_instructions'] === 'Passende Überschriften.', 'Gestaltung geht beim Export verloren.');
};

$tests['Gruppensymbole bleiben portabel und werden validiert'] = static function (): void {
  $group = ['id' => 'reise', 'label' => 'Reise', 'instructions' => 'Sachlich.', 'icon' => 'plane'];
  check(StyleTransfer::decode(StyleTransfer::encode([$group]))[0]['icon'] === 'plane', 'Erkennungssymbol geht verloren.');
  $group['icon'] = '../../invalid';
  rejects(static fn() => StyleTransfer::decode(StyleTransfer::encode([$group])));
  unset($group['icon']);
  check(StyleTransfer::decode(StyleTransfer::encode([$group]))[0]['icon'] === 'file-text', 'Ältere Exporte sind nicht kompatibel.');
};

$skipped = 0;
if (class_exists(\League\CommonMark\CommonMarkConverter::class)) {
  $tests['Sichere Textformatierung'] = static function (): void {
    $html = DraftTransfer::toHtml("## Titel\n\n**Text**\n\n[Angriff](javascript:alert(1))\n\n![Bild](https://example.org/private.jpg)\n\n<script>alert(1)</script>");
    check(str_contains($html, '<h2>Titel</h2>') && str_contains($html, '<strong>Text</strong>'), 'Erlaubte Formatierung verloren.');
    $table = DraftTransfer::toHtml("| Name | Zeit |\n| --- | --- |\n| Test | 23:40 |");
    check(str_contains($table, '<table>') && str_contains($table, '<td>23:40</td>'), 'Markdown-Tabelle nicht übernommen.');
    check(!str_contains($html, '<img') && !str_contains($html, '<script') && !str_contains($html, 'href="javascript:'), 'Aktive Inhalte oder externe Bilder enthalten.');
  };
}
else {
  $skipped++;
}
foreach ($tests as $name => $test) {
  $test();
  echo 'OK: ' . $name . PHP_EOL;
}
echo count($tests) . ' Tests bestanden; ' . $skipped . " Tests benötigen den Drupal-/Composer-Autoloader.\n";
