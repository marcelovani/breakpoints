<?php
/**
 * @file
 * Definition of Drupal\breakpoint\Tests\BreakpointsThemeTest.
 */

namespace Drupal\breakpoint\Tests;

use Drupal\breakpoint\Tests\BreakpointGroupTestBase;
use Drupal\breakpoint\BreakpointGroup;
use Drupal\breakpoint\Breakpoint;

/**
 * Test breakpoints provided by themes.
 */
class BreakpointThemeTest extends BreakpointGroupTestBase {

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
    $breakpoint_group_obj = new BreakpointGroup();
    $breakpoint_group_obj->label = 'Breakpoint test theme';
    $breakpoint_group_obj->id = 'breakpoint_test_theme';
    $breakpoint_group_obj->sourceType = Breakpoint::SOURCE_TYPE_THEME;
    $breakpoint_group_obj->breakpoints = array(
      'theme.breakpoint_test_theme.mobile' => array(),
      'theme.breakpoint_test_theme.narrow' => array(),
      'theme.breakpoint_test_theme.wide' => array(),
      'theme.breakpoint_test_theme.tv' => array(),
    );
    $breakpoint_group_obj->overridden = 0;

    // Verify we can load this breakpoint defined by the theme.
    $this->verifyBreakpointGroup($breakpoint_group_obj);

    // Override the breakpoints.
    $overridden_set = clone $breakpoint_group_obj;
    $breakpoint_group = entity_load('breakpoint_group', 'breakpoint_test_theme');
    $breakpoint_group = $breakpoint_group->override();

    // Verify the group is overridden.
    $overridden_set->breakpoints = array(
      'custom.breakpoint_test_theme.mobile' => array(),
      'custom.breakpoint_test_theme.narrow' => array(),
      'custom.breakpoint_test_theme.wide' => array(),
      'custom.breakpoint_test_theme.tv' => array(),
    );
    $overridden_set->overridden = 1;
    $this->verifyBreakpointGroup($overridden_set);

    // Revert the breakpoint group.
    $breakpoint_group = entity_load('breakpoint_group', 'breakpoint_test_theme');
    $breakpoint_group = $breakpoint_group->revert();

    // Verify the breakpoint group has its original values again when loaded.
    $this->verifyBreakpointGroup($breakpoint_group_obj);

    // Disable the test theme and verify the breakpoint group is deleted.
    theme_disable(array('breakpoint_test_theme'));
    $this->assertFalse(entity_load('breakpoint_group', 'breakpoint_test_theme'), t('breakpoint_group_load: Loading a deleted breakpoint group returns false.'), t('Breakpoints API'));
  }

  /**
   * Test the breakpoints defined by the custom group.
   */
  public function testThemeBreakpointGroup() {
    // Verify the breakpoint group 'test' was created by breakpoint_test_theme.
    $breakpoint_group_obj = new BreakpointGroup();
    $breakpoint_group_obj->label = 'Test';
    $breakpoint_group_obj->id = 'test';
    $breakpoint_group_obj->sourceType = Breakpoint::SOURCE_TYPE_THEME;
    $breakpoint_group_obj->source = 'breakpoint_test_theme';
    $breakpoint_group_obj->breakpoints = array(
      'theme.breakpoint_test_theme.mobile' => array('1.5x', '2.x'),
      'theme.breakpoint_test_theme.narrow' => array(),
      'theme.breakpoint_test_theme.wide' => array(),
    );
    $breakpoint_group_obj->overridden = 0;

    // Verify we can load this breakpoint defined by the theme.
    $this->verifyBreakpointGroup($breakpoint_group_obj);

    // Disable the test theme and verify the breakpoint group is deleted.
    theme_disable(array('breakpoint_test_theme'));
    $this->assertFalse(entity_load('breakpoint_group', 'test'), t('breakpoint_group_load: Loading a deleted breakpoint group returns false.'), t('Breakpoints API'));
  }

  /**
   * Test the breakpoints defined by the custom group in the module.
   */
  public function testThemeBreakpointGroupModule() {
    // Call the import manually, since the testbot needs to enable the module
    // first, otherwise the theme isn't detected.
    _breakpoint_import_breakpoint_groups('breakpoint_theme_test', Breakpoint::SOURCE_TYPE_MODULE);

    // Verify the breakpoint group 'module_test' was created by
    // breakpoint_theme_test module.
    $breakpoint_group_obj = new BreakpointGroup();
    $breakpoint_group_obj->label = 'Test Module';
    $breakpoint_group_obj->id = 'module_test';
    $breakpoint_group_obj->sourceType = Breakpoint::SOURCE_TYPE_MODULE;
    $breakpoint_group_obj->source = 'breakpoint_theme_test';
    $breakpoint_group_obj->breakpoints = array(
      'theme.breakpoint_test_theme.mobile' => array(),
      'theme.breakpoint_test_theme.narrow' => array('3.x', '4.x'),
      'theme.breakpoint_test_theme.wide' => array(),
    );
    $breakpoint_group_obj->overridden = 0;

    // Verify we can load this breakpoint defined by the theme.
    $this->verifyBreakpointGroup($breakpoint_group_obj);

    // Check that the multipliers are added to the settings.
    $settings = breakpoint_settings();
    $expected_settings = array(
      0 => '1x',
      1 => '1.5x',
      2 => '2x',
      3 => '3x',
      4 => '4x',
    );
    $this->assertEqual($settings->multipliers, $expected_settings, 'Multipliers are added to settings.');

    // Disable the test theme and verify the breakpoint group still exists.
    theme_disable(array('breakpoint_test_theme'));
    $this->assertTrue(entity_load('breakpoint_group', 'module_test'), 'Breakpoint group still exists if theme is disabled.');

    // Disable the test module and verify the breakpoint group still exists.
    module_disable(array('breakpoint_theme_test'));
    $this->assertTrue(entity_load('breakpoint_group', 'module_test'), 'Breakpoint group still exists if module is disabled.');

    // Uninstall the test module and verify the breakpoint group is deleted.
    module_uninstall(array('breakpoint_theme_test'));
    $this->assertFalse(entity_load('breakpoint_group', 'module_test'), 'Breakpoint group is removed if module is uninstalled.');
  }

}
