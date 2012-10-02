<?php
/**
 * @file
 * Definition of Drupal\breakpoint\Tests\BreakpointsThemeTest.
 */

namespace Drupal\breakpoint\Tests;

use Drupal\breakpoint\Tests\BreakpointSetTestBase;
use Drupal\breakpoint\BreakpointSet;
use Drupal\breakpoint\Breakpoint;

/**
 * Test breakpoints provided by themes.
 */
class BreakpointThemeTest extends BreakpointSetTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('breakpoint_theme_test');

  /**
   * Drupal\simpletest\WebTestBase\getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'Breakpoint Theme functionality',
      'description' => 'Thoroughly test the breakpoints provided by a theme.',
      'group' => 'Breakpoint',
    );
  }

  /**
   * Drupal\simpletest\WebTestBase\setUp().
   */
  public function setUp() {
    parent::setUp();
    theme_enable(array('breakpoint_test_theme'));
  }

  /**
   * Test the breakpoints provided by a theme.
   */
  public function testThemeBreakpoints() {
    // Verify the breakpoint group for breakpoint_test_theme was created.
    $breakpointset_obj = new BreakpointSet();
    $breakpointset_obj->label = 'Breakpoint test theme';
    $breakpointset_obj->id = 'breakpoint_test_theme';
    $breakpointset_obj->sourceType = Breakpoint::SOURCE_TYPE_THEME;
    $breakpointset_obj->breakpoints = array(
      'theme.breakpoint_test_theme.mobile' => array(),
      'theme.breakpoint_test_theme.narrow' => array(),
      'theme.breakpoint_test_theme.wide' => array(),
      'theme.breakpoint_test_theme.tv' => array(),
    );
    $breakpointset_obj->overridden = 0;

    // Verify we can load this breakpoint defined by the theme.
    $this->verifyBreakpointSet($breakpointset_obj);

    // Override the breakpoints.
    $overridden_set = clone $breakpointset_obj;
    $breakpointset = breakpoint_breakpointset_load('breakpoint_test_theme');
    $breakpointset = $breakpointset->override();

    // Verify the group is overridden.
    $overridden_set->breakpoints = array(
      'custom.breakpoint_test_theme.mobile' => array(),
      'custom.breakpoint_test_theme.narrow' => array(),
      'custom.breakpoint_test_theme.wide' => array(),
      'custom.breakpoint_test_theme.tv' => array(),
    );
    $overridden_set->overridden = 1;
    $this->verifyBreakpointSet($overridden_set);

    // Revert the breakpoint set.
    $breakpointset = breakpoint_breakpointset_load('breakpoint_test_theme');
    $breakpointset = $breakpointset->revert();

    // Verify the breakpointset has its original values again when loaded.
    $this->verifyBreakpointSet($breakpointset_obj);

    // Disable the test theme and verify the breakpoint group is deleted.
    theme_disable(array('breakpoint_test_theme'));
    $this->assertFalse(breakpoint_breakpointset_load('breakpoint_test_theme'), t('breakpoint_breakpoint_group_load: Loading a deleted breakpoint group returns false.'), t('Breakpoints API'));
  }
}
