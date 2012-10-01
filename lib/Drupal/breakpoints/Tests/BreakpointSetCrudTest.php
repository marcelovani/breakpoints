<?php
/**
 * @file
 * Definition of Drupal\breakpoints\Tests\BreakpointSetCrudTest.
 */

namespace Drupal\breakpoints\Tests;

use Drupal\breakpoints\Tests\BreakpointSetTestBase;
use Drupal\breakpoints\BreakpointSet;
use Drupal\breakpoints\Breakpoint;

/**
 * Tests for breakpoint set CRUD operations.
 */
class BreakpointSetCrudTest extends BreakpointSetTestBase {

  /**
   * Drupal\simpletest\WebTestBase\getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'Breakpoint Set CRUD operations',
      'description' => 'Test creation, loading, updating, deleting of breakpoint sets.',
      'group' => 'Breakpoints',
    );
  }

  /**
   * Test CRUD operations for breakpoint sets.
   */
  public function testBreakpointSetCrud() {
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
    // Add a breakpoint set with minimum data only.
    $label = $this->randomName();
    $values = array(
      'label' => $label,
      'id' => drupal_strtolower($label),
    );

    $set = new BreakpointSet($values);
    $set->save();
    $this->verifyBreakpointSet($set);

    // Update the breakpoint set.
    $set->breakpoints = array_keys($breakpoints);
    $set->save();
    $this->verifyBreakpointSet($set);

    // Duplicate the breakpoint set.
    $new_set = new BreakpointSet();
    $new_set->label = t('Clone of') . ' ' . $set->label();
    $new_set->id = '';
    $new_set->sourceType = Breakpoint::BREAKPOINTS_SOURCE_TYPE_CUSTOM;
    $new_set->breakpoints = $set->breakpoints;
    $duplicated_set = $set->createDuplicate();
    $this->verifyBreakpointSet($duplicated_set, $new_set);

    // Delete the breakpoint set.
    $set->delete();
    $this->assertFalse(breakpoints_breakpointset_load($set->id), t('breakpoints_breakpointset_load: Loading a deleted breakpoint set returns false.'), t('Breakpoints API'));
  }
}
