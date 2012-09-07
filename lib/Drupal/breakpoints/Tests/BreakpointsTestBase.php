<?php
/**
 * @file
 * Definition of Drupal\breakpoints\Tests\BreakpointsTestBase.
 */

namespace Drupal\breakpoints\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Base class for Breakpoint tests.
 */
abstract class BreakpointsTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('breakpoints', 'breakpoints_ui');

  function  setUp() {
    parent::setUp();
  }

  /**
   * Verify that a breakpoint is properly stored.
   */
  function verifyBreakpoint($breakpoint) {
    $t_args = array('%breakpoint' => $breakpoint->name);
    $properties = array('name', 'breakpoint', 'source', 'source_type', 'status', 'weight', 'multipliers');
    $assert_group = t('Breakpoints API');

    // Verify breakpoints_breakpoint_load_by_fullkey().
    $load_breakpoint = breakpoints_breakpoint_load_by_fullkey(breakpoints_breakpoint_config_name($breakpoint));
    foreach ($properties as $property) {
      $this->assertEqual($load_breakpoint->{$property}, $breakpoint->{$property}, t('breakpoints_breakpoint_load_by_fullkey: Proper ' . $property . ' for breakpoint %breakpoint.', $t_args), $assert_group);
    }
  }
}