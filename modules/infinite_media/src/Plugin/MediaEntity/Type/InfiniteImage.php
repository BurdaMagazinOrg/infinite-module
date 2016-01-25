<?php
/**
 * Contains \Drupal\infinite_media\Plugin\MediaEntity\Type\InfiniteImage.
 */
namespace Drupal\infinite_media\Plugin\MediaEntity\Type;
use Drupal\Core\Config\Config;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\media_entity\MediaBundleInterface;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
/**
 * Provides generic media type.
 *
 * @MediaType(
 *   id = "infinite_image",
 *   label = @Translation("Infinite image media"),
 *   description = @Translation("Infinite image media type.")
 * )
 */
class InfiniteImage extends MediaTypeBase {
  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   * @param \Drupal\Core\Config\Config $config
   *   Media entity config object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ImageFactory $image_factory, Config $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $config);
    $this->imageFactory = $image_factory;
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
      $container->get('entity_field.manager'),
      $container->get('image.factory'),
      $container->get('config.factory')->get('media_entity.settings')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    return array();
  }
  /**
   * {@inheritdoc}
   */
  public function getField(MediaInterface$media, $name) {
    return FALSE;
  }
  /**
   * {@inheritdoc}
   */
  public function settingsForm(MediaBundleInterface $bundle) {
    return array();
  }
  /**
   * {@inheritdoc}
   */
  public function validate(MediaInterface $media) { }
  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    $source_field = 'field_image';
    /** @var \Drupal\file\FileInterface $file */
    $file = $this->entityTypeManager->getStorage('file')
      ->load($media->{$source_field}->target_id);
    if (!$file) {
      return $this->config->get('icon_base') . '/image.png';
    }
    return $file->getFileUri();
  }
}