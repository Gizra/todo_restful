<?php

/**
 * @file
 * Contains TodosResource.
 */

class TodosResource extends \RestfulEntityBaseNode {


  /**
   * {@inheritdoc}
   */
  public function publicFieldsInfo() {
    $public_fields = parent::publicFieldsInfo();

    $public_fields['title'] = $public_fields['label'];
    unset($public_fields['label']);

    $public_fields['completed'] = array(
      'property' => 'field_completed',
    );

    return $public_fields;
  }
}
