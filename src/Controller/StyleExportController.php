<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\je_ai_submission\Service\StyleTransfer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** Liefert ausschließlich gespeicherte Stilgruppen als Admin-Download. */
final class StyleExportController extends ControllerBase {

  /** Exportiert alle Gruppen oder eine explizit gewählte gespeicherte Gruppe. */
  public function download(string $style = 'all'): Response {
    $styles = $this->config('je_ai_submission.settings')->get('styles');
    if ($style !== 'all') {
      $styles = array_values(array_filter($styles, static fn(array $row): bool => $row['id'] === $style));
      if (!$styles) {
        throw new NotFoundHttpException();
      }
    }
    return new Response(StyleTransfer::encode($styles, $style !== 'all'), 200, ['Content-Type' => 'application/json; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="je-stilgruppen-' . $style . '.json"', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
  }

}
