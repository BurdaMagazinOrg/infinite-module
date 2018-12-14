<?php

namespace Drupal\infinite_ad_injection\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class InfiniteAdInjectionForm extends ConfigFormBase
{
  /**
   * @var array Ad injection type configuration required
   *            This variable is used inside the class to build dynamically the form (buildForm method)
   *            settings configurations are created dynamically, based on the array key (content type):
   *            first_{$type}_ad_injection => indicates the first paragraph after which put the first ads
   *            each_{$type}_ad_injection => indicates after each paragraph after which put the first ads
   */
  protected $adInjectionTypes = [
    'article',
    'term',
    'viversum_horoscope'
  ];

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
    $config = $this->config('infinite_ad_injection.settings');
    //create a tab element
    $form['ad_enable_debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debug'),
      '#default_value' => $config->get("ad_enable_debug"),
    ];
    $form['ad_injection_settings'] = [
      '#type' => 'vertical_tabs',
      'default_tab' => 'article_settings'
    ];

    //populate the tab element with required configurations
    foreach ($this->adInjectionTypes as $type) {
      $form["{$type}_settings"] = [
        '#type' => 'details',
        '#title' => $this->t(ucfirst("{$type} settings")),
        '#group' => 'ad_injection_settings'
      ];
      $form["{$type}_settings"]["first_{$type}_ad_injection"] = [
        '#type' => 'number',
        '#title' => $this->t('First Ad injection'),
        '#description' => $this->t('After how many paragraphs, inject the first ad'),
        '#default_value' => $config->get("first_{$type}_ad_injection"),
      ];
      $form["{$type}_settings"]["each_{$type}_ad_injection"] = [
        '#type' => 'number',
        '#title' => $this->t('Add ads each n. paragraphs'),
        '#description' => $this->t('Add ads every each n. paragraphs'),
        '#default_value' => $config->get("each_{$type}_ad_injection"),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    parent::submitForm($form, $form_state);

    $config = $this->config('infinite_ad_injection.settings');
    $config->set("ad_enable_debug", $form_state->getValue("ad_enable_debug"));
    foreach ($this->adInjectionTypes as $type) {
      $config->set("first_{$type}_ad_injection", $form_state->getValue("first_{$type}_ad_injection"));
      $config->set("each_{$type}_ad_injection", $form_state->getValue("each_{$type}_ad_injection"));
    }
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return [
      'infinite_ad_injection.settings',
    ];
  }

}
