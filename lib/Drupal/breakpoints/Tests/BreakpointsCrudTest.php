<?php
/**
 * @file
 * Definition of Drupal\breakpoints\Tests\BreakpointsCrudTest.
 */
namespace Drupal\breakpoints\Tests;

use Drupal\breakpoints\Tests\BreakpointsTestBase;
use Drupal\breakpoints\Breakpoint;

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
    $values = array(
      'label' => drupal_strtolower($this->randomName()),
      'media_query' => '(min-width: 600px)',
    );

    $breakpoint = new Breakpoint($values);
    $breakpoint->save();

    $this->verifyBreakpoint($breakpoint);

    // Test breakpoints_breakpoint_load_all
    $all_breakpoints = breakpoints_breakpoint_load_all();
    $config_name = $breakpoint->get_config_name();
    $this->assertTrue(isset($all_breakpoints[$config_name]), t('breakpoints_breakpoint_load_all: New breakpoint is present when loading all breakpoints.'));
    $this->verifyBreakpoint($breakpoint, $all_breakpoints[$config_name]);

    // Update the breakpoint.
    $breakpoint->weight = 1;
    $breakpoint->multipliers['2x'] = '2x';
    $breakpoint->save();
    $this->verifyBreakpoint($breakpoint);

    // Disable the breakpoint.
    $breakpoint->disable();
    $this->verifyBreakpoint($breakpoint);

    // Delete the breakpoint.
    $breakpoint->delete();
    $this->assertFalse(breakpoints_breakpoint_load($config_name), t('breakpoints_breakpoint_load: Loading a deleted breakpoint returns false.'), t('Breakpoints API'));
  }
}
