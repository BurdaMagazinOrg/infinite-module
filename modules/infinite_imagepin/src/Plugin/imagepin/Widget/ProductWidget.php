<?php

namespace Drupal\infinite_imagepin\Plugin\imagepin\Widget;

use Drupal\Core\Form\FormBase;
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
            '#title' => t('Design Product'),
            '#required' => FALSE,
            '#weight' => 10,
						'#maxlength' => 1000,

        ];

			$element['product2'] = [
					'#type' => 'entity_autocomplete',
					'#target_type' => 'advertising_product',
					'#selection_handler' => 'advertising_products:product',
					'#title' => t('Alternative Product'),
					'#required' => FALSE,
					'#weight' => 10,
					'#maxlength' => 1000,


			];




        return $element;
    }

	public function columnCallback(array &$form, FormStateInterface $form_state) {
		#return $form['wrapper'];
	}

    /**
     * {@inheritdoc}
     */
    public function previewContent($value) {
        #return ['#markup' => $value['product'].'('.$value['product2'].')'];
				return ['product_one' => $value['product'],'product_two' => $value['product2']];
    }

    /**
     * {@inheritdoc}
     */
    public function viewContent($value) {
        return ['product_one' => $value['product'],'product_two' => $value['product2']];
    }

}
