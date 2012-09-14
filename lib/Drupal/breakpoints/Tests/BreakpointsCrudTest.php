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
    $breakpoint->source_type = BREAKPOINTS_SOURCE_TYPE_CUSTOM;
    $breakpoint->status = 1;
    $breakpoint->weight = 0;
    $breakpoint->multipliers = array(
      '1.5x' => 0,
      '2x' => 0,
    );
    breakpoints_breakpoint_save($breakpoint);

    $config_name = breakpoints_breakpoint_config_name($breakpoint);
    $this->verifyBreakpoint($breakpoint);

    // Test breakpoints_breakpoint_load.
    $compare_breakpoint = breakpoints_breakpoint_load($breakpoint->name, $breakpoint->source, $breakpoint->source_type);
    $this->verifyBreakpoint($breakpoint, $compare_breakpoint);

    // Test breakpoints_breakpoint_load_by_fullkey.
    $compare_breakpoint = breakpoints_breakpoint_load_by_fullkey($config_name);
    $this->verifyBreakpoint($breakpoint, $compare_breakpoint);

    // Test breakpoints_breakpoint_load_all
    $all_breakpoints = breakpoints_breakpoint_load_all();
    $this->assertTrue(isset($all_breakpoints[$config_name]), t('breakpoints_breakpoint_load_all: New breakpoint is present when loading all breakpoints.'));
    $this->verifyBreakpoint($breakpoint, $all_breakpoints[$config_name]);

    $all_custom_breakpoints = _breakpoints_breakpoint_load_all_by_type(BREAKPOINTS_SOURCE_TYPE_CUSTOM);
    $this->assertTrue(isset($all_custom_breakpoints[$config_name]), t('_breakpoints_breakpoint_load_all_by_type: New @type breakpoint is present when loading all breakpoints of type @type.', array('@type' => BREAKPOINTS_SOURCE_TYPE_CUSTOM)));
    $this->verifyBreakpoint($breakpoint, $all_custom_breakpoints[$config_name]);

    // Update the breakpoint.
    $breakpoint->weight = 1;
    $breakpoint->multipliers['2x'] = 1;
    breakpoints_breakpoint_save($breakpoint);
    $this->verifyBreakpoint($breakpoint);

    // Disable the breakpoint.
    breakpoints_breakpoint_toggle_status($breakpoint);
    $this->verifyBreakpoint($breakpoint);
    $breakpoints = breakpoints_breakpoint_load_all_active();
    $config_name = breakpoints_breakpoint_config_name($breakpoint);
    $this->assertFalse(isset($breakpoints[$config_name]), t('breakpoints_breakpoint_load_all_active: Disabled breakpoints aren\'t loaded.'), t('Breakpoints API'));

    // Delete the breakpoint.
    breakpoints_breakpoint_delete($breakpoint);
    $this->assertFalse(breakpoints_breakpoint_load_by_fullkey($config_name), t('breakpoints_breakpoint_load_by_fullkey: Loading a deleted breakpoint returns false.'), t('Breakpoints API'));
  }
}
