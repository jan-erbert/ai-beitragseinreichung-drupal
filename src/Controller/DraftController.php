<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\je_ai_submission\Service\DraftTransfer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** Öffnet die normale Beitragsbearbeitung mit dem freigegebenen Vorschlag. */
final class DraftController extends ControllerBase {

  public function __construct(private readonly DraftTransfer $drafts) {}

  /** {@inheritdoc} */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('je_ai_submission.drafts'));
  }

  /** Lässt sämtliche Medien- und Beitragsfunktionen im nativen Drupal-Formular. */
  public function edit(string $token): array|\Symfony\Component\HttpFoundation\RedirectResponse {
    $entry = $this->drafts->get($token);
    if (!$entry) {
      throw new NotFoundHttpException('Diese Vorschau ist abgelaufen oder gehört zu einem anderen Konto.');
    }
    if (isset($entry['saved_node'])) {
      $node = $this->entityTypeManager()->getStorage('node')->load($entry['saved_node']);
      if (!$node || !$node->access('update')) {
        throw new NotFoundHttpException();
      }
      return $this->redirect('entity.node.edit_form', ['node' => $node->id()]);
    }
    $node = $this->drafts->buildNode($entry);
    $form = $this->entityFormBuilder()->getForm($node, 'je_ai_submission');
    $form['#cache']['max-age'] = 0;
    return $form;
  }

}
