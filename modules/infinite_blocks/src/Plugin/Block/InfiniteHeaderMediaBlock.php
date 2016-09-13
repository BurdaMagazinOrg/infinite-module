<?php

namespace Drupal\infinite_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 * @Block(
 *   id = "infinite_blocks_header_media",
 *   admin_label = @Translation("Header Media Block")
 * )
 */
class InfiniteHeaderMediaBlock extends BlockBase implements ContainerFactoryPluginInterface  {

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * @var array
   *    Router parameter names that can be used as source for the "context" entity.
   *    First one available will be used, search order is from first to last.
   */
  const supportedEntityTypes = ['node', 'taxonomy_term', 'user'];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration, $plugin_id, $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager,
    EntityDisplayRepositoryInterface $entityDisplayRepository,
    RouteMatchInterface $currentRouteMatch) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityDisplayRepository = $entityDisplayRepository;
    $this->currentRouteMatch = $currentRouteMatch;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    foreach (static::supportedEntityTypes as $type) {
      if ($entity = $this->currentRouteMatch->getParameter($type)) {
        break;
      }
    }

    $cache = ['contexts' => ['url.path']];
    if (isset($entity)) {

      $headerMedia = NULL;
      $cache['tags'] = $entity->getCacheTags();

      if ($entity->hasField('field_header_media') && !$entity->get('field_header_media')->isEmpty()) {
        $media = $entity->get('field_header_media')->entity;
        if (!empty($media)) {
          $headerMedia = $this->entityTypeManager
            ->getViewBuilder('media')
            ->view($media, $this->configuration['media_view_mode']);

          $headerTitle = $entity->label();
        }
      }

      if ($entity->hasField('field_header_title') && !$entity->get('field_header_title')->isEmpty()) {
        $headerTitle = $entity->get('field_header_title')->value;
      }

    }

    if (!empty($headerMedia) || !empty($headerTitle)) {
      return array(
        '#theme' => 'header_media',
        '#header_media' => $headerMedia,
        '#header_title' => $headerTitle,
        '#cache' => $cache,
      );
    }

    return ['#cache' => $cache]; // needed to avoid global caching of block without header media.

  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $viewModes = $this->getEntityViewModeOptions('media', 'image');
    return parent::defaultConfiguration() + [
      // we use header as a default b/c this was the hard coded view mode
      // before it was selectable, so it should be there
      'media_view_mode' => isset($viewModes['header']) ? 'header' : current($viewModes),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $formState) {
    $form['media_view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Display view mode for media entity'),
      '#options' => $this->getEntityViewModeOptions('media', 'image'),
      '#default_value' => $this->configuration['media_view_mode'],
    ];

    return parent::blockForm($form, $formState) + $form;
  }

  public function blockValidate($form, FormStateInterface $formState) {
    if (!array_key_exists($formState->getValue('media_view_mode'), $this->getEntityViewModeOptions('media', 'image'))) {
      $formState->setErrorByName('media_view_mode', $this->t('The view mode @viewMode is not available', ['@viewMode' => $formState->getValue('media_view_mode')]));
    }
    parent::blockValidate(
      $form,
      $formState
    );
  }

  public function blockSubmit($form, FormStateInterface $formState) {
    $this->configuration['media_view_mode'] = $formState->getValue('media_view_mode');
    parent::blockSubmit(
      $form,
      $formState
    );
  }

  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    if ($viewMode = $this->configuration['media_view_mode']) {
      $viewModeInfo = $this->getEntityViewModeInfo('media', 'image');
      if (isset($viewModeInfo[$viewMode])) {
        // TODO is there a facility for getting the config entity key?
        $dependencies['config'][] = join('.', ['core', 'entity_view_mode', $viewModeInfo[$viewMode]['id']]);
      }
    }
    return $dependencies;
  }


  /**
   * Returns select options array for all view modes for a given entity type and bundle.
   *
   * @param string $entityType
   *    entity type string
   * @param string $bundle
   *    entity bundle name
   *
   * @return array
   *    Available view mode names
   */
  protected function getEntityViewModeOptions($entityType, $bundle) {
    return $this->entityDisplayRepository->getViewModeOptionsByBundle($entityType, $bundle);
  }

  /**
   * Returns view info all view modes for a given entity type and bundle.
   *
   * "Emulates" EntityDisplayRepositoryInterface::getViewModesByBundle() (which does not exist)
   * without re-implementing the functionality.
   *
   * @param string $entityType
   *    entity type string
   * @param string $bundle
   *    entity bundle name
   *
   * @return array
   *    Info for available view modes
   */
  protected function getEntityViewModeInfo($entityType, $bundle) {
    return array_intersect_key(
      $this->entityDisplayRepository->getViewModes($entityType),
      $this->getEntityViewModeOptions($entityType, $bundle)
    );
  }

}
