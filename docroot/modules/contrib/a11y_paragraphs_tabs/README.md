# A11Y Paragraphs Tabs
## CONTENTS

 * Introduction
 * Features
 * Requirements
 * Installation
 * Configuration
 * Maintainers

## INTRODUCTION

A11Y Paragraphs Tabs gives the user the ability to easily add tabs via
paragraphs to their content that complies to Accessibility (A11Y) standards
and on mobile the tabs changes to an accordion.

This module creates 3 new paragraphs namely:
- A11Y Paragraphs Tabs Wrapper
- A11Y Paragraphs Tabs Panel
- A11Y Paragraphs Tabs Content

The wrapper (`A11Y Paragraphs Tabs Wrapper`) contains the tab panel
(`A11Y Paragraphs Tabs Panel`) of which you can add as many tabs as you need.
In turn, the tabs panel (`A11Y Paragraphs Tabs Panel`) contains a paragraph in
which you can add the paragraphs you would like to use inside the tab panel.

A11Y Paragraphs Tabs uses Matthias Ott's
[A11Y Accordion Tabs](https://github.com/matthiasott/a11y-accordion-tabs) js.

## FEATURES
- Tabs that comply with Accessibility (a11y) standards.
- Tabs become an accordion on mobile.

## REQUIREMENTS
- [Paragraphs](https://www.drupal.org/project/paragraphs) contrib module
- [A11Y Accordion Tabs](https://github.com/matthiasott/a11y-accordion-tabs)

## INSTALLATION

Install as you would normally install a contributed Drupal module. See also
[Core Docs](https://www.drupal.org/docs/extending-drupal/installing-modules).

Additionally, this module requires the A11Y Accordion Tabs library. You can
install that manually or with composer.

**Note**: If you use html such as `em` or `strong` in your tab titles, see
https://github.com/matthiasott/a11y-accordion-tabs/pull/19 and ideally apply it
as a patch on your site.

### Manual Library Installation:
- Download
 [A11Y Accordion Tabs](https://github.com/matthiasott/a11y-accordion-tabs)
- Extract download and move to your `/libraries` folder.
- Rename folder to `a11y-accordion-tabs` and make sure you have the correct
path to the js file: `/libraries/a11y-accordion-tabs/a11y-accordion-tabs.js`

### Composer Library Installation:
Either (if using composer-merge-plugin):
- Use add the included `a11y_paragraphs_tabs.libraries.json` to the
  `wikimedia/composer-merge-plugin` merge section in your `composer.json`
- Run `composer update matthiasott/a11y-accordion-tabs`
Or (if using https://asset-packagist.org/)
- composer require npm-asset/a11y-accordion-tabs:^0.5.0


## CONFIGURATION

- Go to your content type and add a new field of type Reference revisions,
Paragraphs.
- On the field edit screen, you can add a description, and choose which
paragraphs you want to allow for this field. Check only
`A11Y Paragraphs Tabs Wrapper`. This will add everything you need.
Click Save Settings.
- Adjust your form display, placing the field where you want it.
- Add the field into the Manage display tab.
- Done. You can now add tabs to your content.


## MAINTAINERS
Hennie Martens - https://www.drupal.org/u/hmartens
Barry Baeta - https://www.drupal.org/u/fallen8908
