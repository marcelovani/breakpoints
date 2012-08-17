<?php

/**
 * @file
 * Breakpoints - admin settings
 */

/**
 * Admin form
 */
function breakpoints_admin_breakpoints($form, &$form_state, $breakpoint_group_name = '') {
  $form = array();

  // In case of an export to theme, there will be a variable exported_breakpoints
  if (isset($form_state['exported_breakpoints']) && !empty($form_state['exported_breakpoints'])) {
    $form['exported_breakpoints'] = array(
      '#title' => t('Copy/Paste the following inside your theme.info file.'),
      '#type' => 'textarea',
      '#default_value' => $form_state['exported_breakpoints'],
    );
  }

  // Global is the same as no group name
  $global = FALSE;
  if ($breakpoint_group_name == '' || $breakpoint_group_name == 'global') {
    $breakpoint_group_name = '';
    $global = TRUE;
  }

  $form_state['group_name'] = $breakpoint_group_name;

  $settings = breakpoints_settings();
  $multipliers = array();
  if (isset($settings->multipliers) && !empty($settings->multipliers)) {
    $multipliers = drupal_map_assoc(array_values($settings->multipliers));
    if (array_key_exists('1x', $multipliers)) {
      unset($multipliers['1x']);
    }
  }

  if ($global) {
    $form['info'] = array(
      '#type' => 'markup',
      '#markup' => t("You can manage all your breakpoints on this screen, if one of your themes has breakpoints defined inside the .info file they will be shown here."),
    );

    $info = array();
    $info[] = t("To create a new breakpoint, you have to enter a name and a media query (ex. (min-width: 15em)).");
    $info[] = t("All breakpoints can be enabled or disabled so they cannot be used by other modules.");
    $info[] = t("For each breakpoint you can define what multipliers have to be available (needed to support 'retina' displays).");
    $info[] = t("Breakpoints you created yourself can be deleted.");
    $info[] = t("You can group multiple breakpoints in a group by using '!add', so other modules can easily interact with them.", array('!add' => l(t('Add a new group'), 'admin/config/media/breakpoints/groups/add')));

    $form['more_info'] = array(
      '#type' => 'container',
      '#theme' => 'item_list',
      '#items' => $info,
    );
  }
  else {
    $form['info'] = array(
      '#type' => 'markup',
      '#markup' => t("You can manage the breakpoints of this group here."),
    );
    $info = array();
    $info[] = t("You can change the order of the breakpoints inside this group.");
    $info[] = t("You can enable multipliers for each breakpoint, but this will also affect other groups.");

    $form['more_info'] = array(
      '#type' => 'container',
      '#theme' => 'item_list',
      '#items' => $info,
    );
  }

  $form['breakpoints'] = array(
    '#type' => 'container',
    '#tree' => TRUE,
    '#theme' => 'breakpoints_admin_breakpoints_table',
    '#multipliers' => $multipliers,
    '#group_name' => $breakpoint_group_name,
  );

  $breakpoints = array();
  if ($global) {
    $breakpoints = breakpoints_breakpoint_load_all();
  }
  else {
    $weight = 0;
    $breakpoint_group = breakpoints_breakpoint_group_load($breakpoint_group_name);
    foreach ($breakpoint_group->breakpoints as $breakpoint_name) {
      $breakpoint = breakpoints_breakpoint_load_by_fullkey($breakpoint_name);
      if ($breakpoint) {
        $breakpoint->global_weight = $breakpoint->weight;
        $breakpoint->weight = $weight++;
        $breakpoints[$breakpoint_name] = $breakpoint;
      }
    }
  }

  foreach ($breakpoints as $key => $breakpoint) {
    $form['breakpoints'][$key] = array(
      '#breakpoint_data' => $breakpoint,
      'name' => array(
        '#type' => 'textfield',
        '#default_value' => $breakpoint->name,
        '#disabled' => TRUE,
        '#size' => 20,
      ),
      'breakpoint' => array(
        '#type' => 'textfield',
        '#default_value' => $breakpoint->breakpoint,
        '#disabled' => $breakpoint->source_type === BREAKPOINTS_SOURCE_TYPE_THEME || !$global,
        '#size' => empty($multipliers) ? 60 : 30,
      ),
      'weight' => array(
        '#type' => 'textfield',
        '#size' => 4,
        '#default_value' => isset($breakpoint->weight) ? $breakpoint->weight : 0,
        '#attributes' => array('class' => array('breakpoints-weight')),
      ),
    );
    // Add multipliers checkboxes if needed.
    if (!empty($multipliers)) {
      $form['breakpoints'][$key]['multipliers'] = array(
        '#type' => 'checkboxes',
        '#default_value' => (isset($breakpoint->multipliers) && is_array($breakpoint->multipliers)) ? $breakpoint->multipliers : array(),
        '#options' => $multipliers,
      );
    }
    // Add global weight if needed.
    $form['breakpoints'][$key]['global_weight'] = array(
      '#type' => 'value',
      '#value' => isset($breakpoint->global_weight) ? $breakpoint->global_weight : $breakpoint->weight,
    );
  }

  if ($global) {
    // Add empty row
    $form['breakpoints']['new'] = array(
      'name' => array(
        '#type' => 'textfield',
        '#default_value' => '',
        '#size' => 20,
      ),
      'breakpoint' => array(
        '#type' => 'textfield',
        '#default_value' => '',
        '#size' => empty($multipliers) ? 60 : 30,
      ),
      'weight' => array(
        '#type' => 'textfield',
        '#size' => 4,
        '#default_value' => 0,
        '#attributes' => array('class' => array('breakpoints-weight')),
      ),
    );
    // Add multipliers checkboxes if needed.
    if (!empty($multipliers)) {
      $form['breakpoints']['new']['multipliers'] = array(
        '#type' => 'checkboxes',
        '#default_value' => array(),
        '#options' => $multipliers,
      );
    }
  }

  // Buttons
  $form['buttons'] = array(
    '#type' => 'container',
  );

  // Submit button
  $form['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

if (!$global) {
    switch ($breakpoint_group->type) {
      case BREAKPOINTS_SOURCE_TYPE_THEME:
        if (!$breakpoint_group->overridden) {
          $form['buttons']['override'] = array(
            '#type' => 'submit',
            '#value' => t('Override theme breakpoints'),
            '#submit' => array('breakpoints_admin_breakpoints_submit_override'),
          );
          $form['buttons']['reload'] = array(
            '#type' => 'submit',
            '#value' => t('Reload theme breakpoints'),
            '#submit' => array('breakpoints_admin_breakpoints_submit_reload'),
          );
        }
        else {
          $form['buttons']['exporttotheme'] = array(
            '#type' => 'submit',
            '#value' => t('Export breakpoints to theme'),
            '#submit' => array('breakpoints_admin_breakpoints_submit_exporttotheme'),
          );
          $form['buttons']['revert'] = array(
            '#type' => 'submit',
            '#value' => t('Revert theme breakpoints'),
            '#submit' => array('breakpoints_admin_breakpoints_submit_revert'),
          );
          $form['buttons']['editlink'] = array(
            '#type' => 'markup',
            '#markup' => l(
              t('Edit group breakpoints'), 'admin/config/media/breakpoints/groups/' . $breakpoint_group_name . '/edit',
              array(
                'query' => drupal_get_destination(),
                'attributes' => array('class' => array('breakpoints-group-operations-link', 'breakpoints-group-operations-edit-link')),
              )
            ),
          );
        }
        break;
      case BREAKPOINTS_SOURCE_TYPE_MODULE:
        $form['buttons']['exporttotheme'] = array(
          '#type' => 'submit',
          '#value' => t('Export breakpoints to theme'),
          '#submit' => array('breakpoints_admin_breakpoints_submit_exporttotheme'),
        );
        $form['buttons']['editlink'] = array(
          '#type' => 'markup',
          '#markup' => l(
            t('Edit group breakpoints'), 'admin/config/media/breakpoints/groups/' . $breakpoint_group_name . '/edit',
            array(
              'query' => drupal_get_destination(),
              'attributes' => array('class' => array('breakpoints-group-operations-link', 'breakpoints-group-operations-edit-link')),
            )
          ),
        );
        break;
      case BREAKPOINTS_SOURCE_TYPE_CUSTOM:
        $form['buttons']['exporttotheme'] = array(
          '#type' => 'submit',
          '#value' => t('Export breakpoints to theme'),
          '#submit' => array('breakpoints_admin_breakpoints_submit_exporttotheme'),
        );
        $form['buttons']['editlink'] = array(
          '#type' => 'markup',
          '#markup' => l(
            t('Edit group breakpoints'), 'admin/config/media/breakpoints/groups/' . $breakpoint_group_name . '/edit',
            array(
              'query' => drupal_get_destination(),
              'attributes' => array('class' => array('breakpoints-group-operations-link', 'breakpoints-group-operations-edit-link')),
            )
          ),
        );
        $form['buttons']['deletelink'] = array(
          '#type' => 'markup',
          '#markup' => l(
            t('Delete this group'), 'admin/config/media/breakpoints/groups/' . $breakpoint_group_name . '/delete',
            array(
              'query' => array('destination' => 'admin/config/media/breakpoints/groups'),
              'attributes' => array('class' => array('breakpoints-group-operations-link', 'breakpoints-group-operations-delete-link')),
            )
          ),
        );
        break;
    }
  }

  return $form;
}

/**
 * Theme form as table.
 */
function theme_breakpoints_admin_breakpoints_table($variables) {
  drupal_add_css(drupal_get_path('module', 'breakpoints') . '/css/breakpoints.admin.css');
  $form = $variables['form'];
  $rows = array();
  $link_attributes = array(
    'attributes' => array(
      'class' => array('image-style-link'),
    ),
  );
  foreach (element_children($form) as $key) {
    $form[$key]['weight']['#attributes']['class'] = array('breakpoints-weight');
    $row = array();
    if ($key != 'new') {
      $row[] = drupal_render($form[$key]['name']);
    }
    else {
      $row[] = drupal_render($form[$key]['name']);
    }
    $row[] = drupal_render($form[$key]['breakpoint']);
    $row[] = drupal_render($form[$key]['multipliers']);
    if ($key != 'new') {
      $breakpoint = $form[$key]['#breakpoint_data'];
      $row[] = $breakpoint->source . ' (' . $breakpoint->source_type . ')';
      $row[] = $breakpoint->status ? t('Enabled') : t('Disabled');
      if (empty($form['#group_name'])) {
        $row[] = l($breakpoint->status ? t('Disable') : t('Enable'), 'admin/config/media/breakpoints/' . ($breakpoint->status ? 'disable' : 'enable') . '/' . $key, $link_attributes);
      }
      if (empty($form['#group_name'])) {
        if ($breakpoint->source_type == BREAKPOINTS_SOURCE_TYPE_CUSTOM) {
          $row[] = l(t('Delete'), 'admin/config/media/breakpoints/delete/' . $key, $link_attributes);
        }
        else {
          $row[] = '';
        }
      }
      $class= 'resp-img-status-' . ($breakpoint->status ? 'enabled' : 'disabled');
    }
    else {
      $row[] = '';
      $row[] = '';
      $row[] = '';
      $row[] = '';
      $class = 'resp-img-status-new';
    }
    $row[] = drupal_render($form[$key]['weight']);
    $rows[] = array(
      'data' => $row,
      'class' => array('draggable', $class),
    );
  }

  if (empty($form['#group_name'])) {
    $header = array(
      t('Name'),
      t('Breakpoint, @media ...'),
      t('Multipliers'),
      t('Source'),
      t('Status'),
      array('data' => t('Operations'), 'colspan' => 2),
      t('Weight'),
    );
  }
  else {
    $header = array(
      t('Name'),
      t('Breakpoint, @media ...'),
      t('Multipliers'),
      t('Source'),
      t('Status'),
      t('Weight'),
    );
  }

  $output = theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'resp-img-breakpoints')));
  drupal_add_tabledrag('resp-img-breakpoints', 'order', 'sibling', 'breakpoints-weight');

  return $output;
}

