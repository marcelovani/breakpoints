<?php

/**
 * @file
 * Definition of Drupal\breakpoint_ui\BreakpointGroupListController.
 */

namespace Drupal\breakpoint_ui;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\breakpoint\Breakpoint;

/**
 * Provides a listing of breakpoint groups.
 */
class BreakpointGroupListController extends ConfigEntityListController {

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
    $items[$path]['title'] = 'Breakpoint groups';
    $items[$path]['description'] = 'Manage list of breakpoint groups.';
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
    $operations['duplicate'] = array(
      'title' => t('Duplicate'),
      'href' => $uri['path'] . '/duplicate',
      'options' => $uri['options'],
      'weight' => 15,
    );
    if ($entity->sourceType != Breakpoint::SOURCE_TYPE_CUSTOM) {
      if (!$entity->overridden) {
        $operations['override'] = array(
          'title' => t('Override'),
          'href' => $uri['path'] . '/override',
          'options' => $uri['options'],
          'weight' => 15,
        );
      }
      else {
        $operations['revert'] = array(
          'title' => t('Revert'),
          'href' => $uri['path'] . '/revert',
          'options' => $uri['options'],
          'weight' => 15,
        );
      }
    }
    return $operations;
  }

}
