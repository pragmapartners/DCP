<?php

namespace Drupal\dcp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class DcpSettingsForm extends ConfigFormBase
{

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return ['dcp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'dcp_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('dcp.settings');

    $form['custom_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom CSS Classes'),
      '#default_value' => $config->get('custom_classes'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $this->config('dcp.settings')
      ->set('custom_classes', $form_state->getValue('custom_classes'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
