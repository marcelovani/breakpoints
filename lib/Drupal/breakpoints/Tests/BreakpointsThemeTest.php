<?php
/**
 * @file
 * Definition of Drupal\breakpoints\Tests\BreakpointsThemeTest.
 */
namespace Drupal\breakpoints\Tests;

use Drupal\breakpoints\Tests\BreakpointGroupTestBase;
use stdClass;

/**
 * Test breakpoints provided by themes.
 */
class BreakpointsThemeTest extends BreakpointGroupTestBase {

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
    // Create user.
    $this->admin_user = $this->drupalCreateUser(array(
      'administer breakpoints',
    ));

    $this->drupalLogin($this->admin_user);
  }

  /**
   * Test the breakpoints provided by a theme.
   */
  public function testThemeBreakpoints() {
    // Verify the breakpoint group for breakpoints_test_theme was created.
    $breakpoint_group = new stdClass();
    $breakpoint_group->disabled = FALSE; /* Edit this to true to make a default breakpoint_group disabled initially */
    $breakpoint_group->api_version = 1;
    $breakpoint_group->machine_name = 'breakpoints_test_theme';
    $breakpoint_group->name = 'Breakpoints test theme';
    $breakpoint_group->breakpoints = array(
      'breakpoints.theme.breakpoints_test_theme.mobile',
      'breakpoints.theme.breakpoints_test_theme.narrow',
      'breakpoints.theme.breakpoints_test_theme.wide',
      'breakpoints.theme.breakpoints_test_theme.tv',
    );
    $breakpoint_group->type = 'theme';
    $breakpoint_group->overridden = 0;
    
    // Verify we can load this breakpoint defined by the theme.
    $this->verifyBreakpointGroup($breakpoint_group);

    // Override the breakpoints.
    $overridden_group = clone $breakpoint_group;
    breakpoints_breakpoints_group_override($overridden_group);

    // Verify the group is overridden.
    $overridden_group->breakpoints = array(
      'breakpoints.custom.breakpoints_test_theme.mobile',
      'breakpoints.custom.breakpoints_test_theme.narrow',
      'breakpoints.custom.breakpoints_test_theme.wide',
      'breakpoints.custom.breakpoints_test_theme.tv',
    );
    $overridden_group->overridden = 1;
    $this->verifyBreakpointGroup($overridden_group);

    // Reload the breakpoint group from the theme.
    breakpoints_breakpoints_group_revert($overridden_group);

    // Verify the breakpoint has its original values again when loaded.
    $this->verifyBreakpointGroup($breakpoint_group);

    // Verify $overridden_group has been reverted to the original state as well.
    $this->assertEqual($breakpoint_group, $overridden_group, t('breakpoints_breakpoints_group_revert: Breakpoint group variable has the right value after calling reverting the group.'), t('Breakpoints API'));

    // Disable the test theme and verify the breakpoint group is deleted.
    theme_disable(array('breakpoints_test_theme'));
    $this->assertFalse(breakpoints_breakpoint_group_load($breakpoint_group->machine_name), t('breakpoints_breakpoint_group_load: Loading a deleted breakpoint group returns false.'), t('Breakpoints API'));

  }
}
