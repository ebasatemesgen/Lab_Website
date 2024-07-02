<?php

namespace Drupal\lightning_layout\Form;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The settings form for controlling Lightning Layout's behavior.
 *
 * @internal
 *   This is an internal part of Lightning Layout and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The block plugin manager service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  protected $moduleHandler;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block plugin manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, BlockManagerInterface $block_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->blockManager = $block_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.block'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['lightning_layout.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lightning_layout_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$this->moduleHandler->moduleExists('entity_block')) {
      $form['no_settings']['#markup'] = $this->t('There are no settings available.');
      return $form;
    }

    $form['entity_blocks'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Entity types to expose as blocks'),
      '#default_value' => $this->config('lightning_layout.settings')->get('entity_blocks'),
    ];
    foreach ($this->entityTypeManager->getDefinitions() as $id => $entity_type) {
      if ($entity_type->hasViewBuilderClass()) {
        $form['entity_blocks']['#options'][$id] = $entity_type->getLabel();
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $value = $form_state->getValue('entity_blocks');
    // Filter out unselected entity types.
    $value = array_filter($value);
    // Re-key the array.
    $value = array_values($value);

    $this->config('lightning_layout.settings')
      ->set('entity_blocks', $value)
      ->save();

    $this->blockManager->clearCachedDefinitions();

    parent::submitForm($form, $form_state);
  }

}
