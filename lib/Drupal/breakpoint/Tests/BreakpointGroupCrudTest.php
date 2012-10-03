<?php
/**
 * @file
 * Definition of Drupal\breakpoint\Tests\BreakpointGroupCrudTest.
 */

namespace Drupal\breakpoint\Tests;

use Drupal\breakpoint\Tests\BreakpointGroupTestBase;
use Drupal\breakpoint\BreakpointGroup;
use Drupal\breakpoint\Breakpoint;

/**
 * Tests for breakpoint group CRUD operations.
 */
class BreakpointGroupCrudTest extends BreakpointGroupTestBase {

  /**
   * Drupal\simpletest\WebTestBase\getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'Breakpoint group CRUD operations',
      'description' => 'Test creation, loading, updating, deleting of breakpoint groups.',
      'group' => 'Breakpoint',
    );
  }

  /**
   * Test CRUD operations for breakpoint groups.
   */
  public function testBreakpointGroupCrud() {
    // Add breakpoints.
    $breakpoints = array();
    for ($i = 0; $i <= 3; $i++) {
      $width = ($i + 1) * 200;
      $values = array(
        'name' => drupal_strtolower($this->randomName()),
        'weight' => $i,
        'mediaQuery' => "(min-width: {$width}px)",
      );
      $breakpoint = new Breakpoint($values);
      $breakpoint->save();
      $breakpoints[$breakpoint->id()] = $breakpoint;
    }
    // Add a breakpoint group with minimum data only.
    $label = $this->randomName();
    $values = array(
      'label' => $label,
      'id' => drupal_strtolower($label),
    );

    $group = new BreakpointGroup($values);
    $group->save();
    $this->verifyBreakpointGroup($group);

    // Update the breakpoint group.
    $group->breakpoints = array_keys($breakpoints);
    $group->save();
    $this->verifyBreakpointGroup($group);

    // Duplicate the breakpoint group.
    $new_set = new BreakpointGroup();
    $new_set->label = t('Clone of') . ' ' . $group->label();
    $new_set->id = '';
    $new_set->sourceType = Breakpoint::SOURCE_TYPE_CUSTOM;
    $new_set->breakpoints = $group->breakpoints;
    $duplicated_set = $group->createDuplicate();
    $this->verifyBreakpointGroup($duplicated_set, $new_set);

    // Delete the breakpoint group.
    $group->delete();
    $this->assertFalse(entity_load('breakpoint_group', $group->id), t('breakpoint_group_load: Loading a deleted breakpoint group returns false.'), t('Breakpoints API'));
  }
}
