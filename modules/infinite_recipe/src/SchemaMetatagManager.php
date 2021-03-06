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

    if (!isset($items['@graph']) || !is_array($items['@graph'])) {
      return;
    }
    // The current implementation of REWE button does not work
    // with the graph structure. Therefore, we look for recipeIngredient as
    // our recipe indicator and then copy all items from graph so the REWE button
    // can find them.
    foreach ($items['@graph'] as $key => $item) {

      // Find possible recipe in graph structure.
      if (isset($item['@type']) &&
        $item['@type'] == 'Recipe' &&
        isset($item['recipeIngredient'])) {

        // Copy all recipe items out of graph structure.
        foreach ($items['@graph'][$key] as $graphKey => $graphItem) {
          $items[$graphKey] = $graphItem;
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
    }
    return $items;
  }

}