/**
 * Admin form validation.
 */
function breakpoints_admin_breakpoints_validate($form, &$form_state) {
  if (strpos($form_state['triggering_element']['#id'], 'remove') === FALSE) {
    if (isset($form_state['values']['breakpoints'])) {
      $breakpoints = $form_state['values']['breakpoints'];
      if (!empty($breakpoints)) {
        foreach ($breakpoints as $key => $breakpointdata) {
          if (!empty($breakpointdata['name'])) {
            // Breakpoint is required.
            if (empty($breakpointdata['breakpoint']) && $breakpointdata['breakpoint'] !== '0') {
              form_set_error('breakpoints][' . $key . '][breakpoint', 'Breakpoint field is required');
            }
          }
        }
      }
    }
  }
}

/**
 * Admin form submit.
 */
function breakpoints_admin_breakpoints_submit($form, &$form_state) {
  $breakpoints = $form_state['values']['breakpoints'];
  $group_name = $form_state['group_name'];
  $global_group = $group_name == '';
  if (!$global_group) {
    // sort by weight, needed to store the right order in a group
    uasort($breakpoints, '_breakpoints_sort_by_weight_array');
  }
  $saved_breakpoints = array();
  if (!empty($breakpoints)) {
    foreach ($breakpoints as $breakpointname => $breakpointdata) {
      if (!empty($breakpointdata['name'])) {
        $breakpoint = breakpoints_breakpoint_load_by_fullkey($breakpointname);
        if ($breakpoint && $breakpointname != 'new') {
          // only save the weight when on the global screen.
          if ($global_group) {
            $breakpoint->weight = $breakpointdata['weight'];
          }
          else {
            $breakpoint->weight = $breakpointdata['global_weight'];
          }
          $breakpoint->breakpoint = $breakpointdata['breakpoint'];
          $breakpoint->multipliers = $breakpointdata['multipliers'];
          breakpoints_breakpoint_save($breakpoint);
          $saved_breakpoints[] = $breakpointname;
        }
        else {
          $breakpoint = new stdClass();
          $breakpoint->name = $breakpointdata['name'];
          $breakpoint->breakpoint = $breakpointdata['breakpoint'];
          $breakpoint->source = 'user';
          $breakpoint->source_type = 'custom';
          $breakpoint->weight = $breakpointdata['weight'];
          $breakpoint->status = TRUE;
          $breakpoint->multipliers = $breakpointdata['multipliers'];
          $breakpoint = breakpoints_breakpoint_save($breakpoint);
          $saved_breakpoints[] = breakpoints_breakpoint_config_name($breakpoint);
        }
      }
    }

    if (!$global_group) {
      $group = breakpoints_breakpoint_group_load($group_name);
      if ($group) {
        $group->breakpoints = $saved_breakpoints;
        breakpoints_breakpoint_group_save($group);
      }
    }
  }
}

