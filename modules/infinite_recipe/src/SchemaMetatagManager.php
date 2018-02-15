<?php

namespace Drupal\infinite_recipe;

/**
 * Class SchemaMetatagManager.
 *
 * @package Drupal\schema_metatag
 */
class SchemaMetatagManager extends \Drupal\schema_metatag\SchemaMetatagManager {
  /**
   * {@inheritdoc}
   */
  public static function parseJsonld(array &$elements) {
    $items = parent::parseJsonld($elements);

    if (isset($items['@graph'][0]['recipeIngredient'])) {
      foreach ($items['@graph'][0] as $key => $graphItem) {
        $items[$key] = $graphItem;
      }
      foreach ($items['recipeIngredient'] as &$ingredient) {
        $ingredient = preg_replace('#[\s\s]+#', ' ', $ingredient);
        $ingredient = str_replace('&nbsp;', ' ', $ingredient);
        $ingredient = trim($ingredient, " \t\n\r\0\x0B\xC2\xA0");
      }
    }

    return $items;
  }
}
