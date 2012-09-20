<?php

/**
 * @file
 * Definition of Drupal\breakpoint\BreakpointFormController.
 */

namespace Drupal\breakpoints;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFormController;

/**
 * Form controller for the breakpoint edit/add forms.
 */
class BreakpointFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state, EntityInterface $breakpoint) {
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#maxlength' => 255,
      '#default_value' => $breakpoint->label(),
      '#description' => t("Example: 'banner' or 'highlight'."),
      '#required' => TRUE,
      '#disabled' => !empty($breakpoint->id),
      '#element_validate' => array('breakpoint_name_validate'),
    );
    $form['media_query'] = array(
      '#type' => 'textfield',
      '#title' => t('Media query'),
      '#maxlength' => 255,
      '#default_value' => $breakpoint->media_query,
      '#description' => t("Media query without '@media'. Example: '(min-width: 320px)'."),
      '#required' => TRUE,
    );

    $settings = breakpoints_settings();
    $multipliers = array();
    if (isset($settings->multipliers) && !empty($settings->multipliers)) {
      $multipliers = drupal_map_assoc(array_values($settings->multipliers));
      if (array_key_exists('1x', $multipliers)) {
        unset($multipliers['1x']);
      }
    }

    $form['multipliers'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Multipliers'),
      '#default_value' => (isset($breakpoint->multipliers) && is_array($breakpoint->multipliers)) ? $breakpoint->multipliers : array(),
      '#options' => $multipliers,
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
    parent::validate($form, $form_state);

    $breakpoint = $this->getEntity($form_state);
    if (!isset($breakpoint->id)) {
      // Check for duplicates if user adds a new breakpoint.
      // Use $form_state['values']['label'] because $breakpoint->label is empty.
      $name = Breakpoint::BREAKPOINTS_SOURCE_TYPE_CUSTOM . '.user.' . $form_state['values']['label'];
      if (breakpoints_breakpoint_load($name)) {
        form_set_error('label', t('The breakpoint label %label is already in use.', array('%label' => $form_state['values']['label'])));
      }
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $breakpoint = $this->getEntity($form_state);
    $breakpoint->save();

    watchdog('breakpoint', 'Breakpoint @label saved.', array('@label' => $breakpoint->label()), WATCHDOG_NOTICE);
    drupal_set_message(t('Breakpoint %label saved.', array('%label' => $breakpoint->label())));

    $form_state['redirect'] = 'admin/config/media/breakpoints/breakpoint';
  }

}