/**
 * Admin action form: enable, disable, delete
 */
function breakpoints_admin_breakpoint_actions_form($form, &$form_state, $action, $breakpoint) {
  $form_state['action'] = $action;
  $form_state['breakpoint'] = $breakpoint;
  $question = t('Are you sure you want to %action %breakpoint', array(
    '%action' => $action,
    '%breakpoint' => $breakpoint,
  ));
  $path = 'admin/config/media/breakpoints';
  return confirm_form($form, $question, $path, '');
}

/**
 * Admin action form submit
 */
function breakpoints_admin_breakpoint_actions_form_submit($form, &$form_state) {
  $action = $form_state['action'];
  $breakpoint = $form_state['breakpoint'];
  switch ($action) {
    case 'delete':
      breakpoints_breakpoint_delete($breakpoint);
      break;
    case 'enable':
    case 'disable':
      breakpoints_breakpoint_toggle_status($breakpoint);
      break;
  }
  $form_state['redirect'] = 'admin/config/media/breakpoints';
}

function breakpoints_admin_breakpoint_group_edit_form($form, &$form_state, $machine_name = '') {
  $form = array();
  $group = breakpoints_breakpoint_group_load($machine_name);

  $form_state['#breakpoint_group'] = $group;
  $is_new = $machine_name == '';
  $form_state['#is_new'] = $is_new;

  $form['name'] = array(
    '#type' => 'textfield',
    '#size' => '64',
    '#title' => t('group name'),
    '#required' => TRUE,
    '#default_value' => isset($group->name) ? $group->name : '',
    '#disabled' => !$is_new,
  );

  $form['machine_name'] = array(
    '#type' => 'machine_name',
    '#size' => '64',
    '#title' => t('Machine name'),
    '#required' => TRUE,
    '#default_value' => isset($group->machine_name) ? $group->machine_name : '',
    '#disabled' => !$is_new,
    '#machine_name' => array(
      'exists' => 'breakpoints_breakpoint_group_name_exists',
    ),
  );

  $breakpoints = breakpoints_breakpoint_load_all();
  foreach ($breakpoints as $breakpoint_name => $breakpoint) {
    $options[$breakpoint_name] = $breakpoint->name . ' [' . $breakpoint->breakpoint . ']';
  }

  $form['breakpoints'] = array(
    '#title' => 'Select the breakpoints you want to use in this group',
    '#type' => 'checkboxes',
    '#options' => $options,
    '#default_value' => isset($group->breakpoints) ? drupal_map_assoc($group->breakpoints) : array(),
    '#required' => TRUE,
  );

  // Buttons
  $form['buttons'] = array(
    '#type' => 'container',
  );

  // Submit button
  $form['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  if (!$is_new && $group->type == BREAKPOINTS_SOURCE_TYPE_CUSTOM) {
    $form['buttons']['deletelink'] = array(
      '#type' => 'markup',
      '#markup' => l(t('Delete this group'), 'admin/config/media/breakpoints/groups/' . $group->machine_name . '/delete', array(
        'query' => drupal_get_destination(),
      )),
    );
  }

  return $form;
}

