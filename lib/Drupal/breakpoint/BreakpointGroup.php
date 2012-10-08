<?php

/**
 * @file
 * Definition of Drupal\breakpoint\BreakpointGroup.
 */

namespace Drupal\breakpoint;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\breakpoint\InvalidBreakpointSourceException;
use Drupal\breakpoint\InvalidBreakpointSourceTypeException;

/**
 * Defines the BreakpointGroup entity.
 */
class BreakpointGroup extends ConfigEntityBase {

  /**
   * The BreakpointGroup ID (machine name).
   *
   * @var string
   */
  public $id;

  /**
   * The BreakpointGroup UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The BreakpointGroup label.
   *
   * @var string
   */
  public $label;

  /**
   * The BreakpointGroup breakpoints.
   *
   * @var array
   *   Array containing all breakpoints of this group.
   *
   * @see Drupal\breakpoints\Breakpoint
   */
  public $breakpoints = array();

  /**
   * The breakpoint source: theme or module name.
   *
   * @var string
   */
  public $source = '';

  /**
   * The BreakpointGroup source type.
   *
   * @var string
   *   Allowed values:
   *     Breakpoint::SOURCE_TYPE_THEME
   *     Breakpoint::SOURCE_TYPE_MODULE
   *     Breakpoint::SOURCE_TYPE_CUSTOM
   *
   * @see Drupal\breakpoint\Breakpoint
   */
  public $sourceType = Breakpoint::SOURCE_TYPE_CUSTOM;

  /**
   * The BreakpointGroup overridden status.
   *
   * @var boolean
   */
  public $overridden = FALSE;

  /**
   * Overrides Drupal\config\ConfigEntityBase::__construct().
   */
  public function __construct(array $values = array(), $entity_type = 'breakpoint_group') {
    parent::__construct($values, $entity_type);
    $this->loadAllBreakpoints();
  }

  /**
   * Overrides Drupal\Core\Entity::save().
   */
  public function save() {
    // Check if everything is valid.
    if (!$this->isValid()) {
      throw new Exception('Invalid data detected.');
    }
    // Only save the keys, but return the full objects.
    $this->breakpoints = array_keys($this->breakpoints);
    parent::save();
    $this->loadAllBreakpoints();
  }

  /**
   * Check if the breakpoint group is valid.
   */
  public function isValid() {
    // Check for illegal values in breakpoint group source type.
    if (!in_array($this->sourceType, array(
        Breakpoint::SOURCE_TYPE_CUSTOM,
        Breakpoint::SOURCE_TYPE_MODULE,
        Breakpoint::SOURCE_TYPE_THEME)
      )) {
      throw new InvalidBreakpointSourceTypeException(format_string('Invalid source type @source_type', array(
        '@source_type' => $this->sourceType,
      )));
    }
    // Check for illegal characters in breakpoint source.
    if (preg_match('/[^a-z_]+/', $this->source)) {
      throw new InvalidBreakpointSourceException(format_string("Invalid value '@source' for breakpoint source property. Breakpoint source property can only contain lowercase letters and underscores.", array('@source' => $this->source)));
    }
    return TRUE;
  }

  /**
   * Override a breakpoint group.
   */
  public function override() {
    // Custom breakpoint group can't be overridden.
    if ($this->sourceType === Breakpoint::SOURCE_TYPE_CUSTOM) {
      return FALSE;
    }

    // Mark all breakpoints as overridden.
    foreach ($this->breakpoints as $key => $breakpoint) {
      if ($breakpoint->sourceType === $this->sourceType && $breakpoint->source == $this->id()) {
        $breakpoint->override();
      }
    }

    // Mark breakpoint group as overridden.
    $this->overridden = TRUE;
    $this->save();
    return $this;
  }

  /**
   * Revert a breakpoint group after it has been overridden.
   */
  public function revert() {
    if (!$this->overridden || $this->sourceType === Breakpoint::SOURCE_TYPE_CUSTOM) {
      return FALSE;
    }

    // Reload all breakpoints from theme.
    $reloaded_set = breakpoint_group_reload_from_theme($this->id());
    if ($reloaded_set) {
      $this->breakpoints = $reloaded_set->breakpoints;
      $this->overridden = FALSE;
      $this->save();
    }
    return $this;
  }

  /**
   * Duplicate a breakpoint group.
   *
   * The new breakpoint group inherits the breakpoints.
   *
   */
  public function duplicate() {
    return entity_create('breakpoint_group', array(
      'breakpoints' => $this->breakpoints,
    ));
  }

