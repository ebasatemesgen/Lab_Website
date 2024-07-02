<?php

namespace Drupal\contact_emails\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a class for contact_emails's settings form.
 */
class ContactEmailSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contact_emails_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'contact_emails.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('contact_emails.settings');

    $form['allow_charset_utf_8'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Charset UTF-8'),
      '#description' => $this->t('Set the content type header to <b>text/html; charset=UTF-8</b> for HTML emails.'),
      '#default_value' => $config->get('allow_charset_utf_8'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('contact_emails.settings')
      ->set('allow_charset_utf_8', (bool) $form_state->getValue('allow_charset_utf_8'))
      ->save();
  }

}
