<?php

namespace Drupal\workbench_menu_access\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\system\MenuInterface;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Workbench menu access settings for a menu.
 */
class WorkbenchMenuAccessMenuForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a WorkbenchMenuAccessMenuForm object.
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
    return 'workbench_menu_access_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['workbench_menu_access.settings'];
  }

  /**
   * Builds the menu form.
   *
   * @param array $form
   *   A Drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param string $menu
   *   The menu being interacted with, if available.
   *
   * @return array
   *   A Drupal form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $menu = NULL) {
    $entity = $this->entityTypeManager->getStorage('menu')->load($menu);
    $active = $this->configFactory->get('workbench_menu_access.settings')->get('access_scheme');
    if ($entity instanceof MenuInterface && is_string($active)) {
      $scheme = $this->entityTypeManager->getStorage('access_scheme')->load($active);
      if ($scheme instanceof AccessSchemeInterface) {
        /** @var \Drupal\workbench_access\AccessControlHierarchyInterface $access_scheme */
        $access_scheme = $scheme->getAccessScheme();
        $tree = $access_scheme->getTree();
        $options = [];
        foreach ($tree as $set) {
          foreach ($set as $id => $section) {
            $options[$id] = str_repeat('-', $section['depth']) . ' ' . $section['label'];
          }
        }
        // @php-stan-ignore-next-line
        $form['workbench_menu_access'] = [
          '#type' => 'select',
          '#multiple' => TRUE,
          '#title' => t('Workbench access section'),
          '#description' => t('Select the editorial group(s) that can update this menu. If no sections are selected, access will not be restricted.'),
          '#default_value' => $entity->getThirdPartySetting('workbench_menu_access', 'access_scheme'),
          '#options' => $options,
          '#weight' => 0,
          '#size' => (count($options) <= 10) ? count($options) : 10,
          '#access' => \Drupal::currentUser()->hasPermission('administer workbench menu access'),
        ];
        $form['menu'] = ['#type' => 'value', '#value' => $menu];
      }
      return parent::buildForm($form, $form_state);
    }
    else {
      $form['error'] = [
        '#markup' => $this->t('You must <a href="@url">configure an access scheme</a> to continue.',
          ['@url' => Url::fromRoute('workbench_menu_access.admin')->toString()]),
      ];

      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $menu = $form_state->getValue('menu');
    $entity = $this->entityTypeManager->getStorage('menu')->load($menu);
    if ($entity instanceof MenuInterface) {
      $entity->setThirdPartySetting('workbench_menu_access', 'access_scheme', $form_state->getValue('workbench_menu_access'));
      $entity->save();
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * Route title callback.
   *
   * @param string $menu
   *   The menu id.
   *
   * @return array
   *   The menu label as a render array.
   */
  public function menuTitle($menu = NULL) {
    $entity = $this->entityTypeManager->getStorage('menu')->load($menu);
    if ($entity instanceof MenuInterface) {
      return [
        '#markup' => $this->t('@label menu access settings', ['@label' => $entity->label()]),
        '#allowed_tags' => Xss::getHtmlTagList(),
      ];
    }
    return [
      '#markup' => $this->t('Menu access settings'),
    ];
  }

}
