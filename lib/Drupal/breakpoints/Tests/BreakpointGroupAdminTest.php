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
      $breakpoint->name = $this->randomName();
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
      $breakpoints[$breakpoint->machine_name] = $breakpoint;
    }
    // Add breakpoint group.
    $this->drupalGet('admin/config/media/breakpoints/groups/add');
    $name = $this->randomName();
    $machine_name = drupal_strtolower($name);
    $breakpoint = reset($breakpoints);
    $edit = array(
      'name' => $name,
      'machine_name' => $machine_name,
      'breakpoints[' . $breakpoint->machine_name . ']' => $breakpoint->machine_name,
    );

    $this->drupalPost(NULL, $edit, t('Save'));

    // Verify the breakpoint was saved.
    $this->drupalGet('admin/config/media/breakpoints/groups/' . $machine_name);
    $this->assertResponse(200, t('Breakpoint group was saved.'));

    // Verify the breakpoint was attached to the group.
    $this->assertField('breakpoints[' . $breakpoint->machine_name . '][name]', t('The Breakpoint was added.'));

    // Add breakpoints to the breakpoint group.
    $this->drupalGet('admin/config/media/breakpoints/groups/' . $machine_name . '/edit');
    $edit = array();
    foreach ($breakpoints as $key => $breakpoint) {
      $edit['breakpoints[' . $key . ']'] = $key;
    }
    $this->drupalPost(NULL, $edit, t('Save'));

    // Verify the breakpoints were attached to the group.
    $this->drupalGet('admin/config/media/breakpoints/groups/' . $machine_name);
    foreach ($breakpoints as $key => $breakpoint) {
      $this->assertField('breakpoints[' . $key . '][name]', t('The Breakpoint was added.'));
    }

    // Change the order breakpoints of the breakpoints within the breakpoint group.
    $breakpoint = end($breakpoints);
    $edit = array(
      "breakpoints[{$breakpoint->machine_name}][weight]" => 0,
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertFieldByName("breakpoints[{$breakpoint->machine_name}][weight]", 0, t('Breakpoint weight was saved.'));

    // Submit the form.
    $this->drupalGet('admin/config/media/breakpoints');
    $this->drupalPost(NULL, array(), t('Save'));

    // Verify that the custom weight of the breakpoint has been retained.
    $this->drupalGet('admin/config/media/breakpoints/groups/' . $machine_name);
    $this->assertFieldByName("breakpoints[{$breakpoint->machine_name}][weight]", 0, t('Breakpoint weight was retained.'));

    // Verify that the weight has only changed within the group.
    $this->drupalGet('admin/config/media/breakpoints');
    $this->assertFieldByName("breakpoints[{$breakpoint->machine_name}][weight]", $breakpoint->weight, t('Breakpoint weight has only changed within the group.'));

    // Change the multipliers of the breakpoint within the group.
    $edit = array(
      "breakpoints[{$breakpoint->machine_name}][multipliers][1.5x]" => "1.5x",
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    $id = drupal_clean_css_identifier('edit-breakpoints-' . $breakpoint->machine_name . '-multipliers-');
    $this->assertFieldChecked($id . '15x', t('Breakpoint multipliers were saved.'));
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
    $edit = array(
      'name' => $this->randomName(),
      'machine_name' => $machine_name,
      'breakpoints[' . $breakpoint->machine_name . ']' => $breakpoint->machine_name,
    );

    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertText('The machine-readable name is already in use. It must be unique.');

    // Delete breakpoint.
    $this->drupalGet('admin/config/media/breakpoints/groups/' . $machine_name . '/delete');
    $this->drupalPost(NULL, array(), t('Confirm'));

    // Verify that deleted breakpoint no longer exists.
    $this->drupalGet('admin/config/media/breakpoints/groups/' . $machine_name);
    $this->assertResponse(404, t('Breakpoint group was deleted.'));
  }

  /**
   * Test breakpoint group export/import functionality.
   * @todo Wait for Ctools to implement http://drupal.org/node/1649238, then make this a public function.
   */
  private function testBreakpointGroupExportImport() {

    // Breakpoints.
    $breakpoints = array();
    $breakpoint = new stdClass();
    $breakpoint->disabled = FALSE;
    $breakpoint->api_version = 1;
    $breakpoint->machine_name = 'custom.user.mobile';
    $breakpoint->name = 'mobile';
    $breakpoint->breakpoint = '(min-width: 0px)';
    $breakpoint->source = 'user';
    $breakpoint->source_type = 'custom';
    $breakpoint->status = 1;
    $breakpoint->weight = 4;
    $breakpoint->multipliers = array(
      '1.5x' => 0,
      '2x' => 0,
    );

    $breakpoints[$breakpoint->machine_name] = $breakpoint;

    $breakpoint = new stdClass();
    $breakpoint->disabled = FALSE;
    $breakpoint->api_version = 1;
    $breakpoint->machine_name = 'custom.user.narrow';
    $breakpoint->name = 'narrow';
    $breakpoint->breakpoint = '(min-width: 560px)';
    $breakpoint->source = 'user';
    $breakpoint->source_type = 'custom';
    $breakpoint->status = 1;
    $breakpoint->weight = 5;
    $breakpoint->multipliers = array(
      '1.5x' => 0,
      '2x' => 0,
    );

    $breakpoints[$breakpoint->machine_name] = $breakpoint;

    $breakpoint = new stdClass();
    $breakpoint->disabled = FALSE;
    $breakpoint->api_version = 1;
    $breakpoint->machine_name = 'custom.user.wide';
    $breakpoint->name = 'wide';
    $breakpoint->breakpoint = '(min-width: 851px)';
    $breakpoint->source = 'user';
    $breakpoint->source_type = 'custom';
    $breakpoint->status = 1;
    $breakpoint->weight = 6;
    $breakpoint->multipliers = array(
      '1.5x' => 0,
      '2x' => 0,
    );

    $breakpoints[$breakpoint->machine_name] = $breakpoint;

    $breakpoint = new stdClass();
    $breakpoint->disabled = FALSE;
    $breakpoint->api_version = 1;
    $breakpoint->machine_name = 'custom.user.tv';
    $breakpoint->name = 'tv';
    $breakpoint->breakpoint = 'only screen and (min-width: 3456px)';
    $breakpoint->source = 'user';
    $breakpoint->source_type = 'custom';
    $breakpoint->status = 1;
    $breakpoint->weight = 7;
    $breakpoint->multipliers = array(
      '1.5x' => 0,
      '2x' => 0,
    );

    $breakpoints[$breakpoint->machine_name] = $breakpoint;

    /**
     * Breakpoint group.
     */
    $breakpoint_group = new stdClass();
    $breakpoint_group->disabled = FALSE; /* Edit this to true to make a default breakpoint_group disabled initially */
    $breakpoint_group->api_version = 1;
    $breakpoint_group->machine_name = 'customgroup';
    $breakpoint_group->name = 'Customgroup';
    $breakpoint_group->breakpoints = array_keys($breakpoints);
    $breakpoint_group->type = 'custom';
    $breakpoint_group->overridden = 0;

    $importstring = array();
    $importstring[] = '/**';
    $importstring[] = ' * Breakpoints.';
    $importstring[] = ' */';
    $importstring[] = '$breakpoints = array();';
    $importstring[] = '$breakpoint = new stdClass();';
    $importstring[] = '$breakpoint->disabled = FALSE; /* Edit this to true to make a default breakpoint disabled initially */';
    $importstring[] = '$breakpoint->api_version = 1;';
    $importstring[] = '$breakpoint->machine_name = \'custom.user.mobile\';';
    $importstring[] = '$breakpoint->name = \'mobile\';';
    $importstring[] = '$breakpoint->breakpoint = \'(min-width: 0px)\';';
    $importstring[] = '$breakpoint->source = \'user\';';
    $importstring[] = '$breakpoint->source_type = \'custom\';';
    $importstring[] = '$breakpoint->status = 1;';
    $importstring[] = '$breakpoint->weight = 4;';
    $importstring[] = '$breakpoint->multipliers = array(';
    $importstring[] = '  \'1.5x\' => 0,';
    $importstring[] = '  \'2x\' => 0,';
    $importstring[] = ');';
    $importstring[] = '';
    $importstring[] = '$breakpoints[] = $breakpoint;';
    $importstring[] = '';
    $importstring[] = '$breakpoint = new stdClass();';
    $importstring[] = '$breakpoint->disabled = FALSE; /* Edit this to true to make a default breakpoint disabled initially */';
    $importstring[] = '$breakpoint->api_version = 1;';
    $importstring[] = '$breakpoint->machine_name = \'custom.user.narrow\';';
    $importstring[] = '$breakpoint->name = \'narrow\';';
    $importstring[] = '$breakpoint->breakpoint = \'(min-width: 560px)\';';
    $importstring[] = '$breakpoint->source = \'user\';';
    $importstring[] = '$breakpoint->source_type = \'custom\';';
    $importstring[] = '$breakpoint->status = 1;';
    $importstring[] = '$breakpoint->weight = 5;';
    $importstring[] = '$breakpoint->multipliers = array(';
    $importstring[] = '  \'1.5x\' => 0,';
    $importstring[] = '  \'2x\' => 0,';
    $importstring[] = ');';
    $importstring[] = '';
    $importstring[] = '$breakpoints[] = $breakpoint;';
    $importstring[] = '';
    $importstring[] = '$breakpoint = new stdClass();';
    $importstring[] = '$breakpoint->disabled = FALSE; /* Edit this to true to make a default breakpoint disabled initially */';
    $importstring[] = '$breakpoint->api_version = 1;';
    $importstring[] = '$breakpoint->machine_name = \'custom.user.wide\';';
    $importstring[] = '$breakpoint->name = \'wide\';';
    $importstring[] = '$breakpoint->breakpoint = \'(min-width: 851px)\';';
    $importstring[] = '$breakpoint->source = \'user\';';
    $importstring[] = '$breakpoint->source_type = \'custom\';';
    $importstring[] = '$breakpoint->status = 1;';
    $importstring[] = '$breakpoint->weight = 6;';
    $importstring[] = '$breakpoint->multipliers = array(';
    $importstring[] = '  \'1.5x\' => 0,';
    $importstring[] = '  \'2x\' => 0,';
    $importstring[] = ');';
    $importstring[] = '';
    $importstring[] = '$breakpoints[] = $breakpoint;';
    $importstring[] = '';
    $importstring[] = '$breakpoint = new stdClass();';
    $importstring[] = '$breakpoint->disabled = FALSE; /* Edit this to true to make a default breakpoint disabled initially */';
    $importstring[] = '$breakpoint->api_version = 1;';
    $importstring[] = '$breakpoint->machine_name = \'custom.user.tv\';';
    $importstring[] = '$breakpoint->name = \'tv\';';
    $importstring[] = '$breakpoint->breakpoint = \'only screen and (min-width: 3456px)\';';
    $importstring[] = '$breakpoint->source = \'user\';';
    $importstring[] = '$breakpoint->source_type = \'custom\';';
    $importstring[] = '$breakpoint->status = 1;';
    $importstring[] = '$breakpoint->weight = 7;';
    $importstring[] = '$breakpoint->multipliers = array(';
    $importstring[] = '  \'1.5x\' => 0,';
    $importstring[] = '  \'2x\' => 0,';
    $importstring[] = ');';
    $importstring[] = '';
    $importstring[] = '$breakpoints[] = $breakpoint;';
    $importstring[] = '';
    $importstring[] = '/**';
    $importstring[] = ' * Breakpoint group.';
    $importstring[] = ' */';
    $importstring[] = '$breakpoint_group = new stdClass();';
    $importstring[] = '$breakpoint_group->disabled = FALSE; /* Edit this to true to make a default breakpoint_group disabled initially */';
    $importstring[] = '$breakpoint_group->api_version = 1;';
    $importstring[] = '$breakpoint_group->machine_name = \'customgroup\';';
    $importstring[] = '$breakpoint_group->name = \'Customgroup\';';
    $importstring[] = '$breakpoint_group->breakpoints = $breakpoints;';
    $importstring[] = '$breakpoint_group->type = \'custom\';';
    $importstring[] = '$breakpoint_group->overridden = 0;';

    $this->drupalGet('admin/config/media/breakpoints/groups/import');
    $edit = array(
      "import" => implode("\n", $importstring),
    );
    $this->drupalPost(NULL, $edit, t('Import'));

    // Verify the breakpoint group was imported.
    $this->drupalGet('admin/config/media/breakpoints/groups/' . $breakpoint_group->machine_name);
    $this->assertResponse(200, t('Breakpoint group imported correctly'));

    // Verify the breakpoint group is loadable and has the correct data.
    $this->verifyBreakpointGroup($breakpoint_group);

    // Verify the breakpoint group exports correctly.
    $this->drupalGet('admin/config/media/breakpoints/groups/' . $breakpoint_group->machine_name . '/export');
    foreach ($importstring as $importline) {
      $importline = trim($importline);
      if (!empty($importline)) {
        // Text in a textarea is htmlencoded.
        $this->assertRaw(check_plain($importline));
      }
    }
  }
}
