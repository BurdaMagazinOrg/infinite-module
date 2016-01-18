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
 *   id = "infinite_blocks_socials",
 *   subject = @Translation("test: empty block"),
 *   admin_label = @Translation("Socials Block")
 * )
 */

class InfiniteSocialsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    return array(
      '#theme' => 'socials_bar',
      '#follow' => !empty($config['follow']) ? true : false,
      'variables' => [],
    );
  }

  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    //Retrieve existing configuration for this block.
    $config = $this->getConfiguration();

    $form['follow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link Attribute - Follow'),
      '#default_value' => $config['follow'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['follow'] = $form_state->getValue('follow');
  }

}
