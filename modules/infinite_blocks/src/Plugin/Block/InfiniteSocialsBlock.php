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
      '#facebook_page_url' => !empty($config['facebook_page_url']) ? $config['facebook_page_url']: '',
      '#instagram_page_url' => !empty($config['instagram_page_url']) ? $config['instagram_page_url'] : '',
      '#pinterest_page_url' => !empty($config['pinterest_page_url']) ? $config['pinterest_page_url'] : '',
      '#twitter_page_url' => !empty($config['twitter_page_url']) ? $config['twitter_page_url'] : '',
      '#youtube_page_url' => !empty($config['youtube_page_url']) ? $config['youtube_page_url'] : '',
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

    $form['facebook_page_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facbook page url'),
      '#default_value' => $config['facebook_page_url'],
    ];

    $form['instagram_page_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Instagram page url'),
      '#default_value' => $config['instagram_page_url'],
    ];

    $form['pinterest_page_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pinterest page url'),
      '#default_value' => $config['pinterest_page_url'],
    ];

    $form['twitter_page_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter page url'),
      '#default_value' => $config['twitter_page_url'],
    ];

    $form['youtube_page_url'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Youtube channel url'),
        '#default_value' => $config['youtube_page_url'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['follow'] = $form_state->getValue('follow');
    $this->configuration['facebook_page_url'] = $form_state->getValue('facebook_page_url');
    $this->configuration['instagram_page_url'] = $form_state->getValue('instagram_page_url');
    $this->configuration['pinterest_page_url'] = $form_state->getValue('pinterest_page_url');
    $this->configuration['twitter_page_url'] = $form_state->getValue('twitter_page_url');
    $this->configuration['youtube_page_url'] = $form_state->getValue('youtube_page_url');
  }

}
