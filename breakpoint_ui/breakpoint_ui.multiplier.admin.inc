<?php

/**
 * Multipliers administration form.
 */
function breakpoint_ui_multipliers_form($form, &$form_state) {
  $multipliers = drupal_map_assoc(config('breakpoint')->get('multipliers'));
  if (isset($multipliers['1x'])) {
    unset($multipliers['1x']);
  }
  $form['multipliers'] = array(
    '#type' => 'container',
    '#tree' => TRUE,
    '#theme' => 'breakpoint_ui_multipliers_table_form',
  );
  $form['multipliers']['1x'] = array(
    '#markup' => '1x',
  );
  foreach ($multipliers as $multiplier) {
    $form['multipliers'][$multiplier] = array(
      '#type' => 'textfield',
      '#title' => '',
      '#required' => FALSE,
      '#default_value' => $multiplier,
    );
  }
  $form['multipliers']['new'] = array(
    '#type' => 'textfield',
    '#title' => '',
    '#required' => FALSE,
    '#default_value' => '',
    '#description' => t('Multiplier ex. 1.5x 2x.'),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );
  return $form;
}

/**
 * Validate callback for breakpoint_ui_multipliers_form().
 */
function breakpoint_ui_multipliers_form_validate($form, $form_state) {
  $multipliers = $form_state['values']['multipliers'];
  $saved_multipliers = array(
    '1x' => '1x',
  );
  foreach ($multipliers as $form_key => $multiplier) {
    if ($multiplier == '' && $form_key != 'new') {
      form_set_error('multipliers][' . $form_key, t('Label is required.'));
    }
    if ($multiplier != '') {
      if (isset($saved_multipliers[$multiplier])) {
        form_set_error('multipliers][' . $form_key, t('Label must be unique.'));
      }
      if (!preg_match('/^(\d+(?:\.\d+)?)x$/i', $multiplier)) {
        form_set_error('multipliers][' . $form_key, t("Multiplier has to be a number followed by an 'x'."));
      }
      $saved_multipliers[$multiplier] = $multiplier;
    }
  }
}

/**
 * Submit callback for breakpoint_ui_multipliers_form().
 */
function breakpoint_ui_multipliers_form_submit($form, &$form_state) {
  $multipliers = array_values(array_filter($form_state['values']['multipliers']));
  array_unshift($multipliers, '1x');
  config('breakpoint')->set('multipliers', $multipliers)->save();
  drupal_set_message(t('Multipliers are saved.'));
}

/**
 * Multipliers delete form.
 */
function breakpoint_ui_admin_multiplier_delete_form($form, &$form_state, $multiplier) {
  $path = 'admin/config/media/breakpoint/multipliers';
  if ($multiplier == '1x') {
    $form['multiplier'] = array(
      '#markup' => t('Multiplier %multiplier can not be deleted! !link', array('%multiplier' => $multiplier, '!link' => l(t('Back to overview page.'), $path)))
    );
    return $form;
  }
  $form['multiplier'] = array(
    '#type' => 'value',
    '#value' => $multiplier,
  );
  return confirm_form($form, t('Are you sure you want to delete multiplier %multiplier', array('%multiplier' => $multiplier)), $path);
}

/**
 * Sumbit callback for breakpoint_ui_admin_multiplier_delete_form().
 */
function breakpoint_ui_admin_multiplier_delete_form_submit($form, &$form_state) {
  $settings = breakpoint_settings();
  $multiplier = $form_state['values']['multiplier'];
  $multipliers = drupal_map_assoc($settings->multipliers);
  if (isset($multipliers[$multiplier])) {
    unset($multipliers[$multiplier]);
  }
  breakpoint_settings_save_multipliers(array_values($multipliers));
  drupal_set_message(t('Multiplier %multiplier was deleted', array('%multiplier' => $multiplier)));
  $form_state['redirect'] = 'admin/config/media/breakpoint/multipliers';
}

/**
 * Theme multipliers form as table.
 */
function theme_breakpoint_ui_multipliers_table_form($element) {
  $form = $element['form'];
  $header = array(t('Multiplier'), t('Operations'));
  foreach (element_children($form) as $multiplier) {
    $row = array();
    $row[] = drupal_render($form[$multiplier]);
    $row[] = in_array($multiplier, array('new', '1x')) ? '' : l(t('Delete'), 'admin/config/media/breakpoint/multipliers/' . $multiplier . '/delete');
    $rows[] = $row;
  }
  return theme('table', array('header' => $header, 'rows' => $rows));
}