<?php

namespace Drupal\h5p_analytics\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ModuleConfigurationForm.
 */
class ModuleConfigurationForm extends ConfigFormBase {
  /**
   * Config settings
   * @var string
   */
  const SETTINGS = 'h5p_analytics.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'h5p_analytics_module_configuration_form';
  }

  /**
  * {@inheritdoc}
  */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);
    // TODO Make batch size connfigurable

    $form['lrs'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('LRS'),
      '#description' => $this->t('LRS Settings'),
      '#weight' => '0',
    ];
    $form['lrs']['xapi_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('xAPI Endpoint'),
      '#description' => $this->t('xAPI Endpoint URL (no trailing slash)'),
      '#weight' => '0',
      '#default_value' => $config->get('xapi_endpoint'),
    ];
    $form['lrs']['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#description' => $this->t('LRS Client Key'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '1',
      '#default_value' => $config->get('key'),
    ];
    $form['lrs']['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret'),
      '#description' => $this->t('LRS Client Secret'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '2',
      '#default_value' => $config->get('secret'),
    ];
    $form['lrs']['batch_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch size'),
      '#description' => $this->t('Size of the statements batch to be sent to LRS.'),
      '#min' => 1,
      '#max' => 1000,
      '#step' => 1,
      '#size' => 64,
      '#weight' => '3',
      '#default_value' => $config->get('batch_size'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configFactory->getEditable(static::SETTINGS)
    ->set('xapi_endpoint', $values['xapi_endpoint'])
    ->set('key', $values['key'])
    ->set('secret', $values['secret'])
    ->set('batch_size', $values['batch_size'])
    ->save();
    parent::submitForm($form, $form_state);
  }

}
