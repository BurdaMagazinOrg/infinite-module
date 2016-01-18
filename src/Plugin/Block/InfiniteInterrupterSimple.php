<?php

namespace Drupal\infinite_base\Plugin\Block;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\block\Entity\Block;

/**
 * Provides a Advertising slot.
 *
 * @Block(
 *   id = "infinite_base_interrupter_simple",
 *   admin_label = @Translation("Interrupter Simple"),
 * )
 */
class InfiniteInterrupterSimple extends BlockBase implements ContainerFactoryPluginInterface {

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
      'quantity' => 10,
    ];
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['quantity'] = [
      '#type' => 'number',
      '#title' => t('Quantity'),
      '#description' => t('Number of configurable interrupt blocks.'),
      '#default_value' => $config['quantity'],
    ];

    $interrupt_blocks = array();
    for($i = 0; $i < $config['quantity']; $i++) {
      if (!empty($config['interrupt_blocks'][$i])) {
        if ($config['interrupt_blocks'][$i]) {
          $block = Block::load($config['interrupt_blocks'][$i]);
          if (is_object($block)) {
            $interrupt_blocks[$i] = $block;
          }
        }
      }

      $form['interrupt_blocks'][$i] = array(
        '#title' => $i + 1 . '. Interrupter Block',
        '#type' => 'entity_autocomplete',
        '#selection_handler' => 'default',
        '#target_type' => 'block',
        '#selection_settings' => array('target_bundles' => NULL),
        '#default_value' => $interrupt_blocks[$i] ? $interrupt_blocks[$i] : NULL,
        '#placeholder' => t('Select interrupter block'),
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Save our custom settings when the form is submitted.
    $this->setConfigurationValue('quantity', $form_state->getValue('quantity'));
    $this->setConfigurationValue('interrupt_blocks', $form_state->getValue('interrupt_blocks'));
  }

  public function build() {
    $config = $this->getConfiguration();
    $quantity = $config['quantity'];
    $interrupt_blocks = $config['interrupt_blocks'];

    $key = 0;
    if (\Drupal::request()->query->has('page')) {
      $key = \Drupal::request()->query->getInt('page');
    }

    if ($key < $quantity) {
      if ($interrupt_blocks[$key]) {
        $block = Block::load($interrupt_blocks[$key]);
        if (is_object($block)) {
          $block_content = \Drupal::entityManager()
            ->getViewBuilder('block')
            ->view($block, 'default');

          return array(
            '#markup' => \Drupal::service('renderer')->render($block_content),
            '#cache' => [
              'contexts' => ['url.query_args'],
            ],
          );
        }
      }
    }
    return array('#cache' => ['contexts' => ['url.query_args']]);
  }
}
