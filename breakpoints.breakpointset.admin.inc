<?php

use \Drupal\breakpoints\Breakpoint;
use \Drupal\breakpoints\BreakpointSet;

/**
 * @file
 * Administration functions to maintain a common set of breakpointsets for layouts.
 */

function breakpoints_breakpointset_page() {
  $controller = entity_list_controller('breakpoints_breakpointset');
  return $controller->render();
}

/**
 * Page callback: Presents the breakpointset editing form.
 *
 * @see breakpoint_menu()
 */
function breakpoints_breakpointset_page_edit($breakpointset) {
  drupal_set_title(t('<em>Edit breakpoint</em> @label', array('@label' => $breakpointset->label())), PASS_THROUGH);
  return entity_get_form($breakpointset);
}

/**
 * Page callback: Provides the new breakpointset addition form.
 *
 * @see breakpoint_menu()
 */
function breakpoints_breakpointset_page_add() {
  $breakpoint = entity_create('breakpoints_breakpointset', array());
  return entity_get_form($breakpoint);
}

/**
 * Page callback: Form constructor for breakpointset deletion confirmation form.
 *
 * @see breakpoint_menu()
 */
function breakpoints_breakpointset_delete_confirm($form, &$form_state, $breakpointset) {
  // Always provide entity id in the same form key as in the entity edit form.
  $form['id'] = array('#type' => 'value', '#value' => $breakpointset->id());
  $form_state['breakpointset'] = $breakpointset;
  return confirm_form($form,
    t('Are you sure you want to remove the breakpointset %title?', array('%title' => $breakpointset->label())),
    'admin/config/media/breakpoints/breakpointset',
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Form submission handler for breakpointset_delete_confirm().
 */
function breakpoints_breakpointset_delete_confirm_submit($form, &$form_state) {
  $breakpointset = $form_state['breakpointset'];
  $breakpointset->delete();
  drupal_set_message(t('Breakpointset %label has been deleted.', array('%label' => $breakpointset->label())));
  watchdog('breakpointset', 'Breakpointset %label has been deleted.', array('%label' => $breakpointset->label()), WATCHDOG_NOTICE);
  $form_state['redirect'] = 'admin/config/media/breakpoints/breakpointset';
}
