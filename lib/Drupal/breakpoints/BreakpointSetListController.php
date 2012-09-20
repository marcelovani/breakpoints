<?php

/**
 * @file
 * Definition of Drupal\Drupal\breakpoints\BreakpointSetListController.
 */

namespace Drupal\breakpoints;

use Drupal\config\EntityListControllerBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Breakpoint sets.
 */
class BreakpointSetListController extends EntityListControllerBase {

  public function __construct($entity_type, $entity_info = FALSE) {
    parent::__construct($entity_type, $entity_info);
  }

  /**
   * Overrides Drupal\config\EntityListControllerBase::hookMenu();
   */
  public function hookMenu() {
    $path = $this->entityInfo['list path'];
    $items = parent::hookMenu();

    // Override the access callback.
    $items[$path]['title'] = 'Breakpoint sets';
    $items[$path]['description'] = 'Manage list of breakpoint sets.';
    $items[$path]['access callback'] = 'user_access';
    $items[$path]['access arguments'] = array('administer breakpoints');

    return $items;
  }

  /**
   * Implements Drupal\config\EntityListControllerInterface::defineOperationLinks();
   */
  public function defineOperationLinks(EntityInterface $breakpointset) {
    $path = $this->entityInfo['list path'] . '/breakpoints/group/' . $breakpointset->id();
    $definition['edit'] = array(
      'title' => t('Edit'),
      'href' => "$path/edit",
    );
    $definition['delete'] = array(
      'title' => t('Delete'),
      'href' => "$path/delete",
    );
    return $definition;
  }

}
