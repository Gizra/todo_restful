<?php
/**
 * @file
 * todo_general.features.field_instance.inc
 */

/**
 * Implements hook_field_default_field_instances().
 */
function todo_general_field_default_field_instances() {
  $field_instances = array();

  // Exported field_instance: 'node-todo-field_completed'
  $field_instances['node-todo-field_completed'] = array(
    'bundle' => 'todo',
    'default_value' => array(
      0 => array(
        'value' => 0,
      ),
    ),
    'deleted' => 0,
    'description' => '',
    'display' => array(
      'default' => array(
        'label' => 'above',
        'module' => 'list',
        'settings' => array(),
        'type' => 'list_default',
        'weight' => 1,
      ),
      'teaser' => array(
        'label' => 'above',
        'settings' => array(),
        'type' => 'hidden',
        'weight' => 0,
      ),
    ),
    'entity_type' => 'node',
    'field_name' => 'field_completed',
    'label' => 'Completed',
    'required' => 0,
    'settings' => array(
      'user_register_form' => FALSE,
    ),
    'widget' => array(
      'active' => 1,
      'module' => 'options',
      'settings' => array(
        'display_label' => 1,
      ),
      'type' => 'options_onoff',
      'weight' => -3,
    ),
  );

  // Translatables
  // Included for use with string extractors like potx.
  t('Completed');

  return $field_instances;
}
