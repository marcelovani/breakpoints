<?php
/**
 * @file
 * Definition of Drupal\breakpoints\Tests\BreakpointsThemeTest.
 */
namespace Drupal\breakpoints\Tests;

use Drupal\breakpoints\Tests\BreakpointSetTestBase;
use Drupal\breakpoints\BreakpointSet;
use Drupal\breakpoints\Breakpoint;

/**
 * Test breakpoints provided by themes.
 */
class BreakpointsThemeTest extends BreakpointSetTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('breakpoints_theme_test');

  public static function getInfo() {
    return array(
      'name' => 'Breakpoint Theme functionality',
      'description' => 'Thoroughly test the breakpoints provided by a theme.',
      'group' => 'Breakpoints',
    );
  }

  public function  setUp() {
    parent::setUp();
    theme_enable(array('breakpoints_test_theme'));
  }

  /**
   * Test the breakpoints provided by a theme.
   */
  public function testThemeBreakpoints() {
    // Verify the breakpoint group for breakpoints_test_theme was created.
    $breakpoint_set_obj = new BreakpointSet();
    $breakpoint_set_obj->label = 'Breakpoints test theme';
    $breakpoint_set_obj->id = 'breakpoints_test_theme';
    $breakpoint_set_obj->source_type = Breakpoint::BREAKPOINTS_SOURCE_TYPE_THEME;
    $breakpoint_set_obj->breakpoints = array(
      'theme.breakpoints_test_theme.mobile' => array(),
      'theme.breakpoints_test_theme.narrow' => array(),
      'theme.breakpoints_test_theme.wide' => array(),
      'theme.breakpoints_test_theme.tv' => array(),
    );
    $breakpoint_set_obj->overridden = 0;

    // Verify we can load this breakpoint defined by the theme.
    $this->verifyBreakpointSet($breakpoint_set_obj);

    // Override the breakpoints.
    $overridden_set = clone $breakpoint_set_obj;
    $breakpoint_set = breakpoints_breakpointset_load('breakpoints_test_theme');
    $breakpoint_set = $breakpoint_set->override();

    // Verify the group is overridden.
    $overridden_set->breakpoints = array(
      'custom.breakpoints_test_theme.mobile' => array(),
      'custom.breakpoints_test_theme.narrow' => array(),
      'custom.breakpoints_test_theme.wide' => array(),
      'custom.breakpoints_test_theme.tv' => array(),
    );
    $overridden_set->overridden = 1;
    $this->verifyBreakpointSet($overridden_set);

    // Revert the breakpoint set.
    $breakpoint_set = breakpoints_breakpointset_load('breakpoints_test_theme');
    $breakpoint_set = $breakpoint_set->revert();

    // Verify the breakpointset has its original values again when loaded.
    $this->verifyBreakpointSet($breakpoint_set_obj);

    // Disable the test theme and verify the breakpoint group is deleted.
    theme_disable(array('breakpoints_test_theme'));
    $this->assertFalse(breakpoints_breakpointset_load('breakpoints_test_theme'), t('breakpoints_breakpoint_group_load: Loading a deleted breakpoint group returns false.'), t('Breakpoints API'));
  }
}
