<?php

/**
 * @file
 * Definition of Drupal\breakpoint\BreakpointSet.
 */

namespace Drupal\breakpoints;

use Drupal\config\ConfigEntityBase;

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

}
