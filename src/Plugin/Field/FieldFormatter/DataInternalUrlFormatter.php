<?php

namespace Drupal\infinite_base\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Element;

/**
 * Plugin implementation of the 'data internal URL' formatter.
 *
 * @FieldFormatter(
 *   id = "data_internal_url",
 *   label = @Translation("Data Internal URL"),
 *   description = @Translation("Render link as JS clickable data-url"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class DataInternalUrlFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $label = $entity->label();
      $elements[$delta] = array(
        '#theme' => 'data_internal_url',
        '#url' => $entity->url('canonical', array('absolute' => TRUE)),
        '#label' => $label,
      );
      $elements[$delta]['#cache']['tags'] = $entity->getCacheTags();
    }

    return $elements;
  }

//  public function view(FieldItemListInterface $items) {
//    $elements = $this->viewElements($items);
////
//    // If there are actual renderable children, use #theme => field, otherwise,
//    // let access cacheability metadata pass through for correct bubbling.
//    if (Element::children($elements)) {
//      $entity = $items->getEntity();
//      $entity_type = $entity->getEntityTypeId();
//      $field_name = $this->fieldDefinition->getName();
//      $info = array(
//        '#theme' => 'data_internal_url',
//        '#title' => $this->fieldDefinition->getLabel(),
//        '#label_display' => $this->label,
//        '#view_mode' => $this->viewMode,
//        '#language' => $items->getLangcode(),
//        '#field_name' => $field_name,
//        '#field_type' => $this->fieldDefinition->getType(),
//        '#field_translatable' => $this->fieldDefinition->isTranslatable(),
//        '#entity_type' => $entity_type,
//        '#bundle' => $entity->bundle(),
//        '#object' => $entity,
//        '#items' => $items,
//        '#elements' => $elements,
//        '#formatter' => $this->getPluginId(),
//      );
//
//      $elements = array_merge($info, $elements);
//    }
//
//    return $elements;
//  }
//
}
