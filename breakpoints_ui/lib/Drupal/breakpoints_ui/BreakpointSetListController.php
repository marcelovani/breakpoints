<?php

/**
 * @file
 * Definition of Drupal\breakpoints_ui\BreakpointSetListController.
 */

namespace Drupal\breakpoints_ui;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\breakpoints\Breakpoint;

/**
 * Provides a listing of Breakpoint sets.
 */
class BreakpointSetListController extends ConfigEntityListController {

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
    //@todo: override, export to theme, revert.
    if ($entity->source_type == Breakpoint::BREAKPOINTS_SOURCE_TYPE_THEME) {
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
