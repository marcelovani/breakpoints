<?php
/**
 * @file
 * Definition of Drupal\breakpoints\Tests\BreakpointMultipliersTest.
 */
namespace Drupal\breakpoints_ui\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test breakpoint multipliers.
 */
class BreakpointsUIMultipliersTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('breakpoints', 'breakpoints_ui', 'breakpoints_theme_test');

  public static function getInfo() {
    return array(
      'name' => 'Breakpoint Multiplier functionality',
      'description' => 'Thoroughly test the breakpoint multiplier functionality (CRUD).',
      'group' => 'Breakpoints UI',
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
      $this->assertRaw($multiplier, t('Default multiplier %multiplier found.', array('%multiplier' => $multiplier)));
      if ($multiplier != '1x') {
        $this->assertFieldByName('multipliers[' . $multiplier . ']', $multiplier);
      }
    }

    // Verify the '1x' multiplier can't be deleted.
    $this->drupalGet('admin/config/media/breakpoints/multipliers/1x/delete');
    $this->assertText(t('Multiplier 1x can not be deleted!'), t('Multiplier 1x can not be deleted.'), t('Breakpoints API'));
    $this->assertNoFieldById('edit-submit');

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

  }
}
