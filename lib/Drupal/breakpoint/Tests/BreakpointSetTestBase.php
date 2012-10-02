<?php
/**
 * @file
 * Definition of Drupal\breakpoint\Tests\BreakpointSetTestBase.
 */

namespace Drupal\breakpoint\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\breakpoint\BreakpointSet;

/**
 * Base class for Breakpoint Set tests.
 */
abstract class BreakpointSetTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('breakpoint');

  /**
   * Drupal\simpletest\WebTestBase\setUp().
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Verify that a breakpoint is properly stored.
   */
  public function verifyBreakpointSet(BreakpointSet $set, BreakpointSet $compare_set = NULL) {
    $properties = array(
      'label',
      'id',
      'breakpoints',
      'overridden',
      'sourceType',
    );
    $assert_set = t('Breakpoints API');

    // Verify breakpoint_breakpointset_load().
    $compare_set = is_null($compare_set) ? breakpoint_breakpointset_load($set->id) : $compare_set;

    foreach ($properties as $property) {
      $t_args = array(
        '%set' => $set->label(),
        '%property' => $property,
      );
      if (is_array($compare_set->{$property})) {
        $this->assertEqual(array_keys($compare_set->{$property}), array_keys($set->{$property}), t('breakpoint_breakpointset_load: Proper %property for breakpoint set %set.', $t_args), $assert_set);
      }
      else {
        $this->assertEqual($compare_set->{$property}, $set->{$property}, t('breakpoint_breakpointset_load: Proper %property . for breakpoint set %set.', $t_args), $assert_set);
      }
    }
  }
}
