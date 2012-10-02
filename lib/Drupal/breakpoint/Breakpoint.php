<?php

/**
 * @file
 * Definition of Drupal\breakpoint\Breakpoint.
 */

namespace Drupal\breakpoint;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Exception;

/**
 * Defines the Breakpoint entity.
 */
class Breakpoint extends ConfigEntityBase {

  /**
   * The possible values for sourceType.
   */
  const SOURCE_TYPE_THEME = 'theme';
  const SOURCE_TYPE_MODULE = 'module';
  const SOURCE_TYPE_CUSTOM = 'custom';

  /**
   * The breakpoint ID (config name).
   *
   * @var string
   */
  public $id;

  /**
   * The breakpoint UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The breakpoint name (machine name).
   *
   * @var string
   */
  public $name;

  /**
   * The breakpoint label.
   *
   * @var string
   */
  public $label;

  /**
   * The breakpoint media query.
   *
   * @var string
   */
  public $mediaQuery = '';

  /**
   * The breakpoint source.
   *
   * @var string
   */
  public $source = 'user';

  /**
   * The breakpoint source type.
   *
   * @var string
   *   Allowed values:
   *     Breakpoint::SOURCE_TYPE_THEME
   *     Breakpoint::SOURCE_TYPE_MODULE
   *     Breakpoint::SOURCE_TYPE_CUSTOM
   */
  public $sourceType = Breakpoint::SOURCE_TYPE_CUSTOM;

  /**
   * The breakpoint status.
   *
   * @var string
   */
  public $status = TRUE;

  /**
   * The breakpoint weight.
   *
   * @var weight
   */
  public $weight = 0;

  /**
   * The breakpoint multipliers.
   *
   * @var multipliers
   */
  public $multipliers = array();

  /**
   * Overrides Drupal\config\ConfigEntityBase::__construct().
   */
  public function __construct(array $values = array(), $entity_type = 'breakpoint_breakpoint') {
    parent::__construct($values, $entity_type);
  }

  /**
   * Overrides Drupal\config\ConfigEntityBase::save().
   */
  public function save() {
    if (empty($this->id)) {
      $this->id = $this->buildConfigName();
    }
    if (empty($this->label)) {
      $this->label = ucfirst($this->name);
    }
    if (!$this->isValid()) {
      throw new Exception(t('Invalid media query detected.'));
    }
    return parent::save();
  }

  /**
   * Get config name.
   */
  public function getConfigName() {
    return $this->sourceType
      . '.' . $this->source
      . '.' . $this->name;
  }

  /**
   * Build config name.
   */
  protected function buildConfigName() {
    // Check for illegal values in breakpoint source type.
    if (!in_array($this->sourceType, array(
        Breakpoint::SOURCE_TYPE_CUSTOM,
        Breakpoint::SOURCE_TYPE_MODULE,
        Breakpoint::SOURCE_TYPE_THEME)
      )) {
      throw new Exception(
          t(
            "Expected one of '@custom', '@module' or '@theme' for breakpoint sourceType property but got '@sourcetype'.",
            array(
              '@custom' => Breakpoint::SOURCE_TYPE_CUSTOM,
              '@module' => Breakpoint::SOURCE_TYPE_MODULE,
              '@theme' => Breakpoint::SOURCE_TYPE_THEME,
              '@sourcetype' => $this->sourceType,
            )
          )
      );
    }
    // Check for illegal characters in breakpoint source.
    if (preg_match('/[^a-z_]+/', $this->source)) {
      throw new Exception(t("Invalid value '@source' for breakpoint source property. Breakpoint source property can only contain lowercase letters and underscores.", array('@source' => $this->source)));
    }
    // Check for illegal characters in breakpoint names.
    if (preg_match('/[^0-9a-z_\-]/', $this->name)) {
      throw new Exception(t("Invalid value '@name' for breakpoint name property. Breakpoint name property can only contain lowercase alphanumeric characters, underscores (_), and hyphens (-).", array('@name' => $this->name)));
    }
    return $this->sourceType
      . '.' . $this->source
      . '.' . $this->name;
  }

  /**
   * Shortcut function to enable a breakpoint and save it.
   *
   * @see breakpoint_breakpoint_action_confirm_submit()
   */
  public function enable() {
    if (!$this->status) {
      $this->status = 1;
      $this->save();
    }
  }

  /**
   * Shortcut function to disable a breakpoint and save it.
   *
   * @see breakpoint_breakpoint_action_confirm_submit()
   */
  public function disable() {
    if ($this->status) {
      $this->status = 0;
      $this->save();
    }
  }

