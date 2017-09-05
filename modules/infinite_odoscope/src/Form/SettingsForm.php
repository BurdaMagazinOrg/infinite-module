<?php

/**
 * @file
 * Contains Drupal\infinite_odoscope\Form\SettingsForm.
 */

namespace Drupal\infinite_odoscope\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\infinite_odoscope\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'infinite_odoscope.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'infinite_odoscope_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('infinite_odoscope.settings');

    $form['odoscope_user'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Odoscope user'),
      '#description' => $this->t('Used for uploading CSV updates.'),
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $config->get('odoscope_user'),
    );
    $form['odoscope_pass'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Odoscope password'),
      '#description' => $this->t('Used for uploading CSV updates.'),
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $config->get('odoscope_pass'),
    );
    $form['odoscope_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Odoscope URL'),
      '#description' => $this->t('Used for uploading CSV updates.'),
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $config->get('odoscope_url'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('infinite_odoscope.settings')
      ->set('odoscope_user', $form_state->getValue('odoscope_user'))
      ->save();

    $this->config('infinite_odoscope.settings')
      ->set('odoscope_pass', $form_state->getValue('odoscope_pass'))
      ->save();

    $this->config('infinite_odoscope.settings')
      ->set('odoscope_url', $form_state->getValue('odoscope_url'))
      ->save();
  }
}
