<?php
/**
 * @file
 * Definition of Drupal\breakpoints\Tests\BreakpointsCrudTest.
 */
namespace Drupal\breakpoints\Tests;

use Drupal\breakpoints\Tests\BreakpointsTestBase;
use stdClass;

/**
 * Tests for breakpoints CRUD operations.
 */
class BreakpointsCrudTest extends BreakpointsTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Breakpoints CRUD operations',
      'description' => 'Test creation, loading, updating, deleting of breakpoints.',
      'group' => 'Breakpoints',
    );
  }

  /**
   * Test CRUD operations for breakpoints.
   */
  function testBreakpointsCrud() {
    // Add a breakpoint with minimum data only.
    $breakpoint = new stdClass();
    $breakpoint->disabled = FALSE;
    $breakpoint->api_version = 1;
    $breakpoint->name = drupal_strtolower($this->randomName());
    $breakpoint->breakpoint = '(min-width: 600px)';
    $breakpoint->source = 'user';
    $breakpoint->source_type = 'custom';
    $breakpoint->status = 1;
    $breakpoint->weight = 0;
    $breakpoint->multipliers = array(
      '1.5x' => 0,
      '2x' => 0,
    );
    breakpoints_breakpoint_save($breakpoint);
    $this->verifyBreakpoint($breakpoint);

    // Update the breakpoint.
    $breakpoint->weight = 1;
    $breakpoint->multipliers['2x'] = 1;
    breakpoints_breakpoint_save($breakpoint);
    $this->verifyBreakpoint($breakpoint);

    // Disable the breakpoint.
    $breakpoint->status = 0;
    breakpoints_breakpoint_save($breakpoint);
    $this->verifyBreakpoint($breakpoint);
    $breakpoints = breakpoints_breakpoint_load_all_active();
    $this->assertFalse(isset($breakpoints[breakpoints_breakpoint_config_name($breakpoint)]), t('breakpoints_breakpoint_load_all_active: Disabled breakpoints aren\'t loaded.'), t('Breakpoints API'));

    // Delete the breakpoint.
    breakpoints_breakpoint_delete($breakpoint);
    $this->assertFalse(breakpoints_breakpoint_load_by_fullkey(breakpoints_breakpoint_config_name($breakpoint)), t('breakpoints_breakpoint_load_by_fullkey: Loading a deleted breakpoint returns false.'), t('Breakpoints API'));
  }
}
