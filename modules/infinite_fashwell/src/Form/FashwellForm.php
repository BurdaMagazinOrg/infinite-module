<?php 

namespace Drupal\infinite_fashwell\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class FashwellForm extends ConfigFormBase {

  public function getFormId() {
    return 'fashwell_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('infinite_fashwell.settings');

    $form['api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fashwell API token'),
      '#default_value' => $config->get('api_token')
    ];

    $form['api_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Fashwell API URL'),
      '#default_value' => $config->get('api_url')
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('infinite_fashwell.settings');
    $config->set('api_token', $form_state->getValue('api_token'));
    $config->set('api_url', $form_state->getValue('api_url'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  protected function getEditableConfigNames() {
    return [
      'infinite_fashwell.settings',
    ];
  }
  
}
