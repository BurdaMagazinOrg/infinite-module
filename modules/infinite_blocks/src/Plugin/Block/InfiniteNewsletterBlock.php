<?php

/**
 * @file
 * Contains \Drupal\infinite_blocks\Plugin\Block\InfiniteSocialsBlock.
 */

namespace Drupal\infinite_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 * @Block(
 *   id = "infinite_blocks_newsletter",
 *   admin_label = @Translation("Newsletter Block")
 * )
 */
class InfiniteNewsletterBlock extends BlockBase {

  protected $theme = 'newsletter';

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = $this->getConfiguration();

    return array(
      '#theme' => $this->theme,
      '#groupId' => !empty($config['group_id']) ? $config['group_id'] : ''
    );

  }

  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['group_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Group Id'),
      '#default_value' => !empty($config['group_id']) ? $config['group_id'] : '',
      '#size' => 4,
      '#maxlength' => 16,
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('group_id', $form_state->getValue('group_id'));
  }

}
