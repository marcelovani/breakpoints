<?php

/**
 * @file
 * Definition of Drupal\breakpoint_ui\Tests\BreakpointAdminTest.
 */

namespace Drupal\breakpoint_ui\Tests;

use Drupal\breakpoint\Tests\BreakpointTestBase;
use Drupal\breakpoint\Breakpoint;

/**
 * Tests for breakpoints admin interface.
 */
class BreakpointUIBreakpointAdminTest extends BreakpointTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('breakpoint_ui');

  public static function getInfo() {
    return array(
      'name' => 'Breakpoints administration functionality',
      'description' => 'Thoroughly test the administrative interface of the breakpoint module.',
      'group' => 'Breakpoint UI',
    );
  }

  function setUp() {
    parent::setUp();

    // Create user.
    $this->admin_user = $this->drupalCreateUser(array(
      'administer breakpoints',
    ));

    $this->drupalLogin($this->admin_user);
  }

  /**
   * Test administration functionality for breakpoints created by users.
   */
  function testCustomBreakpointAdmin() {
    $group = t('Breakpoint UI');
    // Add breakpoint.
    $this->drupalGet('admin/config/media/breakpoint/breakpoint/add');
    $label = $this->randomName();
    $name = drupal_strtolower($label);
    // Try an illegal mediaquery.
    $mediaquery = $this->randomName() . ' ' . $this->randomName();
    $edit = array(
      'label' => $label,
      'name' => $name,
      'mediaQuery' => $mediaquery,
    );

    $this->drupalPost(NULL, $edit, t('Save'));

    $this->assertText(t('Invalid media query detected.'), t('Entering an illegal media query returns an error'), $group);

    // Try a valid mediaquery.
    $edit['mediaQuery'] = '(min-width: 600px)';
    $this->drupalPost(NULL, $edit, t('Save'));

    $machine_name = Breakpoint::SOURCE_TYPE_CUSTOM . '.user.' . $name;
    // Verify the breakpoint was saved.
    $this->assertText(t('Breakpoint @breakpoint saved.', array('@breakpoint' => $label)), t('Breakpoint was saved.'), $group);
    $this->assertText($machine_name, t('Breakpoint is displayed in the overview table after it was saved.'), $group);

    // Verify all the correct links are present.
    foreach (array('edit', 'disable', 'delete') as $action) {
      $this->assertLinkByHref('admin/config/media/breakpoint/breakpoint/' . $machine_name . '/' . $action, 0, t('@action link found for @breakpoint', array('@action' => drupal_ucfirst($action), '@breakpoint' => $label)), $group);
    }

    // Change the multipliers of the breakpoint.
    $this->drupalGet('admin/config/media/breakpoint/breakpoint/' . $machine_name . '/edit');
    $edit = array(
      'multipliers[1.5x]' => '1.5x',
    );
    $this->drupalPost(NULL, $edit, t('Save'));

    // Verify the multipliers of the breakpoint have been saved.
    $this->drupalGet('admin/config/media/breakpoint/breakpoint/' . $machine_name . '/edit');
    $id = drupal_clean_css_identifier('edit-multipliers-');
    $this->assertFieldChecked($id . '15x', t('Breakpoint multipliers were saved.'));
    $this->assertNoFieldChecked($id . '2x', t('Breakpoint multipliers were saved.'));

    // Disable breakpoint.
    $this->drupalGet('admin/config/media/breakpoint/breakpoint');
    $this->assertLinkByHref('admin/config/media/breakpoint/breakpoint/' . $machine_name . '/disable');
    $this->drupalGet('admin/config/media/breakpoint/breakpoint/' . $machine_name . '/disable');
    $this->drupalPost(NULL, array(), t('Disable'));

    // Verify that the breakpoint is disabled.
    $this->assertText(t('Breakpoint @breakpoint has been disabled.', array('@breakpoint' => $label)), t('Breakpoint was disabled.'), $group);
    $this->assertLinkByHref('admin/config/media/breakpoint/breakpoint/' . $machine_name . '/enable', 0, t('Breakpoint was enabled.'), $group);

    // Enable the breakpoint again.
    $this->drupalGet('admin/config/media/breakpoint/breakpoint/' . $machine_name . '/enable');
    $this->drupalPost(NULL, array(), t('Enable'));

    // Verify that the breakpoint is disabled.
    $this->assertText(t('Breakpoint @breakpoint has been enabled.', array('@breakpoint' => $label)), t('Breakpoint was ebabled.'), $group);
    $this->assertLinkByHref('admin/config/media/breakpoint/breakpoint/' . $machine_name . '/disable', 0, t('Breakpoint was enabled.'), $group);

    // Delete the breakpoint.
    $this->drupalGet('admin/config/media/breakpoint/breakpoint/' . $machine_name . '/delete');
    $this->drupalPost(NULL, array(), t('Delete'));

    // Verify that the breakpoint is deleted.
    $this->assertText(t('Breakpoint @breakpoint has been deleted.', array('@breakpoint' => $label)), t('Breakpoint was deleted.'), $group);
    $this->assertNoText($machine_name, t('Breakpoint is not displayed in the overview table after it was deleted.'), $group);
  }

  /**
   * Test admin functionality for theme- or module-provided breakpoints.
   */
  function testThemeModuleBreakpointAdmin() {
    $group = t('Breakpoint UI');

    // Enable seven and breakpoint_ui_test to load their breakpoints.
    theme_enable(array('seven'));
    module_enable(array('breakpoint_ui_test'));
    
    // Do the tests for all breakpoints defined by Seven and breakpoint_ui_test.
    $breakpoints = entity_load_multiple('breakpoint', array('theme.seven.mobile', 'theme.seven.wide', 'module.breakpoint_ui_test.uitestmobile', 'module.breakpoint_ui_test.uitestwide'));
    
    $this->assertEqual(count($breakpoints), 4, t('All theme- and module-provided breakpoints are loaded.' . count($breakpoints)), $group);
    $this->verbose(highlight_string('<?php ' . var_export($breakpoints, TRUE), TRUE));

    foreach ($breakpoints as $machine_name => $breakpoint) {
      $this->drupalGet('admin/config/media/breakpoint/breakpoint');

      // Verify the breakpoints provided by the theme and module are present.
      $this->assertText($machine_name, t('@breakpoint breakpoint should be loaded.', array($breakpoint->label())), $group);

       // Verify all the correct links are present.
      foreach (array('edit', 'disable') as $action) {
        $this->assertLinkByHref('admin/config/media/breakpoint/breakpoint/' . $machine_name . '/' . $action, 0, t('@action link found for @breakpoint', array('@action' => drupal_ucfirst($action), '@breakpoint' => $breakpoint->label())), $group);
      }

      // Assert there are no delete links for Seven's breakpoints.
      $this->assertNoLinkByHref('admin/breakpoints/breakpoint' . $machine_name . '/delete', t('Breakpoint @breakpoint should not have a delete link.', array('@breakpoint' => $breakpoint->label())), $group);

      // Edit the breakpoint.
      $this->drupalGet('admin/config/media/breakpoint/breakpoint/' . $machine_name . '/edit');
      $this->assertFieldByXPath("//input[@name='mediaQuery' and @disabled='disabled']", $breakpoint->mediaQuery, t('Media query field is disabled for theme- and module-provided breakpoints.'), $group);
      $edit = array(
        'multipliers[1.5x]' => '1.5x',
      );
      $this->drupalPost(NULL, $edit, t('Save'));

      // Verify the multipliers of the breakpoint have been saved.
      $this->drupalGet('admin/config/media/breakpoint/breakpoint/' . $machine_name . '/edit');
      $id = drupal_clean_css_identifier('edit-multipliers-');
      $this->assertFieldChecked($id . '15x', t('Breakpoint multipliers were saved.'));
      $this->assertNoFieldChecked($id . '2x', t('Breakpoint multipliers were saved.'));

      // Verify the media query hasn't changed.
      $this->assertFieldByName('mediaQuery', $breakpoint->mediaQuery, t('Users should not be able to change the media query of theme- or module-provided breakpoints.'));
      
      // Disable breakpoint.
      $this->drupalGet('admin/config/media/breakpoint/breakpoint');
      $this->assertLinkByHref('admin/config/media/breakpoint/breakpoint/' . $machine_name . '/disable');
      $this->drupalGet('admin/config/media/breakpoint/breakpoint/' . $machine_name . '/disable');
      $this->drupalPost(NULL, array(), t('Disable'));

      // Verify that the breakpoint is disabled.
      $this->assertText(t('Breakpoint @breakpoint has been disabled.', array('@breakpoint' => $breakpoint->label())), t('Breakpoint was disabled.'), $group);
      $this->assertLinkByHref('admin/config/media/breakpoint/breakpoint/' . $machine_name . '/enable', 0, t('Breakpoint was enabled.'), $group);

      // Enable the breakpoint again.
      $this->drupalGet('admin/config/media/breakpoint/breakpoint/' . $machine_name . '/enable');
      $this->drupalPost(NULL, array(), t('Enable'));

      // Verify that the breakpoint is disabled.
      $this->assertText(t('Breakpoint @breakpoint has been enabled.', array('@breakpoint' => $breakpoint->label())), t('Breakpoint was ebabled.'), $group);

      // Try to delete the breakpoint.
      $this->drupalGet('admin/config/media/breakpoint/breakpoint/' . $machine_name . '/delete');
      $this->assertResponse('403', t('Users should not be able to delete a breakpoint provided by a theme or module.'));
    }
  }

}
