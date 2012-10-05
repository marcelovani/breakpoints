<?php

/**
 * @file
 * Administration functions to maintain a common set of breakpoints for layouts.
 */

/**
 * Page callback: Presents the breakpoint list page.
 *
 * @see breakpoints_ui_menu()
 */
function breakpoints_ui_breakpoint_page() {
  $controller = entity_list_controller('breakpoints_breakpoint');
  return $controller->render();
}

/**
 * Page callback: Presents the breakpoint editing form.
 *
 * @see breakpoints_ui_menu()
 */
function breakpoints_ui_breakpoint_page_edit($breakpoint) {
  drupal_set_title(t('<em>Edit breakpoint</em> @label', array('@label' => $breakpoint->label())), PASS_THROUGH);
  return entity_get_form($breakpoint);
}

/**
 * Page callback: Provides the new breakpoint addition form.
 *
 * @see breakpoints_ui_menu()
 */
function breakpoints_ui_breakpoint_page_add() {
  $breakpoint = entity_create('breakpoints_breakpoint', array());
  return entity_get_form($breakpoint);
}

/**
 * Page callback: Form constructor for breakpoint deletion/enabling/disabling confirmation form.
 *
 * @see breakpoints_ui_menu()
 */
function breakpoints_ui_breakpoint_action_confirm($form, &$form_state, $breakpoint, $action) {
  // Always provide entity id in the same form key as in the entity edit form.
  if (in_array($action, array('delete', 'enable', 'disable'))) {
    $form['id'] = array('#type' => 'value', '#value' => $breakpoint->id());
    $form['action'] = array('#type' => 'value', '#value' => $action);
    $form_state['breakpoint'] = $breakpoint;
    $form = confirm_form($form,
      t('Are you sure you want to @action the breakpoint %title?', array('@action' => $action, '%title' => $breakpoint->label())),
      'admin/config/media/breakpoints/breakpoint',
      $action == 'delete' ? t('This action cannot be undone.') : '' ,
      t(drupal_ucfirst($action)),
      t('Cancel')
    );
  }
  return $form;
}

/**
 * Form submission handler for breakpoints_ui_action_confirm().
 */
function breakpoints_ui_breakpoint_action_confirm_submit($form, &$form_state) {
  $breakpoint = $form_state['breakpoint'];
  $action = $form_state['values']['action'];
  $breakpoint->{$action}();
  $verb = $action . 'd'; //enabled, disabled, deleted.
  drupal_set_message(t('Breakpoint %label has been @action.', array('%label' => $breakpoint->label(), '@action' => $verb)));
  watchdog('breakpoint', 'Breakpoint %label has been @action.', array('%label' => $breakpoint->label(), '@action' => $verb), WATCHDOG_NOTICE);
  $form_state['redirect'] = 'admin/config/media/breakpoints/breakpoint';
}
