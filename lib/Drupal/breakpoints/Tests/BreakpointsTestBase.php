<?php
/**
 * @file
 * Definition of Drupal\breakpoints\Tests\BreakpointsTestBase.
 */

namespace Drupal\breakpoints\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\breakpoints\Breakpoint;

/**
 * Base class for Breakpoint tests.
 */
abstract class BreakpointsTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('breakpoints');

  /**
   * Drupal\simpletest\WebTestBase\setUp().
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Verify that a breakpoint is properly stored.
   */
  public function verifyBreakpoint(Breakpoint $breakpoint, Breakpoint $compare_breakpoint = NULL) {
    $properties = array(
      'label',
      'mediaQuery',
      'source',
      'sourceType',
      'status',
      'weight',
      'multipliers',
    );
    $assert_group = t('Breakpoints API');

    // Verify breakpoints_breakpoint_load().
    $compare_breakpoint = is_null($compare_breakpoint) ? breakpoints_breakpoint_load($breakpoint->getConfigName()) : $compare_breakpoint;
    foreach ($properties as $property) {
      $t_args = array(
        '%breakpoint' => $breakpoint->label(),
        '%property' => $property,
      );
      $this->assertEqual($compare_breakpoint->{$property}, $breakpoint->{$property}, t('breakpoints_breakpoint_load: Proper %property for breakpoint %breakpoint.', $t_args), $assert_group);
    }
  }
}
