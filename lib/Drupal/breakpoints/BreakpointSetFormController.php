<?php

/**
 * @file
 * Definition of Drupal\breakpoint\BreakpointFormController.
 */

namespace Drupal\breakpoints;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFormController;

/**
 * Form controller for the breakpoint set edit/add forms.
 */
class BreakpointSetFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state, EntityInterface $breakpointset) {
    if ($this->operation == 'duplicate') {
      $cloned_breakpointset = entity_create('breakpoints_breakpointset', array());
      $cloned_breakpointset->id = '';
      $cloned_breakpointset->label = t('Clone of ') . ' ' . $breakpointset->label();
      $cloned_breakpointset->breakpoints = $breakpointset->breakpoints;
      $breakpointset = $cloned_breakpointset;
      $this->setEntity($breakpointset, $form_state);
    }
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#maxlength' => 255,
      '#default_value' => $breakpointset->label(),
      '#description' => t("Example: 'Omega' or 'Custom'."),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $breakpointset->id(),
      '#machine_name' => array(
        'exists' => 'breakpoints_breakpoint_load',
        'source' => array('label'),
      ),
      '#disabled' => (bool)$breakpointset->id() && $this->operation != 'duplicate',
    );
    return parent::form($form, $form_state, $breakpointset);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   */
  protected function actions(array $form, array &$form_state) {
    // Only includes a Save action for the entity, no direct Delete button.
    return array(
      'submit' => array(
        '#value' => t('Save'),
        '#validate' => array(
          array($this, 'validate'),
        ),
        '#submit' => array(
          array($this, 'submit'),
          array($this, 'save'),
        ),
      ),
    );
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
  }

  
  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $breakpointset = $this->getEntity($form_state);

    $breakpointset->save();

    watchdog('breakpoint', 'Breakpoint set @label saved.', array('@label' => $breakpointset->label()), WATCHDOG_NOTICE);
    drupal_set_message(t('Breakpoint set %label saved.', array('%label' => $breakpointset->label())));

    $form_state['redirect'] = 'admin/config/media/breakpoints/breakpointset';
  }

}

