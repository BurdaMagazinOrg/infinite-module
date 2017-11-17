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
        $entity_type = 'advertising_product';
        $entity_id = $value['product']; // static for example purpose
        $view_mode = 'facebook_instant_articles_rss';

        $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
        $pre_render = $view_builder->view($entity, $view_mode);
        $render_output = render($pre_render);

        return ['#markup' => $value['product']];
    }

}
