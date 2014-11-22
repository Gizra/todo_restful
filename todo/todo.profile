<?php
/**
 * @file
 * ToDo profile.
 */

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Allows the profile to alter the site configuration form.
 */
function todo_form_install_configure_form_alter(&$form, $form_state) {
  // Pre-populate the site name with the server name.
  $form['site_information']['site_name']['#default_value'] = $_SERVER['SERVER_NAME'];
}

/**
 * Implements hook_install_tasks().
 */
function todo_install_tasks() {
  $tasks = array();

  $tasks['todo_setup_permissions'] = array(
    'display_name' => st('Setup permissions'),
    'display' => FALSE,
  );

  return $tasks;
}

/**
 * Task callback; Setup permissions.
 */
function todo_setup_permissions() {
  $permissions = array(
    'create todo content',
    'edit own todo content',
    'delete own todo content',
  );

  user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, $permissions);
}
