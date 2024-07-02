<?php

namespace Drupal\workbench_menu_access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\system\MenuAccessControlHandler;

/**
 * Overrides the access control handler for the menu entity type.
 *
 * @see \Drupal\system\Entity\Menu
 *
 * @SuppressWarnings(PHPMD.LongClassName)
 */
class WorkbenchMenuAccessControlHandler extends MenuAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected $viewLabelOperation = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // We do nothing on view label.
    if ($operation === 'view label') {
      return AccessResult::allowed();
    }
    // If allowed by Workbench Menu Access, pass through to the parent.
    if ($account->hasPermission('administer workbench menu access') || $account->hasPermission('bypass workbench access')) {
      return parent::checkAccess($entity, $operation, $account);
    }
    $allowed = $this->checkSections($entity, $account);
    if ($allowed) {
      return parent::checkAccess($entity, $operation, $account);
    }

    // If not allowed, say so.
    return AccessResult::forbidden()->addCacheableDependency($account);
  }

  /**
   * Public alias for checkAccess().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being checked.
   * @param string $operation
   *   The operation being performed.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account making the request.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result.
   */
  public function accessCheck(EntityInterface $entity, $operation, AccountInterface $account) {
    return $this->checkAccess($entity, $operation, $account);
  }

  /**
   * Checks that a user may update a menu and its links.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The parent menu item being checked.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account requesting access.
   *
   * @return bool
   *   TRUE if the user belongs to the menu section.
   */
  public function checkSections(EntityInterface $entity, AccountInterface $account) {
    static $check;
    // Internal cache for performance.
    $key = $entity->id() . ':' . $account->id();
    if (!isset($check[$key])) {
      // By default, no extra restrictions apply.
      $check[$key] = TRUE;
      $active = \Drupal::config('workbench_menu_access.settings')->get('access_scheme');
      // @phpstan-ignore-next-line
      $settings = $entity->getThirdPartySetting('workbench_menu_access', 'access_scheme');
      if (!is_null($active) && !is_null($settings)) {
        /** @var \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme */
        $scheme = \Drupal::entityTypeManager()->getStorage('access_scheme')->load($active);
        $access_manager = \Drupal::service('plugin.manager.workbench_access.scheme');
        $user_section_storage = \Drupal::service('workbench_access.user_section_storage');
        $user_sections = $user_section_storage->getUserSections($scheme, $account);
        // Check children / parents.
        $check[$key] = $access_manager::checkTree($scheme, $settings, $user_sections);
      }
    }
    return $check[$key];
  }

}
