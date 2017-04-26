<?php

namespace Drupal\infinite_blocks\Plugin\Block;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Provides a Outbrain block.
 *
 * @Block(
 *   id = "infinite_blocks_outbrain",
 *   admin_label = @Translation("Outbrain"),
 * )
 */
class InfiniteOutbrainBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an advertising slot object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = [
      'ob_class' => 'OUTBRAIN',
      'ob_widget_quantity' => 3,
      'ob_widget_ids' => NULL,
      'ob_template' => NULL,
    ];
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['ob_class'] = [
      '#type' => 'textfield',
      '#title' => t('Outbrain CSS class'),
      '#description' => t('Enter required Outbrain css class. Default: OUTBRAIN'),
      '#default_value' => $config['ob_class'],
      '#required' => TRUE,
    ];
    $form['ob_widget_quantity'] = [
      '#type' => 'number',
      '#title' => t('Quantity'),
      '#description' => t('Number of configurable Outbrain widgets blocks.'),
      '#default_value' => $config['ob_widget_quantity'],
    ];

    for($i = 0; $i < $config['ob_widget_quantity']; $i++) {
      $form['ob_widget_ids'][$i] = [
        '#type' => 'textfield',
        '#title' => t('Outbrain Widget ID'),
        '#description' => t('Enter required Outbrain data for widget ID. For example: AR_1'),
        '#default_value' => $config['ob_widget_ids'][$i],
        '#required' => TRUE,
      ];
    }

    $form['ob_template'] = [
      '#type' => 'textfield',
      '#title' => t('Outbrain Template'),
      '#description' => t('Enter required Outbrain data for template information. For example: DE_InStyle'),
      '#default_value' => $config['ob_template'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Save our custom settings when the form is submitted.
    $this->setConfigurationValue('ob_class', $form_state->getValue('ob_class'));
    $this->setConfigurationValue('ob_widget_quantity', $form_state->getValue('ob_widget_quantity'));
    $this->setConfigurationValue('ob_widget_ids', $form_state->getValue('ob_widget_ids'));
    $this->setConfigurationValue('ob_template', $form_state->getValue('ob_template'));
  }

  public function build() {
    $cache = ['contexts' => ['url.path']];
    $node_url = NULL;  // todo: check with outbrain how to handle data-src = NULL?
    $config = $this->getConfiguration();

    if ($node = \Drupal::request()->attributes->get('node')) {

      $path_alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node->id());
      $node_url = Url::fromUri('base:/' . $path_alias, array('absolute' => TRUE))->toString();

      return [
        '#theme' => 'outbrain',
        '#outbrain' => [
          'class' => $config['ob_class'],
          'template' => $config['ob_template'],
          'node_url' => $node_url,
          'widget_ids' => $config['ob_widget_ids'],
        ],
        '#attached' => [
          'library' => [
            'infinite_blocks/outbrain_js',
          ],
        ],
        '#cache' => [
          'tags' => [
            'node:' . $node->id(),
          ],
          'contexts' => ['url.path'],
        ],
      ];
    }

    return ['#cache' => $cache]; // needed to avoid global caching of block.
  }
}
