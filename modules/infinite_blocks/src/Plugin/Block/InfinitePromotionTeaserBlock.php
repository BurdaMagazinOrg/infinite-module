<?php

namespace Drupal\infinite_blocks\Plugin\Block;


use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 * @Block(
 *   id = "infinite_blocks_promotion_teaser",
 *   admin_label = @Translation("Promotion Teaser Block")
 * )
 */
class InfinitePromotionTeaserBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $parameters = $this->currentRouteMatch->getParameters();
    $entity = reset(reset($parameters));

    $cache = ['contexts' => ['url.path']];

    if(isset($entity) && $entity instanceof FieldableEntityInterface){
      $cache['tags'] = $entity->getCacheTags();
      if ($entity->hasField('field_promotion_teaser_disable') && $entity->get('field_promotion_teaser_disable')->value) {
        return ['#cache' => $cache];
      }
    }

    return [
      '#theme' => 'block',
      '#cache' => $cache
    ];
  }

}