<?php
/**
 * @file
 * Definition of Drupal\breakpoints\Tests\BreakpointsAdminTest.
 */
namespace Drupal\breakpoints\Tests;

use Drupal\breakpoints\Tests\BreakpointsTestBase;
use stdClass;

/**
 * Tests for breakpoints admin interface.
 */
class BreakpointsAdminTest extends BreakpointsTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Breakpoints administration functionality',
      'description' => 'Thoroughly test the administrative interface of the breakpoints module.',
      'group' => 'Breakpoints',
    );
  }

  function  setUp() {
    parent::setUp();

    // Create user.
    $this->admin_user = $this->drupalCreateUser(array(
      'administer breakpoints',
    ));

    $this->drupalLogin($this->admin_user);
  }

  /**
   * Test breakpoint administration functionality
   */
  function testBreakpointAdmin() {
    // Add breakpoint.
    $this->drupalGet('admin/config/media/breakpoints');
    $name = drupal_strtolower($this->randomName());
    $mediaquery = '(min-width: 600px)';
    $edit = array(
      'breakpoints[new][name]' => $name,
      'breakpoints[new][breakpoint]' => $mediaquery,
    );

    $this->drupalPost(NULL, $edit, t('Save'));

    $machine_name = 'breakpoints.' . BREAKPOINTS_SOURCE_TYPE_CUSTOM . '.user.' . $name;
    // Verify the breakpoint was saved and verify default weight of the breakpoint.
    $this->drupalGet('admin/config/media/breakpoints');
    $this->assertFieldByName("breakpoints[$machine_name][weight]", 0, t('Breakpoint weight was saved.'));

    // Change the weight of the breakpoint.
    $edit = array(
      "breakpoints[$machine_name][weight]" => 5,
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertFieldByName("breakpoints[$machine_name][weight]", 5, t('Breakpoint weight was saved.'));

    // Submit the form.
    $this->drupalGet('admin/config/media/breakpoints');
    $this->drupalPost(NULL, array(), t('Save'));

    // Verify that the custom weight of the breakpoint has been retained.
    $this->drupalGet('admin/config/media/breakpoints');
    $this->assertFieldByName("breakpoints[$machine_name][weight]", 5, t('Breakpoint weight was retained.'));

    // Change the multipliers of the breakpoint.
    $edit = array(
      "breakpoints[$machine_name][multipliers][1.5x]" => "1.5x",
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    $id = drupal_clean_css_identifier('edit-breakpoints-' . $machine_name . '-multipliers-');
    $this->assertFieldChecked($id . '15x', t('Breakpoint multipliers were saved.'));
    $this->assertNoFieldChecked($id . '2x', t('Breakpoint multipliers were saved.'));

    // Submit the form.
    $this->drupalGet('admin/config/media/breakpoints');
    $this->drupalPost(NULL, array(), t('Save'));

    // Verify that the custom weight of the breakpoint has been retained.
    $this->drupalGet('admin/config/media/breakpoints');
    $this->assertFieldChecked($id . '15x', t('Breakpoint multipliers were retained.'));
    $this->assertNoFieldChecked($id . '2x', t('Breakpoint multipliers were retained.'));

    // Disable breakpoint.
    $this->assertLinkByHref('admin/config/media/breakpoints/disable/' . $machine_name);
    $this->drupalGet('admin/config/media/breakpoints/disable/' . $machine_name);
    $this->drupalPost(NULL, array(), t('Confirm'));

    // Verify that the breakpoint is disabled.
    $this->assertLinkByHref('admin/config/media/breakpoints/enable/' . $machine_name, 0, t('Breakpoint was disabled.'));
  }

}
