<?php
/**
 * @file
 * Definition of Drupal\breakpoints\Tests\BreakpointGroupAdminTest.
 */
namespace Drupal\breakpoints\Tests;

use Drupal\breakpoints\Tests\BreakpointGroupTestBase;
use stdClass;

/**
 * Tests for breakpoint groups admin interface.
 */
class BreakpointGroupAdminTest extends BreakpointGroupTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('breakpoints_ui');

  public static function getInfo() {
    return array(
      'name' => 'Breakpoint Group administration functionality',
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
  function testBreakpointGroupAdmin() {
    // Add breakpoints.
    $breakpoints = array();
    for ($i = 0; $i <= 3; $i++) {
      $breakpoint = new stdClass();
      $breakpoint->disabled = FALSE;
      $breakpoint->api_version = 1;
      $breakpoint->name = drupal_strtolower($this->randomName());
      $width = ($i + 1) * 200;
      $breakpoint->breakpoint = "(min-width: {$width}px)";
      $breakpoint->source = 'user';
      $breakpoint->source_type = 'custom';
      $breakpoint->status = 1;
      $breakpoint->weight = $i;
      $breakpoint->multipliers = array(
        '1.5x' => 0,
        '2x' => 0,
      );
      breakpoints_breakpoint_save($breakpoint);
      $breakpoints[] = $breakpoint;
    }
    // Add breakpoint group.
    $this->drupalGet('admin/config/media/breakpoints/groups/add');
    $name = $this->randomName();
    $machine_name = drupal_strtolower($name);
    $breakpoint = reset($breakpoints);
    $config_name = breakpoints_breakpoint_config_name($breakpoint);
    $edit = array(
      'name' => $name,
      'machine_name' => $machine_name,
      'breakpoints[' . $config_name . ']' => $config_name,
    );

    $this->drupalPost(NULL, $edit, t('Save'));

    // Verify the breakpoint was saved.
    $this->drupalGet('admin/config/media/breakpoints/groups/' . $machine_name);
    $this->assertResponse(200, t('Breakpoint group was saved.'));

    // Verify the breakpoint was attached to the group.
    $this->assertField('breakpoints[' . $config_name . '][name]', t('The Breakpoint was added.'));

    // Add breakpoints to the breakpoint group.
    $this->drupalGet('admin/config/media/breakpoints/groups/' . $machine_name . '/edit');
    $edit = array();
    foreach ($breakpoints as $breakpoint) {
      $config_name = breakpoints_breakpoint_config_name($breakpoint);
      $edit['breakpoints[' . $config_name . ']'] = $config_name;
    }
    $this->drupalPost(NULL, $edit, t('Save'));

    // Verify the breakpoints were attached to the group.
    $this->drupalGet('admin/config/media/breakpoints/groups/' . $machine_name);
    foreach ($breakpoints as $breakpoint) {
      $config_name = breakpoints_breakpoint_config_name($breakpoint);
      $this->assertField('breakpoints[' . $config_name . '][name]', t('The Breakpoint was added.'));
    }

    // Change the order breakpoints of the breakpoints within the breakpoint group.
    $breakpoint = end($breakpoints);
    $config_name = breakpoints_breakpoint_config_name($breakpoint);
    $edit = array(
      "breakpoints[" . $config_name . "][weight]" => 0,
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertFieldByName("breakpoints[" . $config_name . "][weight]", 0, t('Breakpoint weight was saved.'));

    // Submit the form.
    $this->drupalGet('admin/config/media/breakpoints');
    $this->drupalPost(NULL, array(), t('Save'));

    // Verify that the custom weight of the breakpoint has been retained.
    $this->drupalGet('admin/config/media/breakpoints/groups/' . $machine_name);
    $this->assertFieldByName("breakpoints[" . $config_name . "][weight]", 0, t('Breakpoint weight was retained.'));

    // Verify that the weight has only changed within the group.
    $this->drupalGet('admin/config/media/breakpoints');
    $this->assertFieldByName("breakpoints[" . $config_name . "][weight]", $breakpoint->weight, t('Breakpoint weight has only changed within the group.'));

    // Change the multipliers of the breakpoint within the group.
    $edit = array(
      "breakpoints[" . $config_name . "][multipliers][1.5x]" => "1.5x",
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    $id = drupal_clean_css_identifier('edit-breakpoints-' . $config_name . '-multipliers-');
    $this->assertFieldChecked($id . '15x', t('Breakpoint multipliers were saved.' . $id . '15x'));
    $this->assertNoFieldChecked($id . '2x', t('Breakpoint multipliers were saved.'));

    // Submit the form.
    $this->drupalGet('admin/config/media/breakpoints/groups/' . $machine_name);
    $this->drupalPost(NULL, array(), t('Save'));

    // Verify that the multipliers of the breakpoint has been retained.
    $this->drupalGet('admin/config/media/breakpoints/groups/' . $machine_name);
    $this->assertFieldChecked($id . '15x', t('Breakpoint multipliers were retained.'));
    $this->assertNoFieldChecked($id . '2x', t('Breakpoint multipliers were retained.'));

    // Verify that the multipliers only changed within the group.
    $this->drupalGet('admin/config/media/breakpoints');
    $this->assertFieldChecked($id . '15x', t('Breakpoint multipliers were retained.'));
    $this->assertNoFieldChecked($id . '2x', t('Breakpoint multipliers were retained.'));

    // Attempt to create a breakpoint group of the same machine name as the disabled
    // breakpoint but with a different human readable name.
    // Add breakpoint group.
    $this->drupalGet('admin/config/media/breakpoints/groups/add');
    $breakpoint = reset($breakpoints);
    $config_name = breakpoints_breakpoint_config_name($breakpoint);
    $edit = array(
      'name' => $this->randomName(),
      'machine_name' => $machine_name,
      'breakpoints[' . $config_name . ']' => $config_name,
    );

    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertText('The machine-readable name is already in use. It must be unique.');

    // Delete breakpoint.
    $this->drupalGet('admin/config/media/breakpoints/groups/' . $machine_name . '/delete');
    $this->drupalPost(NULL, array(), t('Confirm'));
  }

}
