<?php

use Drupal\je_ai_submission\Controller\LogController;

/** Prüft sichere Vergleichsausgabe ohne KI-Aufruf oder bleibende Protokolle. */
function je_log_check(bool $condition, string $message): void {
  if (!$condition) { throw new RuntimeException($message); }
}

$transaction = \Drupal::database()->startTransaction();
$switcher = \Drupal::service('account_switcher');
$switcher->switchTo(\Drupal\user\Entity\User::load(1));
try {
  $source = ['title' => '', 'text' => "Originaltext\nZweite Zeile <script>nur Text</script>", 'excerpt' => '', 'tags' => [], 'additional_instructions' => 'Kurze Absätze.'];
  $result = ['title' => 'Interner Darstellungstest', 'content' => "**Formatiert**\n\n[Unsicher](javascript:alert(1))\n\n<script>kein HTML</script>\n\n![Bild](https://example.org/test.jpg)", 'excerpt' => 'Kurze Vorschau', 'tags' => ['Test']];
  foreach ([['source' => $source, 'result' => $result, 'previous' => $result, 'change' => 'Kürzen.'], ['source' => $source, 'result' => NULL, 'previous' => NULL, 'change' => ''], NULL] as $comparison) {
    $id = \Drupal::database()->insert('je_ai_submission_log')->fields(['created' => time(), 'uid' => 1, 'provider' => 'test', 'model' => 'test', 'style' => 'Test', 'operation' => 'revise', 'success' => $comparison !== NULL ? 1 : 0, 'duration_ms' => 1234, 'comparison' => $comparison ? json_encode($comparison, JSON_THROW_ON_ERROR) : NULL, 'reference' => ''])->execute();
    $build = LogController::create(\Drupal::getContainer())->detail((string) $id);
    $html = (string) \Drupal::service('renderer')->renderInIsolation($build);
    je_log_check(!str_contains($html, '<script') && !str_contains($html, '<img') && !str_contains($html, 'href="javascript:'), 'Aktive Inhalte im Protokoll.');
    if ($comparison) {
      je_log_check(str_contains($html, '&lt;script&gt;') && str_contains($html, 'Kurze Absätze.'), 'Originaltext oder Hinweise fehlen als sicherer Text.');
      if ($comparison['result']) {
        je_log_check(str_contains($html, '<strong>Formatiert</strong>') && str_contains($html, 'Vorheriger Vorschlag'), 'Formatierter Vergleich fehlt.');
        je_log_check(!str_contains($html, '<details open'), 'Vorheriger Vorschlag ist nicht eingeklappt.');
      }
    }
    else {
      je_log_check(str_contains($html, 'kein Textvergleich'), 'Metadateneintrag ohne Textvergleich nicht darstellbar.');
    }
  }
}
finally {
  $switcher->switchBack();
  $transaction->rollBack();
}
echo "Geprüft: Originaltext, sichere Markdown-Ausgabe, Hinweise, Überarbeitung, fehlendes Ergebnis und Metadaten ohne Vergleich. Keine KI-Anfrage; Testdaten zurückgerollt.\n";
