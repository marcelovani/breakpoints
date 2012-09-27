<?php
/**
 * @file
 * Definition of Drupal\breakpoints\Tests\BreakpointsApiTest.
 */
namespace Drupal\breakpoints\Tests;

use Drupal\breakpoints\Tests\BreakpointsTestBase;
use Drupal\breakpoints\Breakpoint;
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
   * Test breakpoints_breakpoint_config_name
   */
  public function testConfigName() {
    $breakpoint = new Breakpoint(
      array(
        'label' => drupal_strtolower($this->randomName()),
        'source' => 'custom_module',
        // Try an invalid source_type.
        'source_type' => $this->randomName(),
      )
    );

    try {
      $breakpoint->save();
    }
    catch (Exception $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception, t('breakpoints_breakpoint_config_name: An exception is thrown when an invalid source_type is entered.'), t('Breakpoints API'));
    $this->assertEqual((string)$breakpoint->id(), '', t('breakpoints_breakpoint_config_name: No id is set when an invalid source_type is entered.'), t('Breakpoints API'));

    // Try an invalid source.
    $breakpoint->source_type = BREAKPOINTS_SOURCE_TYPE_CUSTOM;
    $breakpoint->source = 'custom*_module source';
    $exception = FALSE;
    try {
      $breakpoint->save();
    }
    catch (Exception $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception, t('breakpoints_breakpoint_config_name: An exception is thrown when an invalid source is entered.'), t('Breakpoints API'));
    $this->assertEqual((string)$breakpoint->id(), '', t('breakpoints_breakpoint_config_name: No id is set when an invalid source_type is entered.'), t('Breakpoints API'));

    // Try an invalid label (make sure there is at least once capital letter).
    $breakpoint->source = 'custom_module';
    $breakpoint->label = drupal_ucfirst($this->randomName());
    $exception = FALSE;
    try {
      $breakpoint->save();
    }
    catch (Exception $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception, t('breakpoints_breakpoint_config_name: An exception is thrown when an invalid name is entered.'), t('Breakpoints API'));
    $this->assertEqual((string)$breakpoint->id(), '', t('breakpoints_breakpoint_config_name: No id is set when an invalid source_type is entered.'), t('Breakpoints API'));

    // Try a valid breakpoint.
    $breakpoint->label = drupal_strtolower($this->randomName());
    $exception = FALSE;
    $save = FALSE;
    try {
      $save = $breakpoint->save();
    }
    catch (Exception $e) {
      $exception = TRUE;
    }
    $this->assertFalse($exception, t('breakpoints_breakpoint_config_name: No exception is thrown when a valid breakpoint is passed.'), t('Breakpoints API'));
    $this->assertEqual($breakpoint->id(), Breakpoint::BREAKPOINTS_SOURCE_TYPE_CUSTOM . '.custom_module.' . $breakpoint->label, t('breakpoints_breakpoint_config_name: A id is set when a valid breakpoint is passed.'), t('Breakpoints API'));
    $this->assertIdentical($save, SAVED_NEW, t('breakpoints_breakpoint_config_name: The correct value is returned when saving a new breakpoint'), t('Breakpoints API'));
  }
}
