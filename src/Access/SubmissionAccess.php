<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/** Verknüpft die KI-Berechtigung mit normalen Drupal-Beitragsrechten. */
final class SubmissionAccess {

  /** Erlaubt die Oberfläche nur bei Zugriff auf Beitragstyp und Textformat. */
  public function access(AccountInterface $account): AccessResult {
    $settings = \Drupal::config('je_ai_submission.settings');
    $create = \Drupal::entityTypeManager()->getAccessControlHandler('node')->createAccess($settings->get('content_type'), $account, [], TRUE);
    $format = \Drupal::entityTypeManager()->getStorage('filter_format')->load($settings->get('text_format'));
    return AccessResult::allowedIfHasPermission($account, 'use je ai submission')
      ->andIf($create)
      ->andIf($format ? $format->access('use', $account, TRUE) : AccessResult::forbidden())
      ->addCacheableDependency($settings);
  }

}
