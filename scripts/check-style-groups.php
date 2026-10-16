<?php

/** Prüft Gruppenkennzeichen, Import und Protokollrechte ohne KI oder Mail. */
use Drupal\je_ai_submission\Service\StyleGroups;
use Drupal\je_ai_submission\Service\StyleTransfer;
use Drupal\je_ai_submission\Service\IconCatalog;
use Drupal\je_ai_submission\Form\SettingsForm;
use Drupal\je_ai_submission\Form\SubmissionForm;
use Drupal\user\Entity\Role;

function je_group_check(bool $condition, string $message): void {
  if (!$condition) { throw new RuntimeException($message); }
}
$transaction = \Drupal::database()->startTransaction();
$switcher = \Drupal::service('account_switcher');
$switcher->switchTo(\Drupal\user\Entity\User::load(1));
$settings = \Drupal::configFactory()->getEditable('je_ai_submission.settings');
try {
  $path = \Drupal::service('extension.list.module')->getPath('je_ai_submission');
  $defaults = \Drupal::service('serialization.yaml')->decode(file_get_contents($path . '/config/install/je_ai_submission.settings.yml'));
  je_group_check(array_column($defaults['styles'], 'id') === ['standardartikel', 'veranstaltung', 'presse_medien'] && !$defaults['visual_enabled'], 'Neutrale Grundausstattung falsch.');
  foreach ($defaults['styles'] as $group) { je_group_check(IconCatalog::has($group['icon']), 'Standardsymbol fehlt.'); }
  $form = \Drupal::formBuilder()->getForm(SettingsForm::class);
  $group = $settings->get('styles')[0];
  je_group_check($form['presentation']['styles'][0]['id']['#type'] === 'hidden' && $form['presentation']['styles'][0]['icon_choice']['#parents'] === ['styles', 0, 'icon_choice'], 'Kennung sichtbar oder Auswahlpfad verändert.');
  $draft = StyleTransfer::decode(StyleTransfer::encode([$group]))[0];
  je_group_check($draft['icon'] === $group['icon'], 'Gruppensymbol geht beim Import verloren.');
  $method = new ReflectionMethod(SettingsForm::class, 'visualGroup');
  je_group_check($method->invoke(NULL, ['icon_choice' => '__custom', 'icon_custom' => 'search'])['icon'] === 'search', 'Individuelles Symbol fehlt.');
  $form = \Drupal::formBuilder()->getForm(SubmissionForm::class);
  $icons = json_decode($form['source']['style']['#attributes']['data-je-group-icons'], TRUE);
  je_group_check($icons[$group['id']] === $group['icon'], 'Beitragsauswahl verliert Symbol.');
  $aliases = \Drupal::state()->get('je_ai_submission.style_aliases', []);
  \Drupal::state()->set('je_ai_submission.style_aliases', ['old_fixture' => $group['id']]);
  je_group_check(StyleGroups::resolve('old_fixture') === $group['id'] && StyleGroups::resolve('missing_fixture') === 'missing_fixture', 'Aliasauflösung unsicher.');
  \Drupal::state()->set('je_ai_submission.style_aliases', $aliases);
  $role = Role::create(['id' => 'je_ai_role_fixture', 'label' => 'Interne Rechteprüfung']);
  $role->grantPermission('use je ai submission')->save();
  je_group_check($role->hasPermission('view je ai submission log') && !$role->hasPermission('delete je ai submission log'), 'Neue KI-Rolle erhält falsche Standardrechte.');
  $role->revokePermission('view je ai submission log')->save();
  $role->set('label', 'Individuelle Rechteprüfung')->save();
  je_group_check(!$role->hasPermission('view je ai submission log'), 'Manueller Rechteentzug wird überschrieben.');
  $typed = \Drupal::service('config.typed')->createFromNameAndData('je_ai_submission.settings', $settings->getRawData())->validate();
  je_group_check(count($typed) === 0, 'Gruppenschema ungültig: ' . (string) $typed);
  $switcher->switchBack();
  $switcher->switchTo(\Drupal\user\Entity\User::load(2));
  $access = \Drupal::service('access_manager');
  je_group_check($access->checkNamedRoute('je_ai_submission.log', [], \Drupal::currentUser()) && $access->checkNamedRoute('je_ai_submission.log_detail', ['log' => '1'], \Drupal::currentUser()), 'Redaktion kann Protokoll nicht lesen.');
  je_group_check(!$access->checkNamedRoute('je_ai_submission.log_delete', ['log' => '1'], \Drupal::currentUser()) && !$access->checkNamedRoute('je_ai_submission.settings', [], \Drupal::currentUser()), 'Redaktion erhält Lösch- oder Einstellungszugriff.');
  echo "Geprüft: drei neutrale Standardgruppen, Kennzeichen und Export, Aliasauflösung, anpassbarer Rechte-Standard und Redaktion nur lesend. Keine KI-Anfrage oder Mail; Prüfdaten zurückgerollt.\n";
}
finally {
  $switcher->switchBack();
  $transaction->rollBack();
  \Drupal::configFactory()->reset();
  \Drupal::state()->resetCache();
  \Drupal::entityTypeManager()->getStorage('user_role')->resetCache();
}
