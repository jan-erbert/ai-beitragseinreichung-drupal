<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\je_ai_submission\Service\DraftTransfer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** Zeigt das KI-Protokoll ausschließlich Konten mit ausdrücklichem Leserecht. */
final class LogController extends ControllerBase {

  /** Zeigt begrenzte, paginierte Vorgänge ohne Beitragstexte in der Übersicht. */
  public function overview(): array {
    $query = \Drupal::database()->select('je_ai_submission_log', 'l')->fields('l')->orderBy('id', 'DESC')->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(25);
    $rows = [];
    foreach ($query->execute() as $entry) {
      $account = $this->entityTypeManager()->getStorage('user')->load($entry->uid);
      $rows[] = [
        \Drupal::service('date.formatter')->format($entry->created, 'custom', 'd.m.Y H:i:s'),
        $account ? $account->getDisplayName() : $this->t('Gelöschtes Konto'),
        $entry->operation === 'revise' ? $this->t('Überarbeitung') : $this->t('Erstellung'),
        $entry->style,
        $entry->model,
        ['data' => ['#type' => 'html_tag', '#tag' => 'span', '#value' => $entry->success ? $this->t('Erfolgreich') : $this->t('Fehlgeschlagen'), '#attributes' => ['class' => ['je-ai-log__status', $entry->success ? 'je-ai-log__status--success' : 'je-ai-log__status--error']]]],
        number_format($entry->duration_ms / 1000, 1, ',', '.') . ' s',
        Link::fromTextAndUrl($this->t('Anzeigen'), Url::fromRoute('je_ai_submission.log_detail', ['log' => $entry->id])),
      ];
    }
    return [
      '#type' => 'container', '#attributes' => ['class' => ['je-ai-log']],
      '#attached' => ['library' => ['je_ai_submission/editorial']],
      'intro' => ['#markup' => '<p>' . $this->t('KI-Vorgänge und redaktionelle Textvergleiche. Sichtbar nur mit ausdrücklichem Protokoll-Leserecht. Einstellungen und Löschen sind gesondert berechtigt.') . '</p>'],
      'settings' => Link::fromTextAndUrl($this->t('Einstellungen öffnen'), Url::fromRoute('je_ai_submission.settings'))->toRenderable() + ['#access' => $this->currentUser()->hasPermission('administer je ai submission')],
      'table' => ['#type' => 'table', '#prefix' => '<div class="je-ai-log__table">', '#suffix' => '</div>', '#header' => [$this->t('Zeitpunkt'), $this->t('Konto'), $this->t('Vorgang'), $this->t('Stilgruppe'), $this->t('Modell'), $this->t('Ergebnis'), $this->t('Dauer'), $this->t('Details')], '#rows' => $rows, '#empty' => $this->t('Noch keine KI-Vorgänge protokolliert. Frühere Anfragen werden nicht nachträglich mit Texten ergänzt.')],
      'pager' => ['#type' => 'pager'],
      '#cache' => ['max-age' => 0],
    ];
  }

