<?php

declare(strict_types=1);

namespace Drupal\je_ai_submission\Service;

/** Liefert vorhandene Schlagwörter mit der Nutzung in veröffentlichten Beiträgen. */
final class TagSuggestions {

  /** Zählt Beiträge statt Revisionen und berücksichtigt auch bisher ungenutzte Begriffe. */
  public function frequent(int $limit = 50): array {
    $settings = \Drupal::config('je_ai_submission.settings');
    $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $settings->get('content_type'));
    $definition = $definitions[$settings->get('tags_field')] ?? NULL;
    if (!$definition || $definition->getType() !== 'entity_reference' || $definition->getSetting('target_type') !== 'taxonomy_term') { return []; }
    $mapping = \Drupal::entityTypeManager()->getStorage('node')->getTableMapping();
    $table = $mapping->getDedicatedDataTableName($definition->getFieldStorageDefinition());
    $column = $mapping->getFieldColumnName($definition->getFieldStorageDefinition(), 'target_id');
    $query = \Drupal::database()->select('taxonomy_term_field_data', 't');
    $query->leftJoin($table, 'r', 'r.' . $column . ' = t.tid AND r.deleted = 0');
    $query->leftJoin('node_field_data', 'n', 'n.nid = r.entity_id AND n.type = :type AND n.status = 1 AND n.default_langcode = 1', [':type' => $settings->get('content_type')]);
    $query->fields('t', ['tid', 'name'])->condition('t.vid', $settings->get('tags_vocabulary'))->condition('t.default_langcode', 1);
    $query->addExpression('COUNT(DISTINCT n.nid)', 'uses');
    $query->groupBy('t.tid')->groupBy('t.name')->orderBy('uses', 'DESC')->orderBy('t.name')->range(0, max(1, min(1000, $limit)));
    $terms = [];
    foreach ($query->execute() as $row) {
      if (!preg_match('/^\s*\d{4}\s*$/D', $row->name)) { $terms[(int) $row->tid] = ['name' => $row->name, 'count' => (int) $row->uses]; }
    }
    return $terms;
  }

}
