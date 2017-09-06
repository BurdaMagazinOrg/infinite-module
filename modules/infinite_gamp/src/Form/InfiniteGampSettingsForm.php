<?php

namespace Drupal\infinite_gamp\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class InfiniteGampSettingsForm extends ConfigFormBase {

    public function buildForm(array $form, FormStateInterface $form_state)
    {

        $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
        $options = [];
        $config = $this->config('infinite_gamp.settings')->get('content_types');
        $defaultValues = explode('|', $config);

        foreach ($contentTypes as $contentType) {
            $options[$contentType->id()] = $contentType->label();
        }

        $form['content_types'] = [
            '#title' => $this->t('Hide AMP metatag on specific content types'),
            '#type' => 'checkboxes',
            '#options' => $options,
            '#default_value' => $defaultValues,
            '#multiple' => TRUE,
        ];

        return parent::buildForm($form, $form_state);
    }

    public function getFormId()
    {
        return 'infinite-gamp-settings';
    }

    protected function getEditableConfigNames()
    {
        return ['infinite_gamp.settings'];
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {

       $content_types = $form_state->getValue('content_types');

       if(!empty($content_types)) {
           $ampConfig = $this->config('infinite_gamp.settings');
           $ampConfig->set('content_types', implode('|', $content_types))->save();
       }

       Cache::invalidateTags(['amp_metadata','amp_available_metadata']);

       parent::submitForm($form, $form_state);

    }

}