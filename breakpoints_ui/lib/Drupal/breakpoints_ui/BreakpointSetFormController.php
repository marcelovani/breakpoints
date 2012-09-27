<?php

/**
 * @file
 * Definition of Drupal\breakpoints_ui\BreakpointFormController.
 */

namespace Drupal\breakpoints_ui;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFormController;
use Drupal\breakpoints\Breakpoint;

/**
 * Form controller for the breakpoint set edit/add forms.
 */
class BreakpointSetFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state, EntityInterface $breakpointset) {
    if ($this->operation == 'duplicate') {
      $breakpointset = $breakpointset->createDuplicate();
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

    switch($breakpointset->source_type) {
      case Breakpoint::BREAKPOINTS_SOURCE_TYPE_MODULE:
      case Breakpoint::BREAKPOINTS_SOURCE_TYPE_CUSTOM:
        // Show all breakpoints part of this set.
        $breakpoints = array();
        foreach(breakpoints_breakpoint_load_all() as $breakpoint) {
          $breakpoints[$breakpoint->id] = $breakpoint->label . ' (' . $breakpoint->source . ' - ' . $breakpoint->source_type .   ') [' . $breakpoint->media_query . ']';
        }
        $options = array_intersect_key($breakpoints, $breakpointset->breakpoints);
        // @todo allow people to change the order
        $form['breakpoints_ajax'] = array(
          '#type' => 'container',
          '#attributes' => array(
            'id' => "breakpoints-checkboxes-ajax-wrapper",
          ),
        );
        $form['breakpoints_ajax']['breakpoints'] = array(
          '#type' => 'checkboxes',
          '#title' => t('Breakpoints'),
          '#description' => t('Select the breakpoints that are part of this set.'),
          '#tree' => TRUE,
          '#options' => array_intersect_key($breakpoints, $breakpointset->breakpoints),
          '#default_value' => $breakpointset->breakpoints,
        );
        $options = array_diff_key($breakpoints, $breakpointset->breakpoints);
        if (!empty($options)) {
          $form['breakpoints_ajax']['add_breakpoint_action'] = array(
            '#type' => 'actions',
          );
          $form['breakpoints_ajax']['add_breakpoint_action']['breakpoint'] = array(
            '#type' => 'select',
            '#title' => t('Add breakpoint'),
            '#description' => t('Add a breakpoint to this set'),
            '#options' => $options,
          );
          $form['breakpoints_ajax']['add_breakpoint_action']['add_breakpoint'] = array(
            '#type' => 'submit',
            '#value' => t('Add breakpoint'),
            '#submit' => array(
              array($this, 'addBreakpointSubmit'),
            ),
            '#ajax' => array(
              'callback' => 'ajax_add_breakpoint_submit',
              'wrapper' => 'breakpoints-checkboxes-ajax-wrapper',
            ),
          );
          $form['add_breakpoint_action']['#attached']['css'][] = drupal_get_path('module', 'breakpoints_ui') . '/css/breakpoints_ui.breakpointset.admin.css';
        }
        break;
      case Breakpoint::BREAKPOINTS_SOURCE_TYPE_THEME:
        // Show all breakpoints part of this set.
        $breakpoints = array();
        foreach($breakpointset->breakpoints as $breakpoint_id) {
          $breakpoint = breakpoints_breakpoint_load($breakpoint_id);
          $breakpoints[$breakpoint->id] = $breakpoint->label . ' [' . $breakpoint->media_query . ']';
        }

        $form['breakpoints'] = array(
          '#type' => 'checkboxes',
          '#title' => t('Breakpoints'),
          '#description' => t('The following breakpoints are part of this set.'),
          '#tree' => TRUE,
          '#options' => $breakpoints,
          '#default_value' => $breakpointset->breakpoints,
          '#disabled' => TRUE,
        );
        break;
    }

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
  public function save(array $form, array &$form_state) {
    $breakpointset = $this->getEntity($form_state);
    $breakpointset->save();

    watchdog('breakpoint', 'Breakpoint set @label saved.', array('@label' => $breakpointset->label()), WATCHDOG_NOTICE);
    drupal_set_message(t('Breakpoint set %label saved.', array('%label' => $breakpointset->label())));

    $form_state['redirect'] = 'admin/config/media/breakpoints/breakpointset';
  }

  /**
   * Submit callback to add a new breakpoint to a breakpoint set.
   * @see BreakpointSetFormController::form()
   */
  public function addBreakpointSubmit(array $form, array $form_state) {
    $entity = $this->getEntity($form_state);
    $entity->breakpoints[$form_state['values']['breakpoint']] = 1;
    $this->setEntity($entity, $form_state);
    $form_state['rebuild'] = TRUE;
  }

}

