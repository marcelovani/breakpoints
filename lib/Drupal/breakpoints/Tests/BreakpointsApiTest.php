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

  /**
   * Drupal\simpletest\WebTestBase\getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'Breakpoints general API functions',
      'description' => 'Test general API functions of the breakpoints module.',
      'group' => 'Breakpoints',
    );
  }

  /**
   * Test Breakpoint::buildConfigName().
   */
  public function testConfigName() {
    $breakpoint = new Breakpoint(
      array(
        'label' => drupal_strtolower($this->randomName()),
        'source' => 'custom_module',
        // Try an invalid sourceType.
        'sourceType' => 'oops',
      )
    );

    try {
      $breakpoint->save();
    }
    catch (Exception $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception, t('breakpoints_breakpoint_config_name: An exception is thrown when an invalid sourceType is entered.'), t('Breakpoints API'));
    $this->assertEqual((string) $breakpoint->id(), '', t('breakpoints_breakpoint_config_name: No id is set when an invalid sourceType is entered.'), t('Breakpoints API'));

    // Try an invalid source.
    $breakpoint->sourceType = Breakpoint::SOURCE_TYPE_CUSTOM;
    $breakpoint->source = 'custom*_module source';
    $exception = FALSE;
    try {
      $breakpoint->save();
    }
    catch (Exception $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception, t('breakpoints_breakpoint_config_name: An exception is thrown when an invalid source is entered.'), t('Breakpoints API'));
    $this->assertEqual((string) $breakpoint->id(), '', t('breakpoints_breakpoint_config_name: No id is set when an invalid sourceType is entered.'), t('Breakpoints API'));

    // Try an invalid name (make sure there is at least once capital letter).
    $breakpoint->source = 'custom_module';
    $breakpoint->name = drupal_ucfirst($this->randomName());
    $exception = FALSE;
    try {
      $breakpoint->save();
    }
    catch (Exception $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception, t('breakpoints_breakpoint_config_name: An exception is thrown when an invalid name is entered.'), t('Breakpoints API'));
    $this->assertEqual((string) $breakpoint->id(), '', t('breakpoints_breakpoint_config_name: No id is set when an invalid sourceType is entered.'), t('Breakpoints API'));

    // Try a valid breakpoint.
    $breakpoint->name = drupal_strtolower($this->randomName());
    $breakpoint->mediaQuery = 'all';
    $exception = FALSE;
    try {
      $breakpoint->save();
    }
    catch (Exception $e) {
      $exception = TRUE;
    }
    $this->assertFalse($exception, t('breakpoints_breakpoint_config_name: No exception is thrown when a valid breakpoint is passed.'), t('Breakpoints API'));
    $this->assertEqual($breakpoint->id(), Breakpoint::SOURCE_TYPE_CUSTOM . '.custom_module.' . $breakpoint->name, t('breakpoints_breakpoint_config_name: A id is set when a valid breakpoint is passed.'), t('Breakpoints API'));
  }
}
