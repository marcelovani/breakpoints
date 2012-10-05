<?php
/**
 * @file
 * Definition of Drupal\breakpoint_ui\Tests\BreakpointGroupAdminTest.
 */
namespace Drupal\breakpoint_ui\Tests;

use Drupal\breakpoint\Tests\BreakpointGroupTestBase;
use Drupal\breakpoint\Breakpoint;

/**
 * Tests for breakpoint groups admin interface.
 */
class BreakpointUIBreakpointGroupAdminTest extends BreakpointGroupTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('breakpoint_ui');

  public static function getInfo() {
    return array(
      'name' => 'Breakpoint Group administration functionality',
      'description' => 'Thoroughly test the administrative interface of the breakpoint module.',
      'group' => 'Breakpoint UI',
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
  function testCustomBreakpointGroupAdmin() {
    $group = t('Breakpoint Group UI');
    // Add breakpoints.
    $breakpoints = array();
    for ($i = 0; $i <= 3; $i++) {
      $width = ($i + 1) * 200;
      $values = array(
        'name' => drupal_strtolower($this->randomName()),
        'mediaQuery' => "(min-width: {$width}px)",
      );
      $breakpoint = entity_create('breakpoint', $values);
      $breakpoint->save();
      $breakpoints[$breakpoint->id] = $breakpoint;
    }
    // Add breakpoint group.
    $this->drupalGet('admin/config/media/breakpoint/breakpoint_group/add');
    $label = $this->randomName();
    $machine_name = drupal_strtolower($name);
    $breakpoint = reset($breakpoints);
    $edit = array(
      'label' => $name,
      'id' => $machine_name,
      'breakpoints[' . $breakpoint->id . '][label]' => $breakpoint->label(),
      'breakpoints[' . $breakpoint->id . '][mediaQuery]' => $breakpoint->mediaQuery,
    );

    $this->drupalPost(NULL, $edit, t('Save'));

    // Verify the breakpoint was saved.
    $this->assertText(t('Breakpoint group @group was saved', array('@group' => $name)), $group);
    $this->drupalGet('admin/config/media/breakpoint/breakpoint_group/' . $machine_name . '/edit');
    $this->assertResponse(200, t('Breakpoint group was saved.'));
    $this->assertField('breakpoints[' . $breakpoint->id . '][label]', t('The breakpointgroup should contain the right breakpoints.'), $group);

    // Add breakpoints to the breakpoint group.
    $edit = array();
    foreach ($breakpoints as $breakpoint) {
      $edit['breakpoints[' . $breakpoint->id() . '][label]'] = $breakpoint->label();
      $edit['breakpoints[' . $breakpoint->id . '][mediaQuery]'] = $breakpoint->mediaQuery;
    }
    $this->drupalPost(NULL, $edit, t('Save'));

    // Verify the breakpoints were attached to the set.
    $this->drupalGet('admin/config/media/breakpoint/breakpoint_group/' . $machine_name . '/edit');
    foreach ($breakpoints as $breakpoint) {
      $this->assertField('breakpoints[' . $breakpoint->id() . '][label]', t('The Breakpoint was added.'), $group);
    }

    // Change the order breakpoints of the breakpoints within the breakpoint group.
    $breakpoint = end($breakpoints);
    $edit = array(
      "breakpoints[" . $breakpoint->id() . "][weight]" => 0,
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertFieldByName("breakpoints[" . $breakpoint->id() . "][weight]", 0, t('Breakpoint weight was saved.'));

    // Submit the form.
    $this->drupalPost(NULL, array(), t('Save'));

    // Verify that the custom weight of the breakpoint has been retained.
    $this->drupalGet('admin/config/media/breakpoint/sets/' . $machine_name . '/edit');
    $this->assertFieldByName("breakpoints[" . $breakpoint->id() . "][weight]", 0, t('Breakpoint weight was retained.'));

    // Change the multipliers of the breakpoint within the set.
    $edit = array(
      "breakpoints[" . $breakpoint->id() . "][multipliers][1.5x]" => "1.5x",
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    $id = drupal_clean_css_identifier('edit-breakpoints-' . $config_name . '-multipliers-');
    $this->assertFieldChecked($id . '15x', t('Breakpoint multipliers were saved.'));
    $this->assertNoFieldChecked($id . '2x', t('Breakpoint multipliers were saved.'));

    // Submit the form.
    $this->drupalGet('admin/config/media/breakpoint/sets/' . $machine_name);
    $this->drupalPost(NULL, array(), t('Save'));

    // Verify that the multipliers of the breakpoint has been retained.
    $this->drupalGet('admin/config/media/breakpoint/sets/' . $machine_name);
    $this->assertFieldChecked($id . '15x', t('Breakpoint multipliers were retained.'));
    $this->assertNoFieldChecked($id . '2x', t('Breakpoint multipliers were retained.'));

    // Verify that the multipliers only changed within the set.
    $this->drupalGet('admin/config/media/breakpoint');
    $this->assertFieldChecked($id . '15x', t('Breakpoint multipliers were retained.'));
    $this->assertNoFieldChecked($id . '2x', t('Breakpoint multipliers were retained.'));

    // Attempt to create a breakpoint group of the same machine name as the disabled
    // breakpoint but with a different human readable name.
    $this->drupalGet('admin/config/media/breakpoint/breakpoint_group/add');
    $breakpoint = reset($breakpoints);
    $edit = array(
      'label' => $this->randomName(),
      'id' => $machine_name,
      'breakpoints[' . $breakpoint->id . '][label]' => $breakpoint->label(),
      'breakpoints[' . $breakpoint->id . '][mediaQuery]' => $breakpoint->mediaQuery,
    );

    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertText('The machine-readable name is already in use. It must be unique.', t('Users can\'t add two breakpoint groups with the same machine readable names.'), $group);

    // Delete breakpoint.
    $this->drupalGet('admin/config/media/breakpoint/breakpoint_group/' . $machine_name . '/delete');
    $this->drupalPost(NULL, array(), t('Confirm'));

    // Verify the breakpoint group is not listed anymore.
    $this->assertNoText($machine_name, t('Breakpoint groups that are deleted are no longer listed'), $group);
  }

}
