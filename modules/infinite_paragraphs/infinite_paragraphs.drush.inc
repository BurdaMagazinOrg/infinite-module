<?php
/**
 * @file
 * Provides drush command to update all articles.
 */

/**
 * Implements hook_drush_command().
 */
function infinite_paragraphs_drush_command() {
  $items = array();
  $items['update-paragraphs'] = array(
    'description' => dt('Moves paragraphs into one paragraph reference field.'),
    'drupal dependencies' => array('paragraphs')
  );
  return $items;
}

/**
 * Moves paragraphs into one paragraph reference field.
 */
function drush_infinite_paragraphs_update_paragraphs() {
  $query = \Drupal::entityQuery('node')->condition('type', 'article')->sort('changed');
  $count = $query->count()->execute();

  print(\Drupal::translation()->formatPlural($count, 'Updating 1 article...', 'Updating @count articles...') . "\n");

  $query = \Drupal::entityQuery('node')->condition('type', 'article')->sort('changed');
  $progress = 0;

  foreach ($query->execute() as $id) {
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($id);
    if (!$node->field_top_paragraphs->isEmpty()) {
      $entities = array_merge($node->field_top_paragraphs->referencedEntities(), $node->field_paragraphs->referencedEntities());
      for ($i = 0; $i < count($entities); $i++) {
        $node->field_paragraphs[$i] = $entities[$i];
      }
      for ($i = 0; $i < count($node->field_top_paragraphs); $i++) {
        unset($node->field_top_paragraphs[$i]);
      }
      $node->save();
    }
    $progress++;
    if ($progress % 100 == 0) {
      \Drupal::entityTypeManager()->getStorage('node')->resetCache();
      print(Drupal::translation()->translate('Updated @progress of @count articles...', array(
        '@progress' => $progress,
        '@count' => $count,
      )) . "\n");
    }
  }

  print("Finished moving paraphraphs from field_top_paragraphs to field_paragraphs.\n");
  print("Setting view modes on all paragraphs...\n");

  $query = \Drupal::entityQuery('node')->condition('type', 'article')->sort('changed');
  $progress = 0;

  foreach ($query->execute() as $id) {
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($id);
    if (!$node->field_paragraphs->isEmpty()) {
      for ($i = 0; $i < count($node->field_paragraphs); $i++) {
        if ($i === 0 && $node->field_paragraphs[$i]->entity->bundle() == 'media') {
          $node->field_paragraphs[$i]->entity->field_p_view_mode->value = 'middle';
        }
        else {
          $node->field_paragraphs[$i]->entity->field_p_view_mode->value = 'small';
        }
        $node->field_paragraphs[$i]->entity->save();
      }
    }
    $progress++;
    if ($progress % 100 == 0) {
      \Drupal::entityTypeManager()->getStorage('node')->resetCache();
      \Drupal::entityTypeManager()->getStorage('paragraph')->resetCache();
      print(Drupal::translation()->translate('Updated @progress of @count articles...', array(
        '@progress' => $progress,
        '@count' => $count,
      )) . "\n");
    }
  }

  print("Finished setting view modes on paragraphs.\n");
}