  /**
   * Is the breakpoint group editable.
   */
  public function isEditable() {
    // Custom breakpoint groups are always editable.
    if ($this->sourceType == Breakpoint::SOURCE_TYPE_CUSTOM) {
      return TRUE;
    }
    // Overridden breakpoints groups are editable.
    if ($this->overridden) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Add a breakpoint using a name and a media query.
   *
   * @param string $name
   *   The name of the breakpoint.
   * @param string $media_query
   *   Media query.
   */
  public function addBreakpointFromMediaQuery($name, $media_query) {
    // Use the existing breakpoint if it exists.
    $breakpoint = entity_load('breakpoint', $this->sourceType . '.' . $this->id . '.' . $name);
    if (!$breakpoint) {
      // Build a new breakpoint.
      $breakpoint = entity_create('breakpoint', array(
        'name' => $name,
        'label' => drupal_ucfirst($name),
        'mediaQuery' => $media_query,
        'source' => $this->id,
        'sourceType' => $this->sourceType,
        'weight' => count($this->breakpoints),
      ));
      $breakpoint->save();
    }
    else {
      // Reset name, label, weight and media query.
      $breakpoint->name = $name;
      $breakpoint->label = drupal_ucfirst($name);
      $breakpoint->mediaQuery = $media_query;
      $breakpoint->weight = count($this->breakpoints);
    }
    $this->breakpoints[$breakpoint->id()] = $breakpoint;
  }

  /**
   * Load breakpoints from a theme/module and build a default group.
   *
   * @param string $id
   *   Name of the breakpoint group.
   * @param string $label
   *   Human readable name of the breakpoint group.
   * @param string $sourceType
   *   Either Breakpoint::SOURCE_TYPE_THEME or Breakpoint::SOURCE_TYPE_MODULE.
   * @param array $media_queries
   *   Array of media queries keyed by id.
   *
   * @return \Drupal\breakpoint\BreakpointGroup|false
   *   Return the new breakpoint group containing all breakpoints.
   */
  public static function ImportMediaQueries($id, $label, $source_type, $media_queries) {
    $breakpoint_group = entity_load('breakpoint_group', $source_type . '.' . $id);
    /* @var $breakpoint_group \Drupal\breakpoint\BreakpointGroup */
    if (!$breakpoint_group) {
      // Build a new breakpoint group.
      $breakpoint_group = entity_create('breakpoint_group', array(
        'id' => $id,
        'label' => $label,
        'source' => $id,
        'sourceType' => $source_type,
      ));
    }
    else {
      // Reset label.
      $breakpoint_group->label = $label;
    }

    foreach ($media_queries as $name => $media_query) {
      $breakpoint_group->addBreakpointFromMediaQuery($name, $media_query);
    }
    return $breakpoint_group;
  }

  /**
   * Import breakpoint groups from theme or module.
   *
   * @param string $source
   *   Source of the breakpoint group, theme_key or module name.
   * @param string $sourceType
   *   Either Breakpoint::SOURCE_TYPE_THEME or Breakpoint::SOURCE_TYPE_MODULE.
   * @param string $name
   *   Name of the breakpoint group.
   * @param string $label
   *   Human readable name of the breakpoint group.
   * @param array $breakpoints
   *   Array of breakpoints, using either the short name or the full name.
   *
   * @return \Drupal\breakpoint\BreakpointGroup|false
   *   Return the new breakpoint group containing all breakpoints.
   */
  public static function ImportBreakpointGroup($source, $source_type, $name, $label, $breakpoints) {
    // Use the existing breakpoint group if it exists.
    $breakpoint_group = entity_load('breakpoint_group', $source_type . '.' . $name);
    /* @var $breakpoint_group \Drupal\breakpoint\BreakpointGroup */
    if (!$breakpoint_group) {
      $breakpoint_group = entity_create('breakpoint_group', array(
        'id' => $name,
        'label' => !empty($label) ? $label : $name,
        'source' => $source,
        'sourceType' => $source_type,
      ));
    }
    else {
      // Reset label.
      $breakpoint_group->label = !empty($label) ? $label : $name;
    }

    // Add breakpoints to the group.
    foreach ($breakpoints as $breakpoint_name) {
      // Check if breakpoint exists, assume short name.
      $breakpoint = entity_load('breakpoint', $source_type . '.' . $source . '.' . $breakpoint_name);
      // If the breakpoint doesn't exist, try using the full name.
      if (!$breakpoint) {
        $breakpoint = entity_load('breakpoint', $breakpoint_name);
      }
      if ($breakpoint) {
        // Add breakpoint to group.
        $breakpoint_group->breakpoints[$breakpoint->id()] = $breakpoint;
      }
    }
    return $breakpoint_group;
  }

  /**
   * Load all breakpoints, remove non-existing ones.
   */
  protected function loadAllBreakpoints() {
    $breakpoints = $this->breakpoints;
    $this->breakpoints = array();
    foreach ($breakpoints as $breakpoint_id) {
      $breakpoint = breakpoint_load($breakpoint_id);
      if ($breakpoint) {
        $this->breakpoints[$breakpoint_id] = $breakpoint;
      }
    }
  }
}
