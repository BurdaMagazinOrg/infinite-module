<?php

/**
 * @file
 * Contains Drupal\infinite_fia\Plugin\Field\FieldFormatter\FiaLinkFormatter.
 */

namespace Drupal\infinite_fia\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Plugin implementation of the 'fia_link' formatter.
 *
 * @FieldFormatter(
 *   id = "fia_link",
 *   label = @Translation("FIA: Add base url to local links"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class FiaLinkFormatter extends LinkFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = parent::viewElements($items, $langcode);

    foreach ($items as $delta => $item) {
      if (isset($element[$delta]['#url'])) {
        $element[$delta]['#url']->setAbsolute();
      }
    }
    return $element;
  }

}
