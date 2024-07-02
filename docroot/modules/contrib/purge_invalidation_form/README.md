# Purge Invalidation Form

## INTRODUCTION

This module directly invalidate an item without going through the
[Purge](https://www.drupal.org/project/purge) queue.

The functionality is very similar than the "p:invalidate" drush 
[command](https://git.drupalcode.org/project/purge#drush-commands)

## USE CASE

You published new content and it needs to be inmediatly available for 
your audience and you can't way until the Purge queue is processed.

Using this module you can enter the url or entity tag and invalidate 
the item right away.

## REQUIREMENTS

1. [Purge](https://www.drupal.org/project/purge) module.

## INSTALLATION

The module can be installed via the
[standard Drupal installation process](https://drupal.org/node/1897420).

## USAGE

Go to the [Purge Invalidation Form](/admin/config/development/performance/purge-invalidation-form) 
and enter the item(s) that needs to be invalidated, one per line.

Examples:
* tag node:1 (Clears URLs tagged with "node:1" from external caching platforms).
* url http://www.drupal.org/ (Clears the url from external caching platforms).
