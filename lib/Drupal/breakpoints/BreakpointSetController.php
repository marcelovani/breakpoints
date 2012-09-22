<?php
/**
 * @file
 * Definition of Drupal\breakpoint\BreakpointSetController.
 */
namespace Drupal\breakpoints;

use Drupal\config\ConfigStorageController;
use Drupal\breakpoints\BreakpointSet;
use Drupal\breakpoints\Breakpoint;

/**
 * Defines the BreakpointSet entity's controller.
 */
class BreakpointSetController extends ConfigStorageController{

  /**
   * Override and save a breakpoint set.
   */
  public function override(BreakpointSet $breakpointset) {
    if (!$breakpointset->source_type == Breakpoint::BREAKPOINTS_SOURCE_TYPE_THEME) {
      return FALSE;
    }
    foreach ($breakpointset->breakpoints as $key => $breakpoint_name) {
      $breakpoint = breakpoints_breakpoint_load($breakpoint_name);
      $old_breakpoint = $breakpoint->createDuplicate();
      if ($breakpoint->source_type == Breakpoint::BREAKPOINTS_SOURCE_TYPE_THEME && $breakpoint->source == $breakpointset->id()) {
        $breakpoint->source_type = Breakpoint::BREAKPOINTS_SOURCE_TYPE_CUSTOM;

        // make sure it doesn't already exists.
        if (breakpoints_breakpoint_load($breakpoint->get_config_name()) === FALSE) {
          $breakpoint->save();
        }

        // Add to the group and delete old breakpoint.
        $breakpointset->breakpoints[$key] = $breakpoint->get_config_name();
        $old_breakpoint->delete();
      }
    }
    $breakpointset->overridden = TRUE;
    $breakpointset->save();
    return $breakpointset;
  }

  /**
   * Revert a breakpoint group after it has been overridden.
   */
  public function revert(BreakpointSet $breakpointset) {
    if (!$breakpointset->overridden || !$breakpointset->source_type == Breakpoint::BREAKPOINTS_SOURCE_TYPE_THEME) {
      return FALSE;
    }
    // delete all breakpoints defined by this theme.
    $names = drupal_container()->get('config.storage')->listAll('breakpoints.breakpoint.custom.' . $breakpointset->id() . '.');
    $breakpoints = entity_load_multiple('breakpoints_breakpoint', array($names));
    foreach ($breakpoints as $breakpoint) {
      $breakpoint->delete();
    }

    // reload all breakpoints from theme.info.
    $reloaded_group = breakpoints_breakpoints_group_reload_from_theme($breakpointset->id());
    $breakpointset->breakpoints = $reloaded_group->breakpoints;
    $breakpointset->overridden = FALSE;
    $breakpointset->save();
  }
}