function breakpoints_admin_breakpoint_group_edit_form_validate($form, &$form_state) {
  $name = $form_state['values']['machine_name'];
  $label = $form_state['values']['name'];
}

function breakpoints_admin_breakpoint_group_edit_form_submit($form, &$form_state) {
  $machine_name = $form_state['values']['machine_name'];
  $name = $form_state['values']['name'];
  $breakpoints = array();
  foreach ($form_state['values']['breakpoints'] as $breakpoint => $status) {
    if ($status) {
      $breakpoints[] = $breakpoint;
    }
  }
  $is_new = $form_state['#is_new'];

  if ($is_new) {
    $new_group = breakpoints_breakpoint_group_empty_object();
    $new_group->machine_name = $machine_name;
    $new_group->name = $name;
    $new_group->type = BREAKPOINTS_SOURCE_TYPE_CUSTOM;
    $new_group->breakpoints = $breakpoints;
    breakpoints_breakpoint_group_save($new_group);
    menu_router_rebuild();
    $form_state['redirect'] = 'admin/config/media/breakpoints/groups/' . $machine_name;
    drupal_set_message(t('The new group have been created'));
  }
  else {
    $existing_group = breakpoints_breakpoint_group_load($name);
    $existing_group->breakpoints = $breakpoints;
    breakpoints_breakpoint_group_save($existing_group);
  }
}

