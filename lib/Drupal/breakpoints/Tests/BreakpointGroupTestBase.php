<?php
/**
 * @file
 * Definition of Drupal\breakpoints\Tests\BreakpointGroupTestBase.
 */

namespace Drupal\breakpoints\Tests;

use Drupal\simpletest\WebTestBase;
/**
 * Base class for Breakpoint Group tests.
 */
abstract class BreakpointGroupTestBase extends WebTestBase {
  
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('breakpoints');

  function  setUp() {
    parent::setUp();
  }

  /**
   * Verify that a breakpoint is properly stored.
   */
  function verifyBreakpointGroup($group) {
    $t_args = array('%group' => $group->name);
    $properties = array('name', 'machine_name', 'breakpoints');
    $assert_group = t('Breakpoints API');

    // Verify breakpoints_breakpoint_group_load().
    $load_group = breakpoints_breakpoint_group_load($group->machine_name);
    foreach ($properties as $property) {
      $this->assertEqual($load_group->{$property}, $group->{$property}, t('breakpoints_breakpoint_group_load: Proper ' . $property . ' for breakpoint group %group.', $t_args), $assert_group);
    }
  }
}