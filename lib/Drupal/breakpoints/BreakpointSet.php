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

  /**
   * Override and save a breakpoint set.
   */
  public function override() {
    if (!$this->source_type == Breakpoint::BREAKPOINTS_SOURCE_TYPE_THEME) {
      return FALSE;
    }
    foreach ($group->breakpoints as $key => $breakpoint_name) {
      $breakpoint = breakpoints_breakpoint_load($breakpoint_name);
      $old_breakpoint = $breakpoint->createDuplicate();
      if ($breakpoint->source_type == Breakpoint::BREAKPOINTS_SOURCE_TYPE_THEME && $breakpoint->source == $this->id) {
        $breakpoint->source_type = Breakpoint::BREAKPOINTS_SOURCE_TYPE_CUSTOM;

        // make sure it doesn't already exists.
        if (breakpoints_breakpoint_load($breakpoint->get_config_name()) === FALSE) {
          $breakpoint->save();
        }

        // Add to the group and delete old breakpoint.
        $this->breakpoints[$key] = $breakpoint->get_config_name();
        $old_breakpoint->delete();
      }
    }
    $this->overridden = TRUE;
    $this->save();
  }

  /**
   * Revert a breakpoint group after it has been overridden.
   */
  public function revert() {
    if (!$this->overridden || !$this->source_type == Breakpoint::BREAKPOINTS_SOURCE_TYPE_THEME) {
      return FALSE;
    }
    //@TODO: rewrite functions used in this function to work with the new ConfigEntities.
    return FALSE;
    // delete all breakpoints defined by this theme.
    $breakpoints = breakpoints_breakpoint_load_all_theme($this->id);
    foreach ($breakpoints as $breakpoint) {
      breakpoints_breakpoint_delete($breakpoint, $this->id);
    }

    // reload all breakpoints from theme.info.
    $reloaded_group = breakpoints_breakpoints_group_reload_from_theme($this->id);
    $this->breakpoints = $reloaded_group->breakpoints;
    $this->save();

  }
}
