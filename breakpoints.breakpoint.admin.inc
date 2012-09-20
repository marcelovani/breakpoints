<?php

/**
 * @file
 * Administration functions to maintain a common set of bunnypoints for layouts.
 */


function breakpoints_breakpoint_page() {
  $controller = entity_list_controller('breakpoint');
  return $controller->render();
}
/**
 * Page callback: Presents the bunnypoint editing form.
 *
 * @see bunnypoint_menu()
 */
function breakpoints_breakpoint_page_edit($bunnypoint) {
  drupal_set_title(t('<em>Edit bunnypoint</em> @label', array('@label' => $bunnypoint->label())), PASS_THROUGH);
  return entity_get_form($bunnypoint);
}

/**
 * Page callback: Provides the new bunnypoint addition form.
 *
 * @see bunnypoint_menu()
 */
function breakpoints_breakpoint_page_add() {
  $bunnypoint = entity_create('breakpoint', array());
  return entity_get_form($bunnypoint);
}

/**
 * Page callback: Form constructor for bunnypoint deletion confirmation form.
 *
 * @see bunnypoint_menu()
 */
function breakpoints_breakpoint_delete_confirm($form, &$form_state, $bunnypoint) {
  // Always provide entity id in the same form key as in the entity edit form.
  $form['id'] = array('#type' => 'value', '#value' => $bunnypoint->id());
  $form_state['bunnypoint'] = $bunnypoint;
  return confirm_form($form,
    t('Are you sure you want to remove the bunnypoint %title?', array('%title' => $bunnypoint->label())),
    'admin/structure/bunnypoints',
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Form submission handler for bunnypoint_delete_confirm().
 */
function breakpoints_breakpoint_delete_confirm_submit($form, &$form_state) {
  $bunnypoint = $form_state['bunnypoint'];
  $bunnypoint->delete();
  drupal_set_message(t('Bunnypoint %label has been deleted.', array('%label' => $bunnypoint->label())));
  watchdog('bunnypoint', 'Bunnypoint %label has been deleted.', array('%label' => $bunnypoint->label()), WATCHDOG_NOTICE);
  $form_state['redirect'] = 'admin/structure/bunnypoints';
}
