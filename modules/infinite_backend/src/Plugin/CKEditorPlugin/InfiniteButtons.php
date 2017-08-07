<?php

namespace Drupal\infinite_backend\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;

/**
 * Defines the "infinite_buttons" plugin.
 *
 * @CKEditorPlugin(
 *   id = "infinite_buttons",
 *   label = @Translation("Infinite Backend"),
 *   module = "infinite_backend"
 * )
 */
class InfiniteButtons extends CKEditorPluginBase {

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile().
   */
  public function getFile() {
    return drupal_get_path('module', 'infinite_backend') . '/js/plugins/infinite_buttons/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'infinite_buttons' => array(
        'label' => t('Bootstrap Buttons'),
        'image' => drupal_get_path('module', 'infinite_backend') . '/js/plugins/infinite_buttons/icons/infinite_buttons.png',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array();
  }

}
