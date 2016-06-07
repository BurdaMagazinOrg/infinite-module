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
      '#facebook_url' => $config['facebook_url'],
      '#instagram_url' => $config['instagram_url'],
      '#pinterest_url' => $config['pinterest_url'],
      '#twitter_url' => $config['twitter_url'],
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
    $form['facebook_url'] = [
      '#type' => 'textfield',
      '#title'=> $this->t('Facebook URL'),
      '#default_value' => $config['facebook_url']
    ];
    $form['instagram_url'] = [
      '#type' => 'textfield',
      '#title'=> $this->t('Instagram URL'),
      '#default_value' => $config['instagram_url']
    ];
    $form['pinterest_url'] = [
      '#type' => 'textfield',
      '#title'=> $this->t('Pinterest URL'),
      '#default_value' => $config['pinterest_url']
    ];
    $form['twitter_url'] = [
      '#type' => 'textfield',
      '#title'=> $this->t('Twitter URL'),
      '#default_value' => $config['twitter_url']
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['follow'] = $form_state->getValue('follow');
    $this->configuration['facebook_url'] = $form_state->getValue('facebook_url');
    $this->configuration['instagram_url'] = $form_state->getValue('instagram_url');
    $this->configuration['pinterest_url'] = $form_state->getValue('pinterest_url');
    $this->configuration['twitter_url'] = $form_state->getValue('twitter_url');
  }

}
