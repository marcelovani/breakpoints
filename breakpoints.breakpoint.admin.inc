<?php

/**
 * @file
 * Administration functions to maintain a common set of breakpoints for layouts.
 */


function breakpoints_breakpoint_page() {
  $controller = entity_list_controller('breakpoints_breakpoint');
  return $controller->render();
}
/**
 * Page callback: Presents the breakpoint editing form.
 *
 * @see breakpoint_menu()
 */
function breakpoints_breakpoint_page_edit($breakpoint) {
  drupal_set_title(t('<em>Edit breakpoint</em> @label', array('@label' => $breakpoint->label())), PASS_THROUGH);
  return entity_get_form($breakpoint);
}

/**
 * Page callback: Provides the new breakpoint addition form.
 *
 * @see breakpoint_menu()
 */
function breakpoints_breakpoint_page_add() {
  $breakpoint = entity_create('breakpoints_breakpoint', array());
  return entity_get_form($breakpoint);
}

/**
 * Page callback: Form constructor for breakpoint deletion confirmation form.
 *
 * @see breakpoint_menu()
 */
function breakpoints_breakpoint_delete_confirm($form, &$form_state, $breakpoint) {
  // Always provide entity id in the same form key as in the entity edit form.
  $form['id'] = array('#type' => 'value', '#value' => $breakpoint->id());
  $form_state['breakpoint'] = $breakpoint;
  return confirm_form($form,
    t('Are you sure you want to remove the breakpoint %title?', array('%title' => $breakpoint->label())),
    'admin/config/media/breakpoints/breakpoint',
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Form submission handler for breakpoint_delete_confirm().
 */
function breakpoints_breakpoint_delete_confirm_submit($form, &$form_state) {
  $breakpoint = $form_state['breakpoint'];
  $breakpoint->delete();
  drupal_set_message(t('Breakpoint %label has been deleted.', array('%label' => $breakpoint->label())));
  watchdog('breakpoint', 'Breakpoint %label has been deleted.', array('%label' => $breakpoint->label()), WATCHDOG_NOTICE);
  $form_state['redirect'] = 'admin/config/media/breakpoints/breakpoint';
}
