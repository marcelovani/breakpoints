<?php

use \Drupal\breakpoints\Breakpoint;
use \Drupal\breakpoints\BreakpointSet;

/**
 * @file
 * Administration functions to maintain a common set of breakpointsets for layouts.
 */

/**
 * Page callback: Presents the breakpointset list page.
 *
 * @see breakpoints_ui_menu()
 */
function breakpoints_ui_breakpointset_page() {
  $controller = entity_list_controller('breakpoints_breakpointset');
  return $controller->render();
}

/**
 * Page callback: Presents the breakpointset editing form.
 *
 * @see breakpoints_ui_menu()
 */
function breakpoints_ui_breakpointset_page_edit($breakpointset) {
  drupal_set_title(t('<em>Edit breakpoint set</em> @label', array('@label' => $breakpointset->label())), PASS_THROUGH);
  return entity_get_form($breakpointset);
}

/**
 * Page callback: Provides the new breakpointset addition form.
 *
 * @see breakpoints_ui_menu()
 */
function breakpoints_ui_breakpointset_page_add() {
  $breakpointset = entity_create('breakpoints_breakpointset', array());
  $form = entity_get_form($breakpointset);
  return $form;
}

/**
 * Page callback: Provides the duplicate breakpointset form.
 *
 * @see breakpoints_ui_menu()
 */
function breakpoints_ui_breakpoint_page_duplicate($breakpointset) {
  drupal_set_title(t('<em>Duplicate breakpoint</em> @label', array('@label' => $breakpointset->label())), PASS_THROUGH);
  return entity_get_form($breakpointset, 'duplicate');
}

/**
 * Page callback: Form constructor for breakpointset action confirmation form.
 *
 * @see breakpoints_ui_menu()
 */
function breakpoints_ui_breakpointset_action_confirm($form, &$form_state, $breakpointset, $action) {
  // Always provide entity id in the same form key as in the entity edit form.
  if (in_array($action, array('delete', 'override', 'revert'))) {
    $form['id'] = array('#type' => 'value', '#value' => $breakpointset->id());
    $form['action'] = array('#type' => 'value', '#value' => $action);
    $form_state['breakpointset'] = $breakpointset;
    $form = confirm_form($form,
      t('Are you sure you want to @action the breakpointset %title?', array('@action' => $action, '%title' => $breakpointset->label())),
      'admin/config/media/breakpoints/breakpointset',
      $action == 'delete' ? t('This action cannot be undone.') : '',
      t(drupal_ucfirst($action)),
      t('Cancel')
    );
  }
  return $form;
}

/**
 * Form submission handler for breakpointset_action_confirm().
 */
function breakpoints_ui_breakpointset_action_confirm_submit($form, &$form_state) {
  $breakpointset = $form_state['breakpointset'];
  $action = $form_state['values']['action'];
  $breakpointset->{$action}();
  $verb = '';
  switch ($action) {
    case 'delete':
      $verb = 'deleted';
      break;
    case 'override':
      $verb = 'overridden';
      break;
    case 'revert':
      $verb = 'reverted';
      break;
  }
  drupal_set_message(t('Breakpointset %label has been @action.', array('%label' => $breakpointset->label(), '@action' => $verb)));
  watchdog('breakpointset', 'Breakpointset %label has been @action.', array('%label' => $breakpointset->label(), '@action' => $verb), WATCHDOG_NOTICE);
  $form_state['redirect'] = 'admin/config/media/breakpoints/breakpointset';
}

/**
 * Ajax callback to add a new breakpoint to a breakpoint set.
 * @see BreakpointSetFormController::form()
 */
function ajax_add_breakpoint_submit($form, $form_state) {
  return $form['breakpoints_ajax'];
}

/**
 * Pre render function.
 * @see BreakpointSetFormController::form()
 */
function breakpoints_ui_add_breakpoints_table_prerender($form) {
  foreach(element_children($form['remove']) as $child) {
    $form['#rows'][$child]['data']['remove'] = drupal_render($form['remove'][$child]);
    $form['#rows'][$child]['data']['weight'] = drupal_render($form['weight'][$child]);
  }
  return $form;
}