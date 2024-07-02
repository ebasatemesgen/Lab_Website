<?php

declare(strict_types = 1);

namespace Drupal\auditfiles\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Audit Files.
 *
 * @internal
 *   There is no extensibility promise for this class. Use form alter hooks to
 *   make customisations.
 */
final class AuditFilesConfigForm extends ConfigFormBase {

  /**
   * Constructs a new AuditFilesConfig.
   */
  final public function __construct(
    ConfigFactoryInterface $configFactory,
    protected StreamWrapperManagerInterface $streamWrapperManager,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->setConfigFactory($configFactory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('stream_wrapper_manager'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'auditfiles_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['auditfiles.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('auditfiles.settings');
    $form['auditfiles_file_system_paths'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('File system paths'),
      '#collapsible' => TRUE,
    ];
    // Show the file system path select list.
    $file_system_paths = $this->streamWrapperManager->getWrappers(StreamWrapperInterface::LOCAL);
    $options = [];
    foreach ($file_system_paths as $file_system_path_id => $file_system_path) {
      $options[$file_system_path_id] = $file_system_path_id . ' : file_' . $file_system_path_id . '_path';
    }
    $form['auditfiles_file_system_paths']['auditfiles_file_system_path'] = [
      '#type' => 'select',
      '#title' => 'File system path',
      '#default_value' => $config->get('auditfiles_file_system_path'),
      '#options' => $options,
      '#description' => $this->t('Select the file system path to use when searching for and comparing files.'),
    ];

    $form['auditfiles_exclusions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Exclusions'),
      '#collapsible' => TRUE,
    ];

    $form['auditfiles_exclusions']['auditfiles_exclude_files'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exclude these files'),
      '#default_value' => $config->get('auditfiles_exclude_files'),
      '#description' => $this->t('Enter a list of files to exclude, each separated by the semi-colon character (;).'),
    ];

    $form['auditfiles_exclusions']['auditfiles_exclude_extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exclude these extensions'),
      '#default_value' => $config->get('auditfiles_exclude_extensions'),
      '#description' => $this->t('Enter a list of extensions to exclude, each separated by the semi-colon character (;). Do not include the leading dot.'),
    ];

    $form['auditfiles_exclusions']['auditfiles_exclude_paths'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exclude these paths'),
      '#default_value' => $config->get('auditfiles_exclude_paths'),
      '#description' => $this->t('Enter a list of paths to exclude, each separated by the semi-colon character (;). Do not include the leading slash.'),
    ];

    $form['auditfiles_domains'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Domains'),
      '#collapsible' => TRUE,
    ];
    $form['auditfiles_domains']['auditfiles_include_domains'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Include references to these domains'),
      '#default_value' => $config->get('auditfiles_include_domains'),
      '#size' => 80,
      '#maxlength' => 1024,
      '#description' => $this->t('Enter a list of domains (e.g., www.example.com) pointing to your website, each separated by the semi-colon character (;). <br />When scanning content for file references (such as &lt;img&gt;tags), any absolute references using these domains will be included and rewritten to use relative references. Absolute references to domains not in this list will be considered to be external references and will not be audited or rewritten.'),
    ];

    $form['auditfiles_report_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Report options'),
      '#collapsible' => TRUE,
    ];

    $date_formats = [];
    $dateFormats = $this->entityTypeManager
      ->getStorage('date_format')
      ->loadMultiple();
    foreach ($dateFormats as $dateFormat) {
      $date_formats[$dateFormat->id()] = $dateFormat->label();
    }

    $form['auditfiles_report_options']['auditfiles_report_options_date_format'] = [
      '#type' => 'select',
      '#title' => 'Date format',
      '#default_value' => $config->get('auditfiles_report_options_date_format'),
      '#options' => $date_formats,
      '#description' => $this->t('Select the date format to use when displaying file dates in the reports.'),
    ];

    $form['auditfiles_report_options']['auditfiles_report_options_items_per_page'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of items per page'),
      '#default_value' => $config->get('auditfiles_report_options_items_per_page'),
      '#size' => 10,
      '#description' => $this->t('Enter an integer representing the number of items to display on each page of a report.<br /> If there are more than this number on a page, then a pager will be used to display the additional items.<br /> Set this to 0 to show all items on a single page.'),
    ];

    $form['auditfiles_report_options']['auditfiles_report_options_maximum_records'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum records'),
      '#default_value' => $config->get('auditfiles_report_options_maximum_records'),
      '#size' => 10,
      '#description' => $this->t('Enter an integer representing the maximum number of records to return for each report.<br /> If any of the reports are timing out, set this to some positive integer to limit the number of records queried in the database. For reports where the limit is reached, a button to batch process the loading of the page will be available that will allow all records to be retrieved without timing out.<br /> Set this to 0 for no limit.'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit popup after login configurations.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('auditfiles.settings')
      ->set('auditfiles_file_system_path', trim($form_state->getValue('auditfiles_file_system_path')))
      ->set('auditfiles_exclude_files', trim($form_state->getValue('auditfiles_exclude_files')))
      ->set('auditfiles_exclude_extensions', trim($form_state->getValue('auditfiles_exclude_extensions')))
      ->set('auditfiles_exclude_paths', trim($form_state->getValue('auditfiles_exclude_paths')))
      ->set('auditfiles_include_domains', trim($form_state->getValue('auditfiles_include_domains')))
      ->set('auditfiles_report_options_items_per_page', trim($form_state->getValue('auditfiles_report_options_items_per_page')))
      ->set('auditfiles_report_options_maximum_records', trim($form_state->getValue('auditfiles_report_options_maximum_records')))
      ->set('auditfiles_report_options_date_format', trim($form_state->getValue('auditfiles_report_options_date_format')))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