  /**
   * Check if the mediaQuery is valid.
   *
   * @see isValidMediaQuery()
   */
  public function isValid() {
    return $this::isValidMediaQuery($this->mediaQuery);
  }

  /**
   * Check if a mediaQuery is valid.
   *
   * @see http://www.w3.org/TR/css3-mediaqueries/
   * @see http://www.w3.org/Style/CSS/Test/MediaQueries/20120229/reports/implement-report.html
   */
  public static function isValidMediaQuery($media_query) {
    $media_features = array(
      'width' => 'length', 'min-width' => 'length', 'max-width' => 'length',
      'height' => 'length', 'min-height' => 'length', 'max-height' => 'length',
      'device-width' => 'length', 'min-device-width' => 'length', 'max-device-width' => 'length',
      'device-height' => 'length', 'min-device-height' => 'length', 'max-device-height' => 'length',
      'orientation' => array('portrait', 'landscape'),
      'aspect-ratio' => 'ratio', 'min-aspect-ratio' => 'ratio', 'max-aspect-ratio' => 'ratio',
      'device-aspect-ratio' => 'ratio', 'min-device-aspect-ratio' => 'ratio', 'max-device-aspect-ratio' => 'ratio',
      'color' => 'integer', 'min-color' => 'integer', 'max-color' => 'integer',
      'color-index' => 'integer', 'min-color-index' => 'integer', 'max-color-index' => 'integer',
      'monochrome' => 'integer', 'min-monochrome' => 'integer', 'max-monochrome' => 'integer',
      'resolution' => 'resolution', 'min-resolution' => 'resolution', 'max-resolution' => 'resolution',
      'scan' => array('progressive', 'interlace'),
      'grid' => 'integer',
    );
    if ($media_query) {
      // Strip new lines and trim.
      $media_query = str_replace(array("\r", "\n"), ' ', trim($media_query));

      // Remove comments /* ... */.
      $media_query = preg_replace('/\/\*[\s\S]*?\*\//', '', $media_query);

      // Check mediaQuery_list: S* [mediaQuery [ ',' S* mediaQuery ]* ]?
      $parts = explode(',', $media_query);
      foreach ($parts as $part) {
        // Split on ' and '
        $query_parts = explode(' and ', trim($part));
        $media_type_found = FALSE;
        foreach ($query_parts as $query_part) {
          $matches = array();
          // Check expression: '(' S* media_feature S* [ ':' S* expr ]? ')' S*
          if (preg_match('/^\(([\w\-]+)(:\s?([\w\-]+))?\)/', trim($query_part), $matches)) {
            // Single expression.
            if (isset($matches[1]) && !isset($matches[2])) {
              if (!array_key_exists($matches[1], $media_features)) {
                return FALSE;
              }
            }
            // Full expression.
            elseif (isset($matches[3]) && !isset($matches[4])) {
              $value = trim($matches[3]);
              if (!array_key_exists($matches[1], $media_features)) {
                return FALSE;
              }
              if (is_array($media_features[$matches[1]])) {
                // Check if value is allowed.
                if (!array_key_exists($value, $media_features[$matches[1]])) {
                  return FALSE;
                }
              }
              else {
                switch ($media_features[$matches[1]]) {
                  case 'length':
                    $length_matches = array();
                    if (preg_match('/^(\-)?(\d+)?((?:|em|ex|px|cm|mm|in|pt|pc|deg|rad|grad|ms|s|hz|khz|dpi|dpcm))$/i', trim($value), $length_matches)) {
                      // Only -0 is allowed.
                      if ($length_matches[1] === '-' && $length_matches[2] !== '0') {
                        return FALSE;
                      }
                      // If there's a unit, a number is needed as well.
                      if ($length_matches[2] === '' && $length_matches[3] !== '') {
                        return FALSE;
                      }
                    }
                    else {
                      return FALSE;
                    }
                    break;
                }
              }
            }
          }
          // Check [ONLY | NOT]? S* media_type
          elseif (preg_match('/((?:only|not)?\s?)([\w\-]+)$/i', trim($query_part), $matches)) {
            if ($media_type_found) {
              throw new Exception(t('Only when media type allowed.'));
            }
            $media_type_found = TRUE;
          }
          else {
            throw new Exception(t("Invalid value '@query_part' for breakpoint media query property.", array('@query_part' => $query_part)));
          }
        }
      }
      return TRUE;
    }
    return FALSE;
  }
}
