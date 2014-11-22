<?php
/**
 * @file
 * todo_general.features.inc
 */

/**
 * Implements hook_node_info().
 */
function todo_general_node_info() {
  $items = array(
    'todo' => array(
      'name' => t('ToDo'),
      'base' => 'node_content',
      'description' => '',
      'has_title' => '1',
      'title_label' => t('Title'),
      'help' => '',
    ),
  );
  return $items;
}
