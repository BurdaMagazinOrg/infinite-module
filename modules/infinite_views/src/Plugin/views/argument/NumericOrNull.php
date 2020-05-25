<?php

namespace Drupal\infinite_views\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\NumericArgument;
use Drupal\Core\Form\FormStateInterface;

/**
 * Argument handler to accept an ingredient id.
 *
 * @ViewsArgument("numeric_or_null")
 */
class NumericOrNull extends NumericArgument {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['or_null'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['not'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Or null'),
      '#description' => $this->t('If selected, the numbers entered for the filter will be optional, rather than limiting the view.'),
      '#default_value' => !empty($this->options['or_null']),
      '#group' => 'options][more',
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    if (!empty($this->options['break_phrase'])) {
      $break = static::breakString($this->argument, FALSE);
      $this->value = $break->value;
      $this->operator = $break->operator;
    }
    else {
      $this->value = [$this->argument];
    }

    $placeholder = $this->placeholder();

    $null_check = (empty($this->options['not']) && empty($this->options['or_null'])) ? '' : " OR $this->tableAlias.$this->realField IS NULL";

    if (count($this->value) > 1) {
      $operator = empty($this->options['not']) ? 'IN' : 'NOT IN';
      $placeholder .= '[]';
      $this->query->addWhereExpression(0, "$this->tableAlias.$this->realField $operator($placeholder)" . $null_check, [$placeholder => $this->value]);
    }
    else {
      $operator = empty($this->options['not']) ? '=' : '!=';
      $this->query->addWhereExpression(0, "$this->tableAlias.$this->realField $operator $placeholder" . $null_check, [$placeholder => $this->argument]);
    }
  }

}
