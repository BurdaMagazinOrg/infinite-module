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
    $form['ad_injection_settings'] = [
      '#type' => 'vertical_tabs',
      'default_tab' => 'article_settings'
    ];
    $form['article_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Article settings'),
      '#group' => 'ad_injection_settings'
    ];
    $form['article_settings']['first_article_ad_injection'] = [
      '#type' => 'number',
      '#title' => $this->t('First Ad injection'),
      '#description' => $this->t('After how many paragraphs, inject the first ad'),
      '#default_value' => $config->get('first_article_ad_injection'),
    ];
    $form['article_settings']['each_article_ad_injection'] = [
      '#type' => 'number',
      '#title' => $this->t('Add ads each n. paragraphs'),
      '#description' => $this->t('Add ads every each n. paragraphs'),
      '#default_value' =>  $config->get('each_article_ad_injection'),
    ];

    $form['term_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Term settings'),
      '#group' => 'ad_injection_settings'
    ];
    $form['term_settings']['first_term_ad_injection'] = [
      '#type' => 'number',
      '#title' => $this->t('First Ad injection'),
      '#description' => $this->t('After how many paragraphs, inject the first ad'),
      '#default_value' => $config->get('first_term_ad_injection'),
    ];
    $form['term_settings']['each_term_ad_injection'] = [
      '#type' => 'number',
      '#title' => $this->t('Add ads each n. paragraphs'),
      '#description' => $this->t('Add ads every each n. paragraphs'),
      '#default_value' =>  $config->get('each_term_ad_injection'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    parent::submitForm($form, $form_state);

    $this->config('infiniteAdInjection.adminsettings')
      ->set('first_article_ad_injection', $form_state->getValue('first_article_ad_injection'))
      ->set('each_article_ad_injection', $form_state->getValue('each_article_ad_injection'))
      ->set('first_term_ad_injection', $form_state->getValue('first_term_ad_injection'))
      ->set('each_term_ad_injection', $form_state->getValue('each_term_ad_injection'))
      ->save();
  }
}