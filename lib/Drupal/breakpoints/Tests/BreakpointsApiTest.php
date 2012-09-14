<?php
/**
 * @file
 * Definition of Drupal\breakpoints\Tests\BreakpointsApiTest.
 */
namespace Drupal\breakpoints\Tests;

use Drupal\breakpoints\Tests\BreakpointsTestBase;
use stdClass;
use Exception;

/**
 * Tests for general breakpoints api functions.
 */
class BreakpointsApiTest extends BreakpointsTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Breakpoints general API functions',
      'description' => 'Test general API functions of the breakpoints module.',
      'group' => 'Breakpoints',
    );
  }

  /**
   * Test breakpoints sort functions
   */
  public function testSortWeight() {
    $breakpoints_objects = array();
    $breakpoints_arrays = array();
    $breakpoins_weights = array();
    for ($i = 5; $i >= 1; $i--) {
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
      $breakpoints_objects[$breakpoint->name] = $breakpoint;
      $breakpoints_arrays[$breakpoint->name] = (array)$breakpoint;
      $breakpoins_weights[$breakpoint->name] = $i;
    }

    // Sort the arrays.
    asort($breakpoins_weights);
    uasort($breakpoints_objects, '_breakpoints_sort_by_weight');
    uasort($breakpoints_arrays, '_breakpoints_sort_by_weight_array');

    // Verify if the arrays have the right order.
    $this->assertEqual(array_keys($breakpoins_weights), array_keys($breakpoints_objects), t('_breakpoints_sort_by_weight: Array of breakpoint objects has the right order after sorting.'), t('Breakpoints API'));
    $this->assertEqual(array_keys($breakpoins_weights), array_keys($breakpoints_arrays), t('_breakpoints_sort_by_weight_array: Array of breakpoint arrays has the right order after sorting.'), t('Breakpoints API'));
  }

  /**
   * Test breakpoints_breakpoint_config_name
   */
  public function testConfigName() {
    $breakpoint = new stdClass();
    $breakpoint->name = drupal_strtolower($this->randomName());
    $breakpoint->source = 'custom_module';

    // Try an invalid source_type.
    $breakpoint->source_type = $this->randomName();
    $exception = FALSE;
    $config_name = '';
    try {
      $config_name = breakpoints_breakpoint_config_name($breakpoint);
    }
    catch (Exception $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception, t('breakpoints_breakpoint_config_name: An exception is thrown when an invalid source_type is entered.'), t('Breakpoints API'));
    $this->assertEqual($config_name, '', t('breakpoints_breakpoint_config_name: No config name is returned when an invalid source_type is entered.'), t('Breakpoints API'));

    // Try an invalid source.
    $breakpoint->source_type = BREAKPOINTS_SOURCE_TYPE_CUSTOM;
    $breakpoint->source = 'custom*_module source';
    $exception = FALSE;
    $config_name = '';
    try {
      $config_name = breakpoints_breakpoint_config_name($breakpoint);
    }
    catch (Exception $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception, t('breakpoints_breakpoint_config_name: An exception is thrown when an invalid source is entered.'), t('Breakpoints API'));
    $this->assertEqual($config_name, '', t('breakpoints_breakpoint_config_name: No config name is returned when an invalid source is entered.'), t('Breakpoints API'));

    // Try an invalid name (make sure there is at least once capital letter).
    $breakpoint->source = 'custom_module';
    $breakpoint->name = drupal_ucfirst($this->randomName());
    $exception = FALSE;
    $config_name = '';
    try {
      $config_name = breakpoints_breakpoint_config_name($breakpoint);
    }
    catch (Exception $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception, t('breakpoints_breakpoint_config_name: An exception is thrown when an invalid name is entered.'), t('Breakpoints API'));
    $this->assertEqual($config_name, '', t('breakpoints_breakpoint_config_name: No config name is returned when an invalid name is entered.'), t('Breakpoints API'));

    // Try a valid breakpoint.
    $breakpoint->name = drupal_strtolower($this->randomName());
    $exception = FALSE;
    $config_name = '';
    try {
      $config_name = breakpoints_breakpoint_config_name($breakpoint);
    }
    catch (Exception $e) {
      $exception = TRUE;
    }
    $this->assertFalse($exception, t('breakpoints_breakpoint_config_name: No exception is thrown when a valid breakpoint is passed.'), t('Breakpoints API'));
    $this->assertEqual($config_name, 'breakpoints.' . BREAKPOINTS_SOURCE_TYPE_CUSTOM . '.custom_module.' . $breakpoint->name, t('breakpoints_breakpoint_config_name: A correct config name is returned when a valid breakpoint is passed.'), t('Breakpoints API'));
  }
}
