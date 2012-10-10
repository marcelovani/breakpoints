<?php
/**
 * @file
 * Definition of Drupal\breakpoint_ui\Tests\BreakpointMultipliersTest.
 */
namespace Drupal\breakpoint_ui\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test breakpoint multipliers.
 *
 * @todo Multipliers provided by modules and themes.
 */
class BreakpointUIMultipliersTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('breakpoint', 'breakpoint_ui', 'breakpoint_theme_test');

  public static function getInfo() {
    return array(
      'name' => 'Breakpoint Multiplier functionality',
      'description' => 'Thoroughly test the breakpoint multiplier functionality (CRUD).',
      'group' => 'Breakpoint UI',
    );
  }

  public function  setUp() {
    parent::setUp();
    // Enable our test theme so we have breakpoints to test on.
    theme_enable(array('breakpoint_test_theme'));
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
    $group = t('Breakpoint UI');
    $path = 'admin/config/media/breakpoint/multipliers';
    // Verify the default multipliers are visible.
    $this->drupalGet($path);
    $multipliers = drupal_map_assoc(config('breakpoint')->get('multipliers'));
    foreach ($multipliers as $multiplier) {
      $this->assertRaw($multiplier, t('Default multiplier %multiplier found.', array('%multiplier' => $multiplier)));
      if ($multiplier != '1x') {
        $this->assertFieldByName('multipliers[' . $multiplier . ']', $multiplier);
      }
    }

    // Verify the '1x' multiplier can't be deleted.
    $this->drupalGet($path . '/1x/delete');
    $this->assertText(t('Multiplier 1x can not be deleted!'), t('Multiplier 1x can not be deleted.'), $group);
    $this->assertNoFieldById('edit-submit');

    // Try to add an invalid multiplier
    $this->drupalGet($path);
    $invalid_multiplier = $this->randomString();
    $edit = array(
      'multipliers[new]' => $invalid_multiplier,
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertText(t('Multiplier has to be a number followed by an \'x\'.'), t('An error message is shown when an invalid multiplier is entered.'), $group);

    // Add a new multiplier.
    $this->drupalGet($path);
    // Generate random float (1 decimal) between 2.1 and 4 followed by 'x'.
    $new_multiplier = (mt_rand(21, 40) / 10) . 'x';
    $edit = array(
      'multipliers[new]' => $new_multiplier,
    );
    $this->drupalPost(NULL, $edit, t('Save'));

    // Verify the multiplier was added.
    $multipliers = drupal_map_assoc(config('breakpoint')->get('multipliers'));
    $this->assertTrue(in_array($new_multiplier, $multipliers), t('Multiplier %multiplier was added.', array('%multiplier' => $new_multiplier)), $group);

    // Verify the new multiplier is visible on the multiplier overview page.
    $this->assertFieldByName('multipliers[' . $new_multiplier . ']', $new_multiplier);

    // Update a multiplier.
    $updated_multiplier = (mt_rand(21, 40) / 10) . 'x';
    $edit = array(
      'multipliers[' . $new_multiplier . ']' => $updated_multiplier,
    );
    $this->drupalPost(NULL, $edit, t('Save'));

    // Verify the multiplier was updated.
    $multipliers = drupal_map_assoc(config('breakpoint')->get('multipliers'));
    $this->assertFalse(in_array($new_multiplier, $multipliers), t('Multiplier %multiplier was updated.', array('%multiplier' => $updated_multiplier)), $group);
    $this->assertTrue(in_array($updated_multiplier, $multipliers), t('Multiplier %multiplier was updated.', array('%multiplier' => $updated_multiplier)), $group);

    // Verify the updated multiplier is visible on the multiplier overview page.
    $this->assertNoFieldByName('multipliers[' . $new_multiplier . ']');
    $this->assertFieldByName('multipliers[' . $updated_multiplier . ']', $updated_multiplier);
    $new_multiplier = $updated_multiplier;

    // Delete a multiplier.
    $this->drupalGet($path . '/' . $new_multiplier . '/delete');
    $this->drupalPost(NULL, array(), t('Confirm'));
    $this->assertText('Multiplier ' . $new_multiplier . ' was deleted');

    // Verify the deleted multiplier is no longer visible on the multiplier overview page.
    $this->drupalGet($path);
    $this->assertNoFieldByName('multipliers[' . $new_multiplier . ']');

    // Verify the deleted multiplier is deleted.
    $multipliers = drupal_map_assoc(config('breakpoint')->get('multipliers'));
    $this->assertFalse(in_array($new_multiplier, $multipliers), t('Multiplier %multiplier was deleted.', array('%multiplier' => $new_multiplier)));

  }
}
