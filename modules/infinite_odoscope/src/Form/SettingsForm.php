<?php

/**
 * @file
 * Contains Drupal\infinite_odoscope\Form\SettingsForm.
 */

namespace Drupal\infinite_odoscope\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use function drupal_flush_all_caches;

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
      '#default_value' => $config->get('odoscope_user'),
    );
    $form['odoscope_pass'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Odoscope password'),
      '#description' => $this->t('Used for uploading CSV updates.'),
      '#maxlength' => 128,
      '#default_value' => $config->get('odoscope_pass'),
    );
    $form['odoscope_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Odoscope URL'),
      '#description' => $this->t('Used for uploading CSV updates.'),
      '#maxlength' => 128,
      '#default_value' => $config->get('odoscope_url'),
    );
    $enabled = $config->get('odoscope_enabled');
    $form['odoscope_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Odoscope enabled'),
      '#description' => $this->t('Check this to enable odoscope library. Warning: changing this value will flush all drupal caches.'),
      '#default_value' => isset($enabled) ? $config->get('odoscope_enabled') : TRUE,
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

    if($this->config('infinite_odoscope.settings')
      ->get('odoscope_enabled') != $form_state->getValue('odoscope_enabled')){
      $this->config('infinite_odoscope.settings')
        ->set('odoscope_enabled', $form_state->getValue('odoscope_enabled'))
        ->save();

      drupal_flush_all_caches();
    }
  }
}
