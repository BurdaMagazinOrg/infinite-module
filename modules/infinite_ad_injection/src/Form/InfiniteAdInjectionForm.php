<?php
namespace Drupal\infinite_ad_injection\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class InfiniteAdInjectionForm extends ConfigFormBase
{

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return [
      'infiniteAdInjection.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'infinite_ad_injection_configs';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('infiniteAdInjection.adminsettings');
    $form['article_injection_settings'] = [
      '#type' => 'textarea',
      '#title' => $this->t('First Ad injection'),
      '#description' => $this->t('After how many paragraph inject the first ad'),
      '#default_value' => $config->get('article_injection_settings'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('infiniteAdInjection.adminsettings')
      ->set('article_injection_settings', $form_state->getValue('article_injection_settings'))
      ->save();
  }
}