  /** Zeigt gespeicherte Texte als ungefährlichen Vergleich statt ungefiltertem HTML. */
  public function detail(string $log): array {
    $entry = \Drupal::database()->select('je_ai_submission_log', 'l')->fields('l')->condition('id', $log)->execute()->fetchObject();
    if (!$entry) {
      throw new NotFoundHttpException();
    }
    $build = [
      '#type' => 'container', '#attributes' => ['class' => ['je-ai-log']],
      '#attached' => ['library' => ['je_ai_submission/editorial']],
      '#cache' => ['max-age' => 0],
      'back' => Link::fromTextAndUrl($this->t('Zurück zum KI-Protokoll'), Url::fromRoute('je_ai_submission.log'))->toRenderable(),
      'meta' => ['#type' => 'container', '#attributes' => ['class' => ['je-ai-log__meta']],
        'date' => $this->field($this->t('Zeitpunkt'), \Drupal::service('date.formatter')->format($entry->created, 'custom', 'd.m.Y H:i:s')),
        'model' => $this->field($this->t('Modell'), $entry->model),
        'style' => $this->field($this->t('Stilgruppe'), $entry->style),
        'operation' => $this->field($this->t('Vorgang'), (string) ($entry->operation === 'revise' ? $this->t('Überarbeitung') : $this->t('Erstellung'))),
        'duration' => $this->field($this->t('Dauer'), number_format($entry->duration_ms / 1000, 1, ',', '.') . ' s'),
        'status' => ['#type' => 'html_tag', '#tag' => 'span', '#value' => $entry->success ? $this->t('Erfolgreich') : $this->t('Fehlgeschlagen'), '#attributes' => ['class' => ['je-ai-log__status', $entry->success ? 'je-ai-log__status--success' : 'je-ai-log__status--error']]],
      ],
    ];
    if ($entry->reference) {
      $build['reference'] = ['#type' => 'item', '#title' => $this->t('Fehlerreferenz'), '#plain_text' => $entry->reference];
    }
    $node = $entry->nid ? $this->entityTypeManager()->getStorage('node')->load($entry->nid) : NULL;
    if ($node && $node->access('update')) {
      $build['draft'] = Link::fromTextAndUrl($this->t('Beitrag öffnen'), $node->toUrl('edit-form'))->toRenderable();
    }
    $build['delete'] = Link::fromTextAndUrl($this->t('Protokolleintrag löschen'), Url::fromRoute('je_ai_submission.log_delete', ['log' => $entry->id]))->toRenderable();
    $build['delete']['#attributes']['class'] = ['button', 'button--small', 'je-ai-log__delete'];
    $build['delete']['#access'] = $this->currentUser()->hasPermission('delete je ai submission log');
    if (!$entry->comparison) {
      $build['empty'] = ['#type' => 'item', '#plain_text' => $this->t('Für diesen Vorgang wurde kein Textvergleich gespeichert.')];
      return $build;
    }
    $data = json_decode($entry->comparison, TRUE, 512, JSON_THROW_ON_ERROR);
    $build['comparison'] = ['#type' => 'container', '#attributes' => ['class' => ['je-ai-comparison']]];
    foreach (['source' => $this->t('Ausgangstext'), 'result' => $this->t('KI-Ergebnis'), 'previous' => $this->t('Vorheriger Vorschlag')] as $key => $title) {
      $value = $data[$key] ?? NULL;
      if (!$value) {
        continue;
      }
      $section = ['#type' => $key === 'previous' ? 'details' : 'container', '#attributes' => ['class' => ['je-ai-comparison__text', 'je-ai-comparison__text--' . $key]]];
      if ($key === 'previous') {
        $section['#title'] = $title;
        $section['#open'] = FALSE;
      }
      else {
        $section['heading'] = ['#type' => 'html_tag', '#tag' => 'h2', '#value' => $title];
      }
      $section['title'] = $this->field($this->t('Titel'), $value['title'] ?? '');
      $section['text'] = $this->field($this->t('Beitragstext'), $value['text'] ?? $value['content'] ?? '', $key !== 'source');
      $section['excerpt'] = $this->field($this->t('Vorschautext'), $value['excerpt'] ?? '');
      $section['tags'] = $this->field($this->t('Schlagwörter'), implode(', ', $value['tags'] ?? []));
      if ($key === 'source' && !empty($value['additional_instructions'])) {
        $section['additional_instructions'] = $this->field($this->t('Zusätzliche Hinweise für die KI'), $value['additional_instructions']);
      }
      $build['comparison'][$key] = $section;
    }
    if (!empty($data['change'])) {
      $build['change'] = $this->field($this->t('Änderungswunsch für diese Überarbeitung'), $data['change']);
    }
    return $build;
  }

  /** Trennt Beschriftung und Inhalt ohne Leerraum aus Formularvorlagen. */
  private function field(string|\Stringable $label, string $text, bool $formatted = FALSE): array {
    $value = $formatted && trim($text) !== ''
      ? ['#markup' => DraftTransfer::toHtml($text)]
      : ['#plain_text' => trim($text) !== '' ? $text : '–'];
    return [
      '#type' => 'container', '#attributes' => ['class' => ['je-ai-log__field']],
      'label' => ['#type' => 'html_tag', '#tag' => 'h3', '#value' => $label],
      'value' => ['#type' => 'container', '#attributes' => ['class' => [$formatted ? 'je-ai-log__richtext' : 'je-ai-log__value']], 'content' => $value],
    ];
  }

}
