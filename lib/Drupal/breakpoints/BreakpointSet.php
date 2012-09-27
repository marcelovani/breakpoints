<?php

/**
 * @file
 * Definition of Drupal\breakpoint\BreakpointSet.
 */

namespace Drupal\breakpoints;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the BreakpointSet entity.
 */
class BreakpointSet extends ConfigEntityBase {

  /**
   * The BreakpointSet ID (machine name).
   *
   * @var string
   */
  public $id;

  /**
   * The BreakpointSet UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The BreakpointSet label.
   *
   * @var string
   */
  public $label;

  /**
   * The BreakpointSet breakpoints.
   *
   * @var array
   */
  public $breakpoints;

  /**
   * The BreakpointSet source type.
   *
   * @var string
   */
  public $source_type = Breakpoint::BREAKPOINTS_SOURCE_TYPE_CUSTOM;

  /**
   * The BreakpointSet overridden status.
   *
   * @var string
   */
  public $overridden = FALSE;

  /**
   * Overrides Drupal\config\ConfigEntityBase::__construct()
   */
  public function __construct(array $values = array(), $entity_type = 'breakpoints_breakpointset') {
    parent::__construct($values, $entity_type);
  }

  /**
   * Override and save a breakpoint set.
   */
  public function override() {
    return entity_get_controller($this->entityType)->override($this);
  }

  /**
   * Revert a breakpoint set after it has been overridden.
   */
  public function revert() {
    return entity_get_controller($this->entityType)->revert($this);
  }
}
