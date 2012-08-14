<?php
/**
 * @file
 * Definition of Drupal\breakpoints\Tests\BreakpointMultipliersTest.
 */
namespace Drupal\breakpoints\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test breakpoint multipliers.
 */
class BreakpointMultipliersTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('breakpoints', 'breakpoints_theme_test');

  public static function getInfo() {
    return array(
      'name' => 'Breakpoint Multiplier functionality',
      'description' => 'Thoroughly test the breakpoint multiplier functionality (CRUD).',
      'group' => 'Breakpoints',
    );
  }

  public function  setUp() {
    parent::setUp();
    // Enable our test theme so we have breakpoints to test on.
    theme_enable(array('breakpoints_test_theme'));
    // Create user.
    $this->admin_user = $this->drupalCreateUser(array(
      'administer breakpoints',
    ));

    $this->drupalLogin($this->admin_user);
  }

  /**
   * Test breakpoints multipliers functionality.
   */
  public function testBreakpointMultipliers() {
    // Verify the default multipliers are visible.
    $this->drupalGet('admin/config/media/breakpoints/multipliers');
    $settings = breakpoints_settings();
    foreach ($settings->multipliers as $multiplier) {
      $this->assertRaw($multiplier, t('Default multiplier %multiplier found', array('%multiplier' => $multiplier)));
      if ($multiplier != '1x') {
        $this->assertFieldByName('multipliers[' . $multiplier . ']', $multiplier);
      }
    }

    // Verify the '1x' multiplier can't be deleted.
    $this->drupalGet('admin/config/media/breakpoints/multipliers/1x/delete');
    $this->assertText(t('Multiplier 1x can not be deleted!'), t('Multiplier 1x can not be deleted'));
    $this->assertNoFieldById('edit-submit');

    // Verify we need to enter a machine readable name.
    $this->drupalGet('admin/config/media/breakpoints/multipliers');
    $edit = array(
      'multipliers[new]' => '**',
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertText(t('The machine-readable name must contain only lowercase letters, numbers, and underscores.'));

    // Add a multiplier.
    $new_multiplier = drupal_strtolower($this->randomName());
    $this->drupalGet('admin/config/media/breakpoints/multipliers');
    $edit = array(
      'multipliers[new]' => $new_multiplier,
    );
    $this->drupalPost(NULL, $edit, t('Save'));

    // Verify the multiplier was added.
    $settings = breakpoints_settings();
    $this->assertTrue(in_array($new_multiplier, $settings->multipliers), t('Multiplier %multiplier was added.', array('%multiplier' => $new_multiplier)));

    // Verify the new multiplier is visible on the multiplier overview page.
    $this->assertFieldByName('multipliers[' . $new_multiplier . ']', $new_multiplier);

    // Update a multiplier.
    $updated_multiplier = drupal_strtolower($this->randomName());
    $edit = array(
      'multipliers[' . $new_multiplier . ']' => $updated_multiplier,
    );
    $this->drupalPost(NULL, $edit, t('Save'));

    // Verify the multiplier was updated.
    $settings = breakpoints_settings();
    $this->assertFalse(in_array($new_multiplier, $settings->multipliers), t('Multiplier %multiplier was updated.', array('%multiplier' => $updated_multiplier)));
    $this->assertTrue(in_array($updated_multiplier, $settings->multipliers), t('Multiplier %multiplier was updated.', array('%multiplier' => $updated_multiplier)));

    // Verify the updated multiplier is visible on the multiplier overview page.
    $this->assertNoFieldByName('multipliers[' . $new_multiplier . ']');
    $this->assertFieldByName('multipliers[' . $updated_multiplier . ']', $updated_multiplier);
    $new_multiplier = $updated_multiplier;

    // Verify the default multipliers are visible on the global breakpoints page.
    $this->drupalGet('admin/config/media/breakpoints');
    foreach (breakpoints_breakpoint_load_all() as $breakpoint) {
      foreach ($settings->multipliers as $multiplier) {
        if ($multiplier != '1x') {
          $this->assertFieldByName('breakpoints[' . $breakpoint->machine_name . '][multipliers][' . $multiplier . ']');
        }
        else {
          // Multiplier 1x can not be disabled for any breakpoint.
          $this->assertNoFieldByName('breakpoints[' . $breakpoint->machine_name . '][multipliers][' . $multiplier . ']');
        }
      }
    }

    // Enable a multiplier for a breakpoint and verify if it's enabled on all pages.
    $edit = array(
      'breakpoints[breakpoints.theme.breakpoints_test_theme.narrow][multipliers][1.5x]' => 1,
      'breakpoints[breakpoints.theme.breakpoints_test_theme.narrow][multipliers][' . $new_multiplier . ']' => 1,
    );
    $this->drupalPost(NULL, $edit, t('Save'));

    // Verify the checkbox for the enabled multipliers is checked on the global breakpoints page.
    $this->assertFieldChecked('edit-breakpoints-breakpointsthemebreakpoints-test-themenarrow-multipliers-15x');
    $this->assertFieldChecked('edit-breakpoints-breakpointsthemebreakpoints-test-themenarrow-multipliers-' . drupal_clean_css_identifier($new_multiplier));

    // Verify the checkbox for the enabled multipliers is checked on the breakpoints page of a group.
    $this->drupalGet('admin/config/media/breakpoints/groups/breakpoints_test_theme');
    $this->assertFieldChecked('edit-breakpoints-breakpointsthemebreakpoints-test-themenarrow-multipliers-15x');
    $this->assertFieldChecked('edit-breakpoints-breakpointsthemebreakpoints-test-themenarrow-multipliers-' . drupal_clean_css_identifier($new_multiplier));

    // Delete a multiplier.
    $this->drupalGet('admin/config/media/breakpoints/multipliers/' . $new_multiplier . '/delete');
    $this->drupalPost(NULL, array(), t('Confirm'));
    $this->assertText('Multiplier ' . $new_multiplier . ' was deleted');

    // Verify the deleted multiplier is no longer visible on the multiplier overview page.
    $this->drupalGet('admin/config/media/breakpoints/multipliers');
    $this->assertNoFieldByName('multipliers[' . $new_multiplier . ']');

    // Verify the deleted multiplier is deleted.
    $settings = breakpoints_settings();
    $this->assertFalse(in_array($new_multiplier, $settings->multipliers), t('Multiplier %multiplier was deleted.', array('%multiplier' => $new_multiplier)));

    // Verify the deleted multiplier is no longer visible on the breakpoints page.
    $this->drupalGet('admin/config/media/breakpoints');
    foreach (breakpoints_breakpoint_load_all() as $breakpoint) {
      $this->assertNoFieldByName('breakpoints[' . $breakpoint->machine_name . '][multipliers][' . $new_multiplier . ']');
    }
  }
}
