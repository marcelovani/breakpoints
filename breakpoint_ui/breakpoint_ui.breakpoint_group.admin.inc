<?php

use \Drupal\breakpoint\Breakpoint;
use \Drupal\breakpoint\BreakpointGroup;

/**
 * @file
 * Administration functions to maintain a common set of breakpoint groups.
 */

/**
 * Page callback: Presents the breakpoint group list page.
 *
 * @see breakpoint_ui_menu()
 */
function breakpoint_ui_breakpoint_group_page() {
  $controller = entity_list_controller('breakpoint_group');
  return $controller->render();
}

/**
 * Page callback: Presents the breakpoint group editing form.
 *
 * @see breakpoint_ui_menu()
 */
function breakpoint_ui_breakpoint_group_page_edit($breakpoint_group) {
  drupal_set_title(t('<em>Edit breakpoint group</em> @label', array('@label' => $breakpoint_group->label())), PASS_THROUGH);
  return entity_get_form($breakpoint_group);
}

/**
 * Page callback: Provides the new breakpoint group addition form.
 *
 * @see breakpoint_ui_menu()
 */
function breakpoint_ui_breakpoint_group_page_add() {
  $breakpoint_group = entity_create('breakpoint_group', array());
  $form = entity_get_form($breakpoint_group);
  return $form;
}

/**
 * Page callback: Provides the duplicate breakpoint group form.
 *
 * @see breakpoint_ui_menu()
 */
function breakpoint_ui_breakpoint_page_duplicate($breakpoint_group) {
  drupal_set_title(t('<em>Duplicate breakpoint</em> @label', array('@label' => $breakpoint_group->label())), PASS_THROUGH);
  return entity_get_form($breakpoint_group, 'duplicate');
}

/**
 * Page callback: Form constructor for breakpoint group action confirmation form.
 *
 * @see breakpoint_ui_menu()
 */
function breakpoint_ui_breakpoint_group_action_confirm($form, &$form_state, $breakpoint_group, $action) {
  // Always provide entity id in the same form key as in the entity edit form.
  if (in_array($action, array('delete', 'override', 'revert'))) {
    $form['id'] = array('#type' => 'value', '#value' => $breakpoint_group->id());
    $form['action'] = array('#type' => 'value', '#value' => $action);
    $form_state['breakpoint_group'] = $breakpoint_group;
    $form = confirm_form($form,
      t('Are you sure you want to @action the breakpoint group %title?', array('@action' => $action, '%title' => $breakpoint_group->label())),
      'admin/config/media/breakpoint/breakpoint_group',
      $action == 'delete' ? t('This action cannot be undone.') : '',
      t(drupal_ucfirst($action)),
      t('Cancel')
    );
  }
  return $form;
}

/**
 * Form submission handler for breakpoint_group_action_confirm().
 */
function breakpoint_ui_breakpoint_group_action_confirm_submit($form, &$form_state) {
  $breakpoint_group = $form_state['breakpoint_group'];
  $action = $form_state['values']['action'];
  $breakpoint_group->{$action}();
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
  drupal_set_message(t('Breakpoint set %label has been @action.', array('%label' => $breakpoint_group->label(), '@action' => $verb)));
  watchdog('breakpoint_group', 'Breakpoint set %label has been @action.', array('%label' => $breakpoint_group->label(), '@action' => $verb), WATCHDOG_NOTICE);
  $form_state['redirect'] = 'admin/config/media/breakpoint/breakpoint_group';
}

/**
 * Ajax callback to add a new breakpoint to a breakpoint group.
 * @see BreakpointGroupFormController::form()
 */
function ajax_add_breakpoint_submit($form, $form_state) {
  return $form['breakpoint_fieldset'];
}

/**
 * Pre render function.
 * @see BreakpointGroupFormController::form()
 */
function breakpoint_ui_add_breakpoint_table_prerender($form) {
  foreach(element_children($form) as $breakpoint_id) {
    foreach (element_children($form[$breakpoint_id]) as $breakpoint_field) {
      $form['#rows'][$breakpoint_id]['data'][$breakpoint_field] = drupal_render($form[$breakpoint_id][$breakpoint_field]);
    }
  }
  drupal_add_tabledrag('breakpoint_group-breakpoints-table', 'order', 'siblig', 'weight');
  return $form;
}