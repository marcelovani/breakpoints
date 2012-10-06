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
 *
 * @todo Breakpoint groups provided by modules and themes.
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
      $breakpoints[$breakpoint->id()] = $breakpoint;
    }
    // Add breakpoint group.
    $this->drupalGet('admin/config/media/breakpoint/breakpoint_group/add');
    $label = $this->randomName();
    $machine_name = drupal_strtolower($label);
    $breakpoint = reset($breakpoints);
    $edit = array(
      'label' => $label,
      'id' => $machine_name,
      'breakpoint' => $breakpoint->id(),
    );

    $this->drupalPost(NULL, $edit, t('Add breakpoint'));


    $edit += array(
      'breakpoints[' . $breakpoint->id() . '][label]' => $breakpoint->label(),
      'breakpoints[' . $breakpoint->id() . '][mediaQuery]' => $breakpoint->mediaQuery,
    );
    unset($edit['breakpoint']);
    $this->drupalPost(NULL, $edit, t('Save'));

    // Verify the breakpoint was saved.
    $this->assertText(t('Breakpoint group @group saved.', array('@group' => $label)), t('Breakpoint group was saved.'), $group);
    $this->drupalGet('admin/config/media/breakpoint/breakpoint_group/' . $machine_name . '/edit');
    $this->assertResponse(200, t('Breakpoint group was saved.'));
    $this->assertField('breakpoints[' . $breakpoint->id() . '][label]', t('The breakpointgroup should contain the right breakpoints.'), $group);

    // Add breakpoints to the breakpoint group.
    $remaining_breakpoints = array_diff_key($breakpoints, array($breakpoint->id() => ''));
    $this->verbose(highlight_string("<?php \n" . var_export($remaining_breakpoints, TRUE), TRUE));
    unset($edit['id']);
    foreach ($remaining_breakpoints as $breakpoint) {
      $edit['breakpoint'] = $breakpoint->id();
      $this->drupalPost(NULL, $edit, t('Add breakpoint'));
      $edit['breakpoints[' . $breakpoint->id() . '][label]'] = $breakpoint->label();
      $edit['breakpoints[' . $breakpoint->id() . '][mediaQuery]'] = $breakpoint->mediaQuery;
    }
    unset($edit['breakpoint']);
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
    
    // Verify the weight was saved.
    $this->drupalGet('admin/config/media/breakpoint/breakpoint_group/' . $machine_name . '/edit');
    $this->assertFieldByName("breakpoints[" . $breakpoint->id() . "][weight]", 0, t('Breakpoint weight was saved.'));

    // Submit the form.
    $this->drupalPost(NULL, array(), t('Save'));

    // Verify that the custom weight of the breakpoint has been retained.
    $this->drupalGet('admin/config/media/breakpoint/breakpoint_group/' . $machine_name . '/edit');
    $this->assertFieldByName("breakpoints[" . $breakpoint->id() . "][weight]", 0, t('Breakpoint weight was retained.'));

    // Change the multipliers of the breakpoint within the set.
    $edit["breakpoints[" . $breakpoint->id() . "][multipliers][1.5x]"] = "1.5x";
    $this->drupalPost(NULL, $edit, t('Save'));

    // Verify the multipliers of the breakpoints were saved.
    $this->drupalGet('admin/config/media/breakpoint/breakpoint_group/' . $machine_name . '/edit');
    $id = drupal_clean_css_identifier('edit-breakpoints-' . $breakpoint->id() . '-multipliers-');
    $this->assertFieldChecked($id . '15x', t('Breakpoint multipliers were saved.'));
    $this->assertNoFieldChecked($id . '2x', t('Breakpoint multipliers were saved.'));

    // Submit the form.
    $this->drupalPost(NULL, array(), t('Save'));

    // Verify that the multipliers of the breakpoint has been retained.
    $this->drupalGet('admin/config/media/breakpoint/breakpoint_group/' . $machine_name . '/edit');
    $this->assertFieldChecked($id . '15x', t('Breakpoint multipliers were retained.'));
    $this->assertNoFieldChecked($id . '2x', t('Breakpoint multipliers were retained.'));

    // Attempt to create a breakpoint group of the same machine name as the disabled
    // breakpoint but with a different human readable name.
    $this->drupalGet('admin/config/media/breakpoint/breakpoint_group/add');
    $edit = array(
      'label' => $this->randomName(),
      'id' => $machine_name,
    );

    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertText('The machine-readable name is already in use. It must be unique.', t('Users can\'t add two breakpoint groups with the same machine readable names.'), $group);

    // Delete breakpoint.
    $this->drupalGet('admin/config/media/breakpoint/breakpoint_group/' . $machine_name . '/delete');
    $this->drupalPost(NULL, array(), t('Delete'));

    // Verify the breakpoint group is not listed anymore.
    $this->assertNoText($machine_name, t('Breakpoint groups that are deleted are no longer listed'), $group);
  }

}
