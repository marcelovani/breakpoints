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
      $new_breakpoint = get_object_vars($breakpoint);
      unset($new_breakpoint['id']);
      unset($new_breakpoint['uuid']);

      if ($breakpoint->source_type == Breakpoint::BREAKPOINTS_SOURCE_TYPE_THEME && $breakpoint->source == $breakpointset->id()) {
        $new_breakpoint['source_type'] = Breakpoint::BREAKPOINTS_SOURCE_TYPE_CUSTOM;
        $new_breakpoint = new Breakpoint($new_breakpoint);
        $new_breakpoint->save();

        // Add to the set.
        $breakpointset->breakpoints[$key] = $new_breakpoint->get_config_name();
      }
    }
    $breakpointset->overridden = TRUE;
    $breakpointset->save();
    return $breakpointset;
  }

  /**
   * Revert a breakpoint set after it has been overridden.
   */
  public function revert(BreakpointSet $breakpointset) {
    if (!$breakpointset->overridden || !$breakpointset->source_type == Breakpoint::BREAKPOINTS_SOURCE_TYPE_THEME) {
      return FALSE;
    }

    // reload all breakpoints from theme.info.
    $reloaded_set = breakpoints_breakpoints_set_reload_from_theme($breakpointset->id());
    if ($reloaded_set) {
      $breakpointset->breakpoints = $reloaded_set->breakpoints;
      $breakpointset->overridden = FALSE;
      $breakpointset->save();
    }
  }
}
