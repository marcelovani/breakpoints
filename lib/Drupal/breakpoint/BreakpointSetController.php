<?php
/**
 * @file
 * Definition of Drupal\breakpoint\BreakpointSetController.
 */

namespace Drupal\breakpoint;

use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\breakpoint\BreakpointSet;
use Drupal\breakpoint\Breakpoint;

/**
 * Defines the BreakpointSet entity's controller.
 */
class BreakpointSetController extends ConfigStorageController {

  /**
   * Override and save a breakpoint set.
   */
  public function override(BreakpointSet $breakpointset) {
    if (!$breakpointset->sourceType == Breakpoint::SOURCE_TYPE_THEME) {
      return FALSE;
    }
    foreach ($breakpointset->breakpoints as $key => $breakpoint) {
      if ($breakpoint->sourceType == Breakpoint::SOURCE_TYPE_THEME && $breakpoint->source == $breakpointset->id()) {
        $new_breakpoint = $breakpoint->createDuplicate();
        $new_breakpoint->id = '';
        $new_breakpoint->sourceType = Breakpoint::SOURCE_TYPE_CUSTOM;
        $new_breakpoint->save();

        // Remove old one, add new one.
        unset($breakpointset->breakpoints[$key]);
        $breakpointset->breakpoints[$new_breakpoint->id] = $new_breakpoint;
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
    if (!$breakpointset->overridden || !$breakpointset->sourceType == Breakpoint::SOURCE_TYPE_THEME) {
      return FALSE;
    }

    // Reload all breakpoints from theme.
    $reloaded_set = breakpoint_breakpointset_reload_from_theme($breakpointset->id());
    if ($reloaded_set) {
      $breakpointset->breakpoints = $reloaded_set->breakpoints;
      $breakpointset->overridden = FALSE;
      $breakpointset->save();
    }
    return $breakpointset;
  }
}
