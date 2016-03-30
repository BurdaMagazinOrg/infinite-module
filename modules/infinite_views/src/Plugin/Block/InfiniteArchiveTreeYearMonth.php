<?php

namespace Drupal\infinite_views\Plugin\Block;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Provides a Archive tree grouped by year and month.
 *
 * @Block(
 *   id = "infinite_views_archive_tree_year_month",
 *   admin_label = @Translation("Archive tree (per Year / Month)"),
 * )
 */
class InfiniteArchiveTreeYearMonth extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  protected $dbConnection;

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
   * @param QueryFactory $entity_query
   *   The entity query factory
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    Connection $dbConnection
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->dbConnection = $dbConnection;
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
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('config.factory'), $container->get('database'));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = [
      'archive_view_path' => '/archive',
    ];
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $form['archive_view_path'] = [
      '#type' => 'textfield',
      '#title' => t('Archive '),
      '#description' => t('Enter required path of archive view without trailing slash. Default: /archive'),
      '#default_value' => $config['archive_view_path'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('archive_view_path', $form_state->getValue('archive_view_path'));
  }

  public function build() {
    $config = $this->getConfiguration();
    $archive_view_path = $config['archive_view_path'];

    //Lookup 1 oldest published article node.
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('status', 1)
      ->sort('created', 'ASC')
      ->range(0, 1)
      ->execute();

    $nids = array();
    foreach ($query as $row) {
      $nids[] = (int) $row;
    }

    //Lookup 1 recent published article node.
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->execute();

    foreach ($query as $row) {
      $nids[] = (int) $row;
    }

    if (!empty($nids)) {
      $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
      $oldest_created = $nodes[$nids[0]]->getCreatedTime();
      $recent_created = $nodes[$nids[1]]->getCreatedTime();

      $oldest_year = \Drupal::service('date.formatter')->format($oldest_created, 'custom', 'Y');
      $oldest_month = \Drupal::service('date.formatter')->format($oldest_created, 'custom', 'm');

      $recent_year = \Drupal::service('date.formatter')->format($recent_created, 'custom', 'Y');
      $recent_month = \Drupal::service('date.formatter')->format($recent_created, 'custom', 'm');

      $months = array();
      for ($month = 1; $month <= 12; $month++) {
        $month_timestamp = mktime(0, 0, 0, $month, 1);
        $month_id = \Drupal::service('date.formatter')
          ->format($month_timestamp, 'custom', 'm');
        $month_short_name = \Drupal::service('date.formatter')
          ->format($month_timestamp, 'custom', 'M');
        $month_name = \Drupal::service('date.formatter')
          ->format($month_timestamp, 'custom', 'F');

        $months[$month] = array(
          'id' => $month_id,
          'short_name' => $month_short_name,
          'name' => $month_name,
        );
      }

      $archive_tree = array();
      for ($year = $recent_year; $year >= $oldest_year; $year--) {
        for ($month = 1; $month <= 12; $month++) {
          $archive_tree[$year][$month] = array(
            'month' => $months[$month]['id'],
            'link_text'	=> $months[$month]['short_name'],
            'link_title' => 'Artikel im ' . $months[$month]['name'] . ' ' . $year,
            'url'  => TRUE,
            'year'	=> $year,
          );

          if ($year == $recent_year && $month > $recent_month) {
            $archive_tree[$year][$month]['url'] = FALSE;
          }

          if ($year == $oldest_year && $month < $oldest_month) {
            $archive_tree[$year][$month]['url'] = FALSE;
          }
        }
      }
    }

    if (!empty($archive_tree)) {

      $archive_years = array();
      foreach ($archive_tree as $year => $months) {

        $year_months = array();
        foreach ($months as $month) {
          if ($month['url']) {
            $archive_link = Url::fromUri('base:/' . $archive_view_path .  '/' . $year . $month['month'], array('absolute' => TRUE));
            $year_months[] = [
              '#markup' => \Drupal::l(t($month['link_text']), $archive_link),
              '#wrapper_attributes' => array('class' => array('list__item--month')),
            ];
          }
          else {
            $year_months[] = [
              '#markup' => $month['link_text'],
              '#wrapper_attributes' => array('class' => array('list__item--month')),
            ];
          }
        }

        $archive_years[] = array(
          '#markup' => '<span class="title--list">Artikel ' . $year .'</span>',
          'children' => $year_months,
          '#wrapper_attributes' => array('class' => array('list__item--year')),
        );
      }

      return array(
        '#theme' => 'item_list',
        '#items' => $archive_years,
        '#title' => '',
        '#type' => 'ul',
        '#wrapper_attributes' => array('class' => array('archive-tree')),
      );
    }
    return FALSE;
  }
}
