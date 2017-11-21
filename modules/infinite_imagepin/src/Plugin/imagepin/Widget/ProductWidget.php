<?php

namespace Drupal\infinite_imagepin\Plugin\imagepin\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\imagepin\Plugin\WidgetBase;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\advertising_products\Plugin\Field\FieldWidget;


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

        $element['product'] = [
            '#type' => 'entity_autocomplete',
            '#target_type' => 'advertising_product',
            '#selection_handler' => 'advertising_products:product',
            '#title' => t('Product'),
            '#required' => FALSE,
            '#weight' => 10,

        ];

				$element['product2'] = [
					'#type' => 'entity_autocomplete',
					'#target_type' => 'advertising_product',
					'#selection_handler' => 'advertising_products:product',
					'#title' => t('Product2'),
					'#required' => FALSE,
					'#weight' => 10,

				];

        return $element;
    }

    /**
     * {@inheritdoc}
     */
    public function previewContent($value) {
        return ['#markup' => $value['product'].'('.$value['product2'].')'];
    }

    /**
     * {@inheritdoc}
     */
    public function viewContent($value) {
        return ['product_one' => $value['product'],'product_two' => $value['product2']];
    }

}
