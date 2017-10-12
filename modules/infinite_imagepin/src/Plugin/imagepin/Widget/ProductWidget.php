<?php

namespace Drupal\infinite_imagepin\Plugin\imagepin\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\imagepin\Plugin\WidgetBase;

/**
 * The text widget plugin.
 *
 * @Product(
 *   id = "product",
 *   label = @Translation("Text"),
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
      '#type' => 'textfield',
      '#title' => t('Text').'ccsdf',
      '#required' => FALSE,
      '#weight' => 10,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function previewContent($value) {
    return ['#markup' => $value['text']];
  }

  /**
   * {@inheritdoc}
   */
  public function viewContent($value) {
    return ['#markup' => $value['text']];
  }

}
