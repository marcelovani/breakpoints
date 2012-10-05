<?php

/**
 * @file
 * Definition of Drupal\breakpoint_ui\BreakpointListController.
 */

namespace Drupal\breakpoint_ui;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of breakpoints.
 */
class BreakpointListController extends ConfigEntityListController {

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
   * Overrides Drupal\config\ConfigEntityListController::getOperations();
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    $uri = $entity->uri();
    $action = $entity->status ? 'disable' : 'enable';
    $operations[$action] = array(
      'title' => t(drupal_ucfirst($action)),
      'href' => $uri['path'] . '/' . $action,
      'options' => $uri['options'],
      'weight' => 15,
    );
    return $operations;
  }

}
