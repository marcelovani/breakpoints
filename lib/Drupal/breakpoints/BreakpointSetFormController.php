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
    dpm($breakpointset);
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#maxlength' => 255,
      '#default_value' => $breakpointset->label(),
      '#description' => t("Example: 'Banner' or 'Highlight'."),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $breakpointset->id(),
      '#machine_name' => array(
        'exists' => 'breakpoints_breakpoint_load',
        'source' => array('label'),
      ),
      '#disabled' => (bool) $breakpointset->id(),
    );

    return parent::form($form, $form_state, $breakpoint);
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
  public function save(array $form, array &$form_state) {
    $breakpointset = $this->getEntity($form_state);
    dpm($breakpointset);
    $breakpointset->save();

    watchdog('breakpoint', 'Breakpoint set @label saved.', array('@label' => $breakpoint->label()), WATCHDOG_NOTICE);
    drupal_set_message(t('Breakpoint set %label saved.', array('%label' => $breakpoint->label())));

    $form_state['redirect'] = 'admin/config/media/breakpoints/breakpointset';
  }

}

