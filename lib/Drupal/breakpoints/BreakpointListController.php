<?php

/**
 * @file
 * Definition of Drupal\breakpoint\BreakpointListController.
 */

namespace Drupal\breakpoints;

use Drupal\config\EntityListControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListController;

/**
 * Provides a listing of breakpoints.
 */
class BreakpointListController extends EntityListController {

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
    $items[$path]['title'] = 'Breakpoints';
    $items[$path]['description'] = 'Manage list of breakpoints.';
    $items[$path]['access callback'] = 'user_access';
    $items[$path]['access arguments'] = array('administer breakpoints');

    return $items;
  }

  /**
   * Implements Drupal\config\EntityListControllerInterface::defineOperationLinks();
   */
  public function defineOperationLinks(EntityInterface $breakpoint) {
    $path = $this->entityInfo['list path'] . '/breakpoints/' . $breakpoint->id();
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
