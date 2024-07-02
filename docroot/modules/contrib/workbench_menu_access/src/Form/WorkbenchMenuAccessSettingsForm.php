<?php

namespace Drupal\workbench_menu_access\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Workbench menu access settings for this site.
 */
class WorkbenchMenuAccessSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a WorkbenchMenuAccessSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Instantiates a new instance of this class.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this instance should use.
   *
   * @return self
   *   A new instance of this class.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workbench_menu_access_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['workbench_menu_access.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('workbench_menu_access.settings');
    $schemes = $this->entityTypeManager->getStorage('access_scheme')->loadMultiple();
    if (count($schemes) === 0) {
      $form['error'] = [
        '#markup' => $this->t('You must create an access scheme to continue.'),
      ];
      return $form;
    }
    $options = [t('Do not restrict menu access')];
    foreach ($schemes as $scheme) {
      $options[$scheme->id()] = $scheme->label();
    }
    $form['access_scheme'] = [
      '#type' => 'select',
      '#title' => $this->t('Access scheme'),
      '#options' => $options,
      '#default_value' => $config->get('access_scheme'),
      '#description' => $this->t('Apply this access scheme to menu actions'),
    ];
    $active = $config->get('access_scheme');
    if (is_string($active)) {
      $scheme = $this->entityTypeManager->getStorage('access_scheme')->load($active);
      if ($scheme instanceof AccessSchemeInterface) {
        $form['info'] = [
          '#markup' => $this->t('The %type scheme %label is used for menu access.',
            ['%type' => $scheme->get('scheme'), '%label' => $scheme->label()]),
          '#prefix' => '<p>',
          '#suffix' => '</p>',
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('workbench_menu_access.settings');
    $config->set('access_scheme', $form_state->getValue('access_scheme'))->save();
    parent::submitForm($form, $form_state);
  }

}
