<?php
/**
 * @file
 * Definition of Drupal\breakpoints\Tests\BreakpointsCrudTest.
 */
namespace Drupal\breakpoints\Tests;

use Drupal\simpletest\UnitTestBase;
use Drupal\breakpoints\Breakpoint;
use Exception;

/**
 * Tests for media queries in breakpoints.
 */
class BreakpointsMediaqueryTest extends UnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Breakpoints media query tests',
      'description' => 'Test validation of media queries.',
      'group' => 'Breakpoints',
    );
  }

  /**
   * Test valid media queries.
   */
  function testValidMediaQueries() {
    $media_queries = array(
      '(orientation)',
      'all and (orientation)',
      'not all and (orientation)',
      'only all and (orientation)',
      'screen and (width)',
      'screen and (width: 0)',
      'screen and (width: 0px)',
      'screen and (width: 0em)',
      'screen and (min-width: -0)',
      'screen and (max-width: 0)',
    );

    foreach($media_queries as $media_query) {
      try {
        $this->assertTrue(Breakpoint::isValidMediaQuery($media_query), $media_query . ' is valid.');
      }
      catch (Exception $e) {
        $this->assertTrue(FALSE, $media_query . ' is valid.');
      }
    }
  }

  /**
   * Test invalid media queries.
   */
  function testInvalidMediaQueries() {
    $media_queries = array(
      'not (orientation)',
      'only (orientation)',
      'screen and (min-width)',
      'all and not all',
    );

    foreach($media_queries as $media_query) {
      try {
        $this->assertFalse(Breakpoint::isValidMediaQuery($media_query), $media_query . ' is not valid.');
      }
      catch (Exception $e) {
        $this->assertTrue(TRUE, $media_query . ' is not valid.');
      }
    }
  }
}
