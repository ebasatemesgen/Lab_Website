<?php

namespace Drupal\Tests\workbench_menu_access\Traits;

use Drupal\Core\Session\AccountInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\system\Entity\Menu;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;

/**
 * Contains helper classes for tests to set up various configuration.
 */
trait WorkbenchMenuAccessTestTrait {

  use WorkbenchAccessTestTrait;

  /**
   * Creates a set of nested terms for testing.
   *
   * @param \Drupal\taxonomy\Entity\Vocabulary $vocabulary
   *   A vocabulary entity.
   */
  public function createTerms(Vocabulary $vocabulary): void {
    for ($i = 1; $i <= 10; $i++) {
      if ($i <= 5) {
        $term = Term::create([
          'vid' => $vocabulary->id(),
          'name' => 'Term ' . $i,
        ]);
      }
      else {
        $term = Term::create([
          'vid' => $vocabulary->id(),
          'name' => 'Term ' . $i,
          'parent' => $i - 5,
        ]);
      }
      $term->save();
    }
  }

  /**
   * Adds a user account to a Workbench Access section.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   An access control scheme.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to add.
   * @param array $section_ids
   *   A simple array of section id keys to add.
   */
  public function addUserToSection(AccessSchemeInterface $scheme, AccountInterface $account, array $section_ids): void {
    $user_storage = \Drupal::service('workbench_access.user_section_storage');
    $user_storage->addUser($scheme, $account, $section_ids);
  }

  /**
   * Adds a new menu for testing.
   *
   * @param string $menu_name
   *   The machine name of the menu to create.
   */
  public function addMenu($menu_name = 'main'): void {
    Menu::create([
      'id' => $menu_name,
      'label' => $menu_name,
      'description' => $menu_name,
      'locked' => FALSE,
    ])->save();
  }

  /**
   * Adds a new menu link for testing.
   *
   * @param string $title
   *   The link title.
   * @param string $parent
   *   The menu link id of the parent.
   * @param string $path
   *   The path (URL) for the link.
   * @param string $menu_name
   *   The machine name of the menu to create.
   * @param bool $expanded
   *   If the menu link and its children should be expanded.
   * @param int $weight
   *   The sort order weight of the link.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent
   *   A menu link object.
   */
  public function addMenuLink($title, $parent = '', $path = '/', $menu_name = 'main', $expanded = FALSE, $weight = 0) {
    $menu_link = MenuLinkContent::create([
      'title' => $title,
      'description' => $title,
      'menu_name' => $menu_name,
      'link' => ['uri' => $path],
      'external' => FALSE,
      'weight' => $weight,
      'expanded' => $expanded,
      'enabled' => TRUE,
      'parent' => $parent,
    ]);
    $menu_link->save();

    return $menu_link;
  }

}
