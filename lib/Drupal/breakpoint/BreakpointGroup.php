<?php

/**
 * @file
 * Definition of Drupal\breakpoint\BreakpointGroup.
 */

namespace Drupal\breakpoint;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Component\Uuid\Uuid;

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
   * The breakpoint source: theme or module name.
   *
   * @var string
   */
  public $source = '';

  /**
   * The BreakpointGroup overridden status.
   *
   * @var string
   */
  public $overridden = FALSE;

  /**
   * Overrides Drupal\config\ConfigEntityBase::__construct().
   */
  public function __construct(array $values = array(), $entity_type = 'breakpoint_group') {
    parent::__construct($values, $entity_type);
    // Assign a new UUID if there is none yet.
    if (!isset($this->uuid)) {
      $uuid = new Uuid();
      $this->uuid = $uuid->generate();
    }
    $this->loadAllBreakpoints();
  }

  /**
   * Overrides Drupal\Core\Entity::save().
   */
  public function save() {
    // Only save the keys, but return the full objects.
    $this->breakpoints = array_keys($this->breakpoints);
    parent::save();
    $this->loadAllBreakpoints();
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
   * Implements EntityInterface::createDuplicate().
   */
  public function createDuplicate() {
    $duplicate = clone $this;
    $entity_info = $this->entityInfo();
    $duplicate->{$entity_info['entity keys']['id']} = NULL;

    // Check if the entity type supports UUIDs and generate a new one if so.
    if (!empty($entity_info['entity keys']['uuid'])) {
      $uuid = new Uuid();
      $duplicate->{$entity_info['entity keys']['uuid']} = $uuid->generate();
    }
    $duplicate->label = t('Clone of') . ' ' . $this->label();
    $duplicate->isNew = TRUE;
    $duplicate->originalID = NULL;
    $duplicate->sourceType = Breakpoint::SOURCE_TYPE_CUSTOM;
    $duplicate->overridden = FALSE;
    return $duplicate;
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
