<?php

/**
 * @file
 * Definition of Drupal\breakpoint\Breakpoint.
 */

namespace Drupal\breakpoints;

use Drupal\config\ConfigEntityBase;

/**
 * Defines the Breakpoint entity.
 */
class Breakpoint extends ConfigEntityBase {

  /**
   * The breakpoint ID (machine name).
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
  public $media_query = '';

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
   */
  public $source_type = BREAKPOINTS_SOURCE_TYPE_CUSTOM;

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
   * The possible values for source type.
   *
   */
  const BREAKPOINTS_SOURCE_TYPE_THEME = 'theme';
  const BREAKPOINTS_SOURCE_TYPE_MODULE = 'module';
  const BREAKPOINTS_SOURCE_TYPE_CUSTOM = 'custom';

  public function save() {
    if (empty($this->id)) {
      $this->id = $this->build_config_name();
    }
    parent::save();
  }

  /**
   * Get config name.
   */
  public function get_config_name() {
    return $this->source_type
      . '.' . $this->source
      . '.' . $this->label;
  }

  /**
   * Construct config name.
   */
  private function build_config_name() {
    // Check for illegal values in breakpoint source type.
    if (!in_array($this->source_type, array(BREAKPOINTS_SOURCE_TYPE_CUSTOM, BREAKPOINTS_SOURCE_TYPE_MODULE, BREAKPOINTS_SOURCE_TYPE_THEME))) {
      throw new Exception(
          t(
            'Expected one of \'@custom\', \'@module\' or \'@theme\' for breakpoint source_type property but got \'@sourcetype\'.',
            array(
              '@custom' => \BREAKPOINTS_SOURCE_TYPE_CUSTOM,
              '@module' => \BREAKPOINTS_SOURCE_TYPE_MODULE,
              '@theme' => \BREAKPOINTS_SOURCE_TYPE_THEME,
              '@sourcetype' => $this->source_type,
            )
          )
      );
    }
    // Check for illegal characters in breakpoint source.
    if (preg_match('/[^a-z_]+/', $this->source)) {
      throw new Exception(t('Invalid value \'@source\' for breakpoint source property. Breakpoint source property can only contain lowercase letters and underscores.', array('@source' => $this->source)));
    }
    // Check for illegal characters in breakpoint names.
    if (preg_match('/[^0-9a-z_\-]/', $this->label)) {
      throw new Exception(t('Invalid value \'@label\' for breakpoint label property. Breakpoint label property can only contain lowercase alphanumeric characters, underscores (_), and hyphens (-).', array('@label' => $this->label)));
    }
    return $this->source_type
      . '.' . $this->source
      . '.' . $this->label;
  }
}
