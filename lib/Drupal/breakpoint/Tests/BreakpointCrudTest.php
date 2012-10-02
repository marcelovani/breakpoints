<?php
/**
 * @file
 * Definition of Drupal\breakpoint\Tests\BreakpointCrudTest.
 */

namespace Drupal\breakpoint\Tests;

use Drupal\breakpoint\Tests\BreakpointTestBase;
use Drupal\breakpoint\Breakpoint;

/**
 * Tests for breakpoint CRUD operations.
 */
class BreakpointCrudTest extends BreakpointTestBase {

  /**
   * Drupal\simpletest\WebTestBase\getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'Breakpoint CRUD operations',
      'description' => 'Test creation, loading, updating, deleting of breakpoints.',
      'group' => 'Breakpoint',
    );
  }

  /**
   * Test CRUD operations for breakpoints.
   */
  public function testBreakpointCrud() {
    // Add a breakpoint with minimum data only.
    $values = array(
      'label' => drupal_strtolower($this->randomName()),
      'mediaQuery' => '(min-width: 600px)',
    );

    $breakpoint = new Breakpoint($values);
    $breakpoint->save();

    $this->verifyBreakpoint($breakpoint);

    // Test breakpoint_breakpoint_load_all
    $all_breakpoints = breakpoint_breakpoint_load_all();
    $config_name = $breakpoint->getConfigName();
    $this->assertTrue(isset($all_breakpoints[$config_name]), t('breakpoint_breakpoint_load_all: New breakpoint is present when loading all breakpoints.'));
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
    $this->assertFalse(breakpoint_breakpoint_load($config_name), t('breakpoint_breakpoint_load: Loading a deleted breakpoint returns false.'), t('Breakpoints API'));
  }
}
