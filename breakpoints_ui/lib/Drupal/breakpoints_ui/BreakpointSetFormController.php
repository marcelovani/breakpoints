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
    // Check if we need to duplicate the breakpoint set.
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
        'exists' => 'breakpoints_breakpointset_load',
        'source' => array('label'),
      ),
      '#disabled' => (bool)$breakpointset->id() && $this->operation != 'duplicate',
    );

    $form['#tree'] = TRUE;

    // Load all available multipliers.
    $settings = breakpoints_settings();
    $multipliers = array();
    if (isset($settings->multipliers) && !empty($settings->multipliers)) {
      $multipliers = drupal_map_assoc(array_values($settings->multipliers));
      if (array_key_exists('1x', $multipliers)) {
        unset($multipliers['1x']);
      }
    }

    // Breakpointsets efined by themes cannot be altered.
    $read_only = $breakpointset->source_type === Breakpoint::BREAKPOINTS_SOURCE_TYPE_THEME;

    // Weight for the order of the breakpoints.
    $weight = 0;

    $form['breakpoints_fieldset'] = array(
      '#type' => 'fieldset',
      '#title' => t('Breakpoints'),
      '#collapsible' => TRUE,
      '#attributes' => array(
        'id' => 'breakpointset-fieldset',
      ),
    );

    // Build table of breakpoints.
    $form['breakpoints_fieldset']['breakpoints'] = array(
      '#theme' => 'table',
      '#attributes' => array(
        'id' => 'breakpointset-breakpoints-table',
      ),
      '#empty' => t('No breakpoints added.'),
      '#pre_render' => array(
        'breakpoints_ui_add_breakpoints_table_prerender'
      ),
    );

    foreach ($breakpointset->breakpoints as $key => $breakpoint) {
      $form['breakpoints_fieldset']['breakpoints']['#rows'][$key] = array(
        'class' => array('draggable'),
        'data' => array(
          'label' => '',
          'media_query' => '',
          'multipliers' => '',
          'weight' => '',
          'remove' => '',
        ),
      );
      $form['breakpoints_fieldset']['breakpoints'][$key]['label'] = array(
        '#type' => 'textfield',
        '#default_value' => $breakpoint->label(),
        '#parents' => array('breakpoints', $key, 'label'),
        '#maxlength' => 255,
        '#size' => 20,
        '#required' => TRUE,
      );
      $form['breakpoints_fieldset']['breakpoints'][$key]['media_query'] = array(
        '#type' => 'textfield',
        '#default_value' => $breakpoint->media_query,
        '#maxlength' => 255,
        '#parents' => array('breakpoints', $key, 'media_query'),
        '#required' => TRUE,
        '#size' => 60,
        '#disabled' => $read_only,
      );
      $form['breakpoints_fieldset']['breakpoints'][$key]['multipliers'] = array(
        '#type' => 'checkboxes',
        '#default_value' => (isset($breakpoint->multipliers) && is_array($breakpoint->multipliers)) ? $breakpoint->multipliers : array(),
        '#options' => $multipliers,
        '#parents' => array('breakpoints', $key, 'multipliers'),
      );
      if (!$read_only) {
        $form['breakpoints_fieldset']['breakpoints'][$key]['remove'] = array(
          '#type' => 'submit',
          '#value' => t('Remove'),
          '#name' => 'breakpoints_remove_' . $weight,
          '#submit' => array(
            array($this, 'removeBreakpointSubmit'),
          ),
          '#breakpoint' => $key,
          '#ajax' => array(
            'callback' => 'ajax_add_breakpoint_submit',
            'wrapper' => 'breakpointset-fieldset',
          ),
        );
      }
      $form['breakpoints_fieldset']['breakpoints'][$key]['weight'] = array(
        '#type' => 'select',
        '#title' => t('Weight'),
        '#description' => t('Select the weight of this breakpoint in this set.'),
        '#options' => range(0, count($breakpointset->breakpoints)),
        '#attributes' => array('class' => array('weight')),
        '#parents' => array('breakpoints', $key, 'weight'),
        '#default_value' => $weight++,
      );
    }
    $form['breakpoints_fieldset']['breakpoints']['#header'] = array(
      'label' => t('Label'),
      'media_query' => t('Media query'),
      'multipliers' => t('Multipliers'),
      'weight' => t('Weight'),
      'remove' => t('Remove'),
    );
    if ($read_only) {
      unset($form['breakpoints_fieldset']['breakpoints']['#header']['remove']);
    }
    drupal_add_tabledrag('breakpointset-breakpoints-table', 'order', 'siblig', 'weight');

    if (!$read_only) {
      $options = array_diff_key(breakpoints_ui_breakpoints_options(), $breakpointset->breakpoints);

      if (!empty($options)) {
        $form['breakpoints_fieldset']['add_breakpoint_action'] = array(
          '#type' => 'actions',
          '#suffix' => '</div>',
        );
        $form['breakpoints_fieldset']['add_breakpoint_action']['breakpoint'] = array(
          '#type' => 'select',
          '#title' => t('Add existing breakpoint'),
          '#description' => t('Add an existing breakpoint to this set'),
          '#options' => $options,
          '#parents' => array('breakpoint'),
        );
        $form['breakpoints_fieldset']['add_breakpoint_action']['add_breakpoint'] = array(
          '#type' => 'submit',
          '#value' => t('Add breakpoint'),
          '#submit' => array(
            array($this, 'addBreakpointSubmit'),
          ),
          '#ajax' => array(
            'callback' => 'ajax_add_breakpoint_submit',
            'wrapper' => 'breakpointset-fieldset',
          ),
        );
        $form['breakpoints_fieldset']['add_breakpoint_action']['#attached']['css'][] = drupal_get_path('module', 'breakpoints_ui') . '/css/breakpoints_ui.breakpointset.admin.css';
      }
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
    $breakpoints = $form_state['values']['breakpoints'];
    $this->_sort_breakpoints($breakpointset, $breakpoints);
    foreach ($breakpointset->breakpoints as $breakpoint_id => $breakpoint) {
      // Config will recognize this is an existing breakpoint by its id.
      $breakpointobject = breakpoints_breakpoint_load($breakpoint_id);
      foreach ($breakpoint as $property => $value) {
        $breakpointobject->{$property} = $value;
      }
      $breakpointobject->save();
    }
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
    // Get the order from the current form_state.
    $breakpoints = $form_state['values']['breakpoints'];
    $this->_sort_breakpoints($entity, $breakpoints);
    // Add the new breakpoint at the end.
    $breakpoint = $form_state['values']['breakpoint'];
    $entity->breakpoints += array($breakpoint => breakpoints_breakpoint_load($breakpoint));
    $form_state['rebuild'] = TRUE;
  }

  /**
   * Submit callback to add a new breakpoint to a breakpoint set.
   * @see BreakpointSetFormController::form()
   */
  public function removeBreakpointSubmit(array $form, array $form_state) {
    $entity = $this->getEntity($form_state);
    // Get the order from the current form_state.
    $breakpoints = $form_state['values']['breakpoints'];
    $this->_sort_breakpoints($entity, $breakpoints);
    unset($entity->breakpoints[$form_state['triggering_element']['#breakpoint']]);
    $form_state['rebuild'] = TRUE;
  }

  private function _sort_breakpoints(&$entity, $breakpoints) {
    // Sort the breakpoints in the right order.
    uasort($breakpoints, 'drupal_sort_weight');
    $breakpoints_order = array_keys($breakpoints);
    $entity_breakpoints = $entity->breakpoints;
    $entity->breakpoints = array();
    foreach ($breakpoints_order as $breakpoint_id) {
      $entity->breakpoints[$breakpoint_id] = $entity_breakpoints[$breakpoint_id];
    }
    // make sure we don't lose any data
    $entity->breakpoints += $entity_breakpoints;
  }

}

