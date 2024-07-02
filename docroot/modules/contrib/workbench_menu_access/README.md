Workbench Menu Access
=======

An extension module that applies Workbench Access logic to menus.

This module adds access controls to menu editing and the menu links within
a specific menu, both in stand-alone and node-editing contexts.

Features
=======

- Allows administrators to assign a Workbench Access control scheme to users for
access to menus and menu links.
- Allows each menu to be assigned to one or more access control sections.
- Ignores access rules if a menu is not assigned to a Workbench Access section.
- Will not allow editors with 'administer menus' permission to access, edit, or
delete menus or menu links within unassigned menus.
- Will restrict access to menu links on node forms to only those accessible to
the editor. Presents notices to editors when a menu item cannot be changed.

Permissions
=======

- 'Administer Workbench Menu Access configuration'
Gives the role the ability to assign menus to Workbench Access control sections.

Give this permission to trusted administrators.

Configuration
=======

- Enable the module.
  - Enabling the module should clear cache properly. Run `drush cr` if needed.
- Give the desired roles the 'Administer Workbench Menu Access configuration'
permission.
- Go to `/admin/config/workflow/workbench_access/menu_settings`
  - If Workbench Access is already configured, assign the access scheme you
  wish to use for menus.
  - If Workbench Access is _not_ already configured, you will be prompted to
  configure at least one access control scheme. Refer to the Workbench Access
  documentation.
- After setting the access scheme, go to the Administer Menus pages to configure
access controls for each menu as desired. On the menu overview page, users with
the `Administer Workbench Menu Access configuration` permission will see a link
to the `Access settings` page as part of the operations menu.

Expected Behavior
=======

*Administrative forms*

To configure the access controls for a menu and its menu items, visit the new
`Workbench menu access` tab at `admin/structure/menu/manage/main/access`.

On these pages a new form element will appear. *Workbench access section* is a
multiple select list that assigns the menu to the selected access control
sections.

These sections map to the editorial sections granted to roles and users by
Workbench Access. If no sections have been configured for Workbench Access, you
will be prompted to create one.

*Menu usage*

When editing a menu or a menu item in that menu, access is checked against the
editor's Workbench Access assignments. If they do not have permission to one
of the assigned sections, access will be denied.

When creating or editing nodes, only the allowed menus will be shown to that
editor.

If no menus are available, the Menu options form will be hidden.

If the node is assigned to a menu that the editor cannot access, a message will
be presented 'You may not edit the menu this content is assigned to.'

Users with the `Bypass Workbench Access` permission are not subject to these
restrictions.

*Content type forms*

For menu forms to appear on node editing pages, at least one menu must be
assigned as *Available menus* under the *Menu settings* for that content type.

The list of available menus will be filtered by Workbench Menu Access when
users are presented with the menu options for a node.

Currently, no changes are made to the content type settings forms. Any user with
access to these forms may assign eligible menus for each node type. This feature
is considered out of scope for the module.

Tests
=======

The module comes with four main tests:

- WorkbenchMenuAccessSettingsTest
Ensures that basic module settings work as designed and are access restricted.

- WorkbenchMenuAccessMenuTest
Ensures that only users assigned to proper access controls may edit and delete
menus. Also ensures that the menu settings form is only shown to administrators.

- WorkbenchMenuAccessMenuLinkTest
Ensures that only users assigned to proper access controls may edit and delete
menu links.

- WorkbenchMenuAccessNodeFormTest
Ensures that only allowed menus are visible in node editing forms and that
proper messages are printed when required.

Before filing a bug report, please check the test coverage and report on why
it is not sufficient.
