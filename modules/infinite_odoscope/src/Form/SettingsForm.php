<?php

/**
 * @file
 * Contains Drupal\infinite_odoscope\Form\SettingsForm.
 */

namespace Drupal\infinite_odoscope\Form;

use Drupal;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use function drupal_flush_all_caches;
use function drupal_set_message;
use Exception;
use function file_put_contents;
use GuzzleHttp\Exception\ClientException;
use function implode;
use function infinite_odoscope_update_library_source;

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

    $library_source = Drupal::config('infinite_odoscope.settings')->get('library_source');
    $library_source_description = [
      'The source of the odoscope js library.',
      'This value is normally set in settings.php and will not be stored.',
      'You can override it here for one update.',
      'To download the new library source you have to check the "Update odoscope library" checkbox below.'
    ];

    $form['library_source'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Odoscope library source'),
      '#description' => $this->t(implode(' ', $library_source_description)),
      '#default_value' => isset($library_source) ? $library_source : $config->get('library_source'),
    );

    $form['library_update'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Update odoscope library'),
      '#description' => $this->t('Download a new version of the odoscope library.'),
      '#default_value' => FALSE,
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

    if($form_state->getValue('library_update')) {
      $library_source = $form_state->getValue('library_source');
      $client = Drupal::httpClient();
      try {
        $response = $client->get($library_source);

        if($response->getStatusCode() == 200) {
          $response_body = $response->getBody()->getContents();
          $form_state->setValue('library_source', $response_body);
        }
        else {
          $form_state->setError($form['library_source'], $this->t('The odoscope library could not be updated. Response status code: %code for URL %url', [
            '%code' => $response->getStatusCode(),
            '%url' => $library_source
          ]));
        }

      }
      catch (ClientException $e) {
        $response = $e->getResponse();

        $form_state->setError($form['library_source'], $this->t('The odoscope library could not be updated. Response status code: %code for URL %url', [
          '%code' => $response->getStatusCode(),
          '%url' => $library_source,
        ]));
      }
      catch (Exception $e) {
        $form_state->setError($form['library_source'], $this->t('The odoscope library could not be updated. %message', [
          '%message' => $e->getMessage(),
        ]));
      }
    }
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

    $this->config('infinite_odoscope.settings')
      ->set('library_source', $form_state->getValue('library_source'))
      ->save();

    if($form_state->getValue('library_update')) {
      infinite_odoscope_update_library_source($form_state->getValue('library_source'), 'public://odoscope/odoscope.main.js');
      drupal_set_message($this->t('The odoscope library was updated successfully.'));
    }

    if($this->config('infinite_odoscope.settings')
      ->get('odoscope_enabled') != $form_state->getValue('odoscope_enabled')){
      $this->config('infinite_odoscope.settings')
        ->set('odoscope_enabled', $form_state->getValue('odoscope_enabled'))
        ->save();

      drupal_flush_all_caches();
    }
  }
}
