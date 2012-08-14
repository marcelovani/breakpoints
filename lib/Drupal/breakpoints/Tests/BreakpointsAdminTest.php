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
    $name = $this->randomName();
    $mediaquery = '(min-width: 600px)';
    $edit = array(
      'breakpoints[new][name]' => $name,
      'breakpoints[new][machine_name]' => drupal_strtolower($name),
      'breakpoints[new][breakpoint]' => $mediaquery,
    );

    $this->drupalPost(NULL, $edit, t('Save'));

    $machine_name = BREAKPOINTS_SOURCE_TYPE_CUSTOM . '.user.' . drupal_strtolower($name);
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

    // Attempt to create a breakpoint with the same machine name as the disabled
    // breakpoint but with a different human readable name.
    $edit = array(
      'breakpoints[new][name]' => 'New Breakpoint',
      'breakpoints[new][machine_name]' => drupal_strtolower($name),
      'breakpoints[new][breakpoint]' => $mediaquery,
      'breakpoints[new][multipliers][1.5x]' => 0,
      'breakpoints[new][multipliers][2x]' => 0,
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertText('The machine-readable name is already in use. It must be unique.');

    // Delete breakpoint.
    $this->assertLinkByHref('admin/config/media/breakpoints/delete/' . $machine_name);
    $this->drupalGet('admin/config/media/breakpoints/delete/' . $machine_name);
    $this->drupalPost(NULL, array(), t('Confirm'));

    // Verify that deleted breakpoint no longer exists.
    $this->drupalGet('admin/config/media/breakpoints');
    $this->assertNoFieldByName('breakpoints[' . $machine_name . '][name]', '', t('Deleted breakpoint no longer exists'));
  }

  /**
   * Test breakpoint export/import functionality.
   * @todo Wait for Ctools to implement http://drupal.org/node/1649238, then make this a public function.
   */
  private function testBreakpointExportImport() {
    $breakpoint = new stdClass();
    $breakpoint->disabled = FALSE;
    $breakpoint->api_version = 1;
    $breakpoint->machine_name = 'custom.user.test';
    $breakpoint->name = 'test';
    $breakpoint->breakpoint = '(min-width: 600px)';
    $breakpoint->source = 'user';
    $breakpoint->source_type = 'custom';
    $breakpoint->status = 1;
    $breakpoint->weight = 0;
    $breakpoint->multipliers = array(
      '1.5x' => 0,
      '2x' => 0,
    );

    // Import a breakpoint;
    $importstring = array();
    $importstring[] = '$breakpoint = new stdClass();';
    $importstring[] = '$breakpoint->disabled = FALSE; /* Edit this to true to make a default breakpoint disabled initially */';
    $importstring[] = '$breakpoint->api_version = 1;';
    $importstring[] = '$breakpoint->machine_name = \'custom.user.test\';';
    $importstring[] = '$breakpoint->name = \'test\';';
    $importstring[] = '$breakpoint->breakpoint = \'(min-width: 600px)\';';
    $importstring[] = '$breakpoint->source = \'user\';';
    $importstring[] = '$breakpoint->source_type = \'custom\';';
    $importstring[] = '$breakpoint->status = 1;';
    $importstring[] = '$breakpoint->weight = 0;';
    $importstring[] = '$breakpoint->multipliers = array(';
    $importstring[] = '  \'1.5x\' => 0,';
    $importstring[] = '  \'2x\' => 0,';
    $importstring[] = ');';

    $this->drupalGet('admin/config/media/breakpoints/groups/import-breakpoint');
    $edit = array(
      "import" => implode("\n", $importstring),
    );
    $this->drupalPost(NULL, $edit, t('Import'));

    // Verify the breakpoint was imported.
    $this->drupalGet('admin/config/media/breakpoints');
    $this->assertField('breakpoints[' . $breakpoint->machine_name . '][name]', t('Breakpoint imported correctly.'));

    // Verify the breakpoint is loadable and has the correct data.
    $this->verifyBreakpoint($breakpoint);

    // Verify the breakpoint exports correctly.
    $this->drupalGet('admin/config/media/breakpoints/export/' . $breakpoint->machine_name);
    foreach ($importstring as $importline) {
      $importline = trim($importline);
      if (!empty($importline)) {
        // Text in a textarea is htmlencoded.
        $this->assertRaw(check_plain($importline));
      }
    }
  }
}
