<?php
/**
 * @file
 * Definition of Drupal\breakpoints\Tests\BreakpointGroupCrudTest.
 */
namespace Drupal\breakpoints\Tests;

use Drupal\breakpoints\Tests\BreakpointGroupTestBase;
use stdClass;

/**
 * Tests for breakpoint group CRUD operations.
 */
class BreakpointGroupCrudTest extends BreakpointGroupTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Breakpoint Group CRUD operations',
      'description' => 'Test creation, loading, updating, deleting of breakpoint groups.',
      'group' => 'Breakpoints',
    );
  }

  /**
   * Test CRUD operations for breakpoint groups.
   */
  function testBreakpointGroupCrud() {
    // Add breakpoints.
    $breakpoints = array();
    for ($i = 0; $i <= 3; $i++) {
      $breakpoint = new stdClass();
      $breakpoint->disabled = FALSE;
      $breakpoint->api_version = 1;
      $breakpoint->name = drupal_strtolower($this->randomName());
      $width = ($i + 1) * 200;
      $breakpoint->breakpoint = "(min-width: {$width}px)";
      $breakpoint->source = 'user';
      $breakpoint->source_type = 'custom';
      $breakpoint->status = 1;
      $breakpoint->weight = $i;
      $breakpoint->multipliers = array(
        '1.5x' => 0,
        '2x' => 0,
      );
      breakpoints_breakpoint_save($breakpoint);
      $breakpoints[] = $breakpoint;
    }
    // Add a breakpoint group with minimum data only.
    $group = new stdClass();
    $group->name = $this->randomName();
    $group->type = BREAKPOINTS_SOURCE_TYPE_CUSTOM;
    $group->overridden = FALSE;
    $group->machine_name = drupal_strtolower($group->name);
    $group->breakpoints = array();
    breakpoints_breakpoint_group_save($group);
    $this->verifyBreakpointGroup($group);

    // Update the breakpoint group.
    $group->breakpoints = array_keys($breakpoints);
    breakpoints_breakpoint_group_save($group);
    $this->verifyBreakpointGroup($group);

    // Delete the breakpoint group.
    breakpoints_breakpoint_group_delete($group);
    $this->assertFalse(breakpoints_breakpoint_group_load($group->machine_name), t('breakpoints_breakpoint_group_load: Loading a deleted breakpoint group returns false.'), t('Breakpoints API'));
  }
}
