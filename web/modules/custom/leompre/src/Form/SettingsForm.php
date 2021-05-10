<?php

namespace Drupal\leompre\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure leompre settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'leompre_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['leompre.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Cuantos usuarios se muestran por pagina'),
      '#default_value' => $this->config('leompre.settings')->get('limit') ? $this->config('leompre.settings')->get('limit') : 10,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('leompre.settings')
      ->set('limit', $form_state->getValue('limit'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
