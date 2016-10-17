<?php

/**
 * @file
 * Contains \Drupal\infinite_base\Form\FilterExternalLinksForm.
 */

namespace Drupal\infinite_base\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configures aggregator settings for this site.
 */
class FilterExternalLinksForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['infinite_base.filter_external_links'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'infinite_base_filter_external_links_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('infinite_base.filter_external_links');

    // Global aggregator settings.
    $form['internal_domains'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Internal domains'),
      '#size' => 80,
      '#maxlength' => 255,
      '#default_value' => implode("\r\n", $config->get('internal_domains')),
      '#description' => $this->t('A list of domains, one per line. Links to those domains will not automatically get rel="nofollow" and target="_blank" attributes.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('infinite_base.filter_external_links');
    $config->set('internal_domains', array_values(array_filter(array_map('trim', explode("\r\n", $form_state->getValue('internal_domains'))))));
    $config->save();
  }

}
