<?php

namespace Drupal\infinite_imagepin\Plugin\imagepin\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\imagepin\Plugin\WidgetBase;

/**
 * The text widget plugin.
 *
 * @Widget(
 *   id = "product",

 *   label = @Translation("Product"),
 * )
 */
class ProductWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formNewElement(array &$form, FormStateInterface $form_state) {
    $element = [];

    // TODO Required fields currently don't work.
    // Form API documentation lacks here, again.
    $element['product'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'advertising_product',
      '#title' => t('Product'),
      '#required' => FALSE,
      '#weight' => 10,

    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function previewContent($value) {
    return ['#markup' => $value['product']];
  }

  /**
   * {@inheritdoc}
   */
  public function viewContent($value) {
     return ['#markup' => $value['product']];
  }

}
