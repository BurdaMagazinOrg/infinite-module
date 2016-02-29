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
      '#groupId' => !empty($config['group_id']) ? $config['group_id'] : '',
      '#headline' => !empty($config['headline']) ? $config['headline'] : '',
      '#text' => !empty($config['text']) ? $config['text']['value'] : '',
      '#confirmation_headline' => !empty($config['confirmation_headline']) ? $config['confirmation_headline'] : '',
      '#confirmation_text' => !empty($config['confirmation_text']) ? $config['confirmation_text']['value'] : '',
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

    $form['headline'] = array(
      '#type' => 'textfield',
      '#title' => t('Headline'),
      '#default_value' => !empty($config['headline']) ? $config['headline'] : '',
      '#size' => 256,
      '#maxlength' => 512,
      '#required' => TRUE,
    );

    $form['text'] = array(
      '#type' => 'text_format',
      '#title' => t('Text'),
      '#default_value' => !empty($config['text']) ? $config['text']['value'] : '',
      '#rows' => 8,
      '#cols' => 128,
    );

    $form['confirmation_headline'] = array(
      '#type' => 'textfield',
      '#title' => t('Confirmation headline'),
      '#default_value' => !empty($config['confirmation_headline']) ? $config['confirmation_headline'] : '',
      '#size' => 256,
      '#maxlength' => 512,
      '#required' => TRUE,
    );

    $form['confirmation_text'] = array(
      '#type' => 'text_format',
      '#title' => t('Confirmation text'),
      '#default_value' => !empty($config['confirmation_text']) ? $config['confirmation_text']['value'] : '',
      '#rows' => 8,
      '#cols' => 128,
    );



    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('group_id', $form_state->getValue('group_id'));
    $this->setConfigurationValue('headline', $form_state->getValue('headline'));
    $this->setConfigurationValue('text', $form_state->getValue('text'));
    $this->setConfigurationValue('confirmation_headline', $form_state->getValue('confirmation_headline'));
    $this->setConfigurationValue('confirmation_text', $form_state->getValue('confirmation_text'));
  }

}
