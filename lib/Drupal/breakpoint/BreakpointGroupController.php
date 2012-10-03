<?php
/**
 * @file
 * Definition of Drupal\breakpoint\BreakpointGroupController.
 */

namespace Drupal\breakpoint;

use Drupal\Core\Config\Entity\ConfigStorageController;
use Drupal\breakpoint\BreakpointGroup;
use Drupal\breakpoint\Breakpoint;

/**
 * Defines the BreakpointGroup entity's controller.
 */
class BreakpointGroupController extends ConfigStorageController {

  /**
   * Override and save a breakpoint group.
   */
  public function override(BreakpointGroup $breakpoint_group) {
    if (!$breakpoint_group->sourceType == Breakpoint::SOURCE_TYPE_THEME) {
      return FALSE;
    }
    foreach ($breakpoint_group->breakpoints as $key => $breakpoint) {
      if ($breakpoint->sourceType == Breakpoint::SOURCE_TYPE_THEME && $breakpoint->source == $breakpoint_group->id()) {
        $new_breakpoint = $breakpoint->createDuplicate();
        $new_breakpoint->id = '';
        $new_breakpoint->sourceType = Breakpoint::SOURCE_TYPE_CUSTOM;
        $new_breakpoint->save();

        // Remove old one, add new one.
        unset($breakpoint_group->breakpoints[$key]);
        $breakpoint_group->breakpoints[$new_breakpoint->id] = $new_breakpoint;
      }
    }
    $breakpoint_group->overridden = TRUE;
    $breakpoint_group->save();
    return $breakpoint_group;
  }

  /**
   * Revert a breakpoint group after it has been overridden.
   */
  public function revert(BreakpointGroup $breakpoint_group) {
    if (!$breakpoint_group->overridden || !$breakpoint_group->sourceType == Breakpoint::SOURCE_TYPE_THEME) {
      return FALSE;
    }

    // Reload all breakpoints from theme.
    $reloaded_set = breakpoint_group_reload_from_theme($breakpoint_group->id());
    if ($reloaded_set) {
      $breakpoint_group->breakpoints = $reloaded_set->breakpoints;
      $breakpoint_group->overridden = FALSE;
      $breakpoint_group->save();
    }
    return $breakpoint_group;
  }
}
