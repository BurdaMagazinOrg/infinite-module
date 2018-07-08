<?php

namespace Drupal\infinite_fashwell\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Plugin implementation of the 'infinite_fashwell_autocomplete_widget' widget.
 *
 * @FieldWidget(
 *   id = "infinite_fashwell_autocomplete_widget",
 *   label = @Translation("Autocomplete for advertising products and Fashwell"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class InfiniteFashwellAutocompleteWidget extends EntityReferenceAutocompleteWidget {
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['target_id']['#selection_handler'] = 'advertising_products:product';

    $value = $items->getValue();
    if ($value && is_array($value) && isset($value[$delta])
      && isset($value[$delta]['target_id']) && is_numeric($value[$delta]['target_id'])
    ) {
      $product = \Drupal::entityTypeManager()->getStorage('advertising_product')->load($value[$delta]['target_id']);
      if ($product) {
        $image_id = $product->get('product_image')->target_id;
        $image = \Drupal::entityTypeManager()->getStorage('file')->load($image_id);
        $image_url = file_create_url($image->getFileUri());

        $element['target_id']['#attributes']['class'][] = 'fashwell';
        $element['target_id']['#attributes']['data-product-title'] = $product->get('product_name')->value;
        $element['target_id']['#attributes']['data-product-image'] = $image_url;
        $element['target_id']['#field_suffix'] = '<a class="fashwell-alt" target="_blank">' . t('Fashwell alternative') . '</a>';
      }
    }
    return $element;
  }
}