/**
 * Admin form submit - Override theme breakpoints.
 */
function breakpoints_admin_breakpoints_submit_override($form, &$form_state) {
  $group_name = $form_state['group_name'];
  $global_group = $group_name == '';
  if (!$global_group) {
    $group = breakpoints_breakpoint_group_load($group_name);
    if ($group) {
      breakpoints_breakpoints_group_override($group);
    }
  }
}

/**
 * Admin form submit - Revert theme breakpoints.
 */
function breakpoints_admin_breakpoints_submit_revert($form, &$form_state) {
  $group_name = $form_state['group_name'];
  $global_group = $group_name == '';
  if (!$global_group) {
    $group = breakpoints_breakpoint_group_load($group_name);
    if ($group) {
      breakpoints_breakpoints_group_revert($group);
    }
  }
}

/**
 * Admin form submit - Reload theme breakpoints.
 */
function breakpoints_admin_breakpoints_submit_reload($form, &$form_state) {
  $group_name = $form_state['group_name'];
  $global_group = $group_name == '';
  if (!$global_group) {
    $group = breakpoints_breakpoint_group_load($group_name);
    if ($group) {
      breakpoints_breakpoints_group_reload($group);
    }
  }
}

/**
 * Admin form submit - Export breakpoints to theme.
 */
function breakpoints_admin_breakpoints_submit_exporttotheme($form, &$form_state) {
  $group_name = $form_state['group_name'];
  $global_group = $group_name == '';
  if (!$global_group) {
    $group = breakpoints_breakpoint_group_load($group_name);
    if ($group) {
      $breakpoints = breakpoints_breakpoints_group_exporttotheme($group);
      if ($breakpoints) {
        $export = array();
        foreach ($breakpoints as $breakpoint_name => $breakpoint) {
          $export[] = 'breakpoints[' . $breakpoint_name . '] = ' . $breakpoint;
        }
        $form_state['exported_breakpoints'] = implode("\n", $export);
        $form_state['rebuild'] = TRUE;
      }
    }
  }
}

/**
 * Delete a group.
 */
function breakpoints_admin_breakpoint_group_delete_form($form, &$form_state, $machine_name) {
  $group = breakpoints_breakpoint_group_load($machine_name);
  $form_state['machine_name'] = $machine_name;
  $question = t('Are you sure you want to delete %group', array(
    '%group' => $group->name,
  ));
  $path = 'admin/config/media/breakpoints/groups/' . $machine_name;
  return confirm_form($form, $question, $path, '');
}

/**
 * Delete a group.
 */
function breakpoints_admin_breakpoint_group_delete_form_submit($form, &$form_state) {
  $machine_name = $form_state['machine_name'];
  breakpoints_breakpoint_group_delete_by_name($machine_name);
  menu_router_rebuild();
  $form_state['redirect'] = 'admin/config/media/breakpoints/groups';
}
