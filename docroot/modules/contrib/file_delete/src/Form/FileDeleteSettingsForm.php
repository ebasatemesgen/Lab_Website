<?php

namespace Drupal\file_delete\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Settings Form for the Debug Pause Module.
 */
class FileDeleteSettingsForm extends ConfigFormBase {

  /**
   * Holds user's account session.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $account;

  /**
   * FileDeleteSettingsForm Constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Injects AccountSession service.
   */
  public function __construct(AccountInterface $account) {
    $this->account = $account;
  }

  /**
   * Dependency Injection Container for FileDeleteSettingsForm.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Dependency Injection Container.
   *
   * @return \Drupal\Core\Form\ConfigFormBase|FileDeleteSettingsForm|static
   *   Services from the container for Dependency Injection.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'file_delete_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    // Default settings.
    $config = $this->config('file_delete.settings');

    $form['instant_delete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Defaults Instant File Deletion:'),
      '#default_value' => $config->get('instant_delete'),
      '#description' => $this->t('Sets Instant File Deletion as the default to Users who have permission.'),
    ];

    $form['force_delete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Defaults Forceful File Deletion:'),
      '#default_value' => $config->get('force_delete'),
      '#description' => $this->t('Sets Usage override as the default to users who have permission.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('file_delete.settings');
    $config->set('instant_delete', $form_state->getValue('instant_delete'));
    $config->set('force_delete', $form_state->getValue('force_delete'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getValue('instant_delete') && !$this->account->hasPermission('delete files immediately')) {
      $form_state->setErrorByName('instant_delete', $this->t('You do not have permission to instantly delete files'));
    }
    if ($form_state->getValue('force_delete') && !$this->account->hasPermission('delete files override usage')) {
      $form_state->setErrorByName('force_delete', $this->t('You do not have permission to forcefully delete files.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'file_delete.settings',
    ];
  }

}
