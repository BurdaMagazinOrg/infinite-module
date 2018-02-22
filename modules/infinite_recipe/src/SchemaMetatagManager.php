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

    // The current implementation of REWE button does not work
    // with the graph structure. Therefore, we look for recipeIngredient as
    // our recipe indicator and then copy all items from graph so the REWE button
    // can find them
    if (isset($items['@graph'][0]['recipeIngredient'])) {
      foreach ($items['@graph'][0] as $key => $graphItem) {
        $items[$key] = $graphItem;
      }
      // cleanup the ingredients (strip all whitespace etc.)
      foreach ($items['recipeIngredient'] as &$ingredient) {
        $ingredient = preg_replace('#[\s\s]+#', ' ', $ingredient);
        $ingredient = str_replace('&nbsp;', ' ', $ingredient);
        $ingredient = trim($ingredient, " \t\n\r\0\x0B\xC2\xA0");
      }
      // The REWE button can't handle the image information as array,
      // therefore we have to replace the array with a string containing
      // only the image url
      if (isset($items['image']) && is_array($items['image'])) {
        $items['image'] = $items['image']['url'];
      }
    }

    return $items;
  }
}
