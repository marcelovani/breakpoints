<?php

/**
 * @file
 * Definition of Drupal\breakpoint\BreakpointGroup.
 */

namespace Drupal\breakpoint;

use Drupal\Core\Config\Entity\ConfigEntityBase;

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
   * Override and save a breakpoint group.
   */
  public function override() {
    return entity_get_controller($this->entityType)->override($this);
  }

  /**
   * Revert a breakpoint group after it has been overridden.
   */
  public function revert() {
    return entity_get_controller($this->entityType)->revert($this);
  }

  /**
   * Implements EntityInterface::createDuplicate().
   */
  public function createDuplicate() {
    $duplicate = new BreakpointGroup();
    $duplicate->id = '';
    $duplicate->label = t('Clone of') . ' ' . $this->label();
    $duplicate->breakpoints = $this->breakpoints;
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
