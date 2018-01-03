<?php

namespace Drupal\infinite_adstxt\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures ad settings.
 */
class AdsTxtSettingsForm extends ConfigFormBase {


  /**
   * Constructs a \Drupal\infinite_base\AdsTxtSettingsForm object.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param Token $token
   *   The token object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adstxt_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->config('infinite_adstxt.settings');

    $form['adstxt_content'] = array(
      '#type' => 'textarea',
      '#rows' => 30,
      '#default_value' => $settings->get('adstxt_content'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $values = $form_state->getValues();
    $config = $this->configFactory()->getEditable('infinite_adstxt.settings');
    $config->set('adstxt_content', $values['adstxt_content'])->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'infinite_adstxt.settings',
    ];
  }

}
