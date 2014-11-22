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

  /**
   * Overrides \RestfulEntityBase::updateEntity().
   *
   * Unset the ID from the request if it was sent.
   */
  protected function updateEntity($entity_id, $null_missing_fields = FALSE) {
    $request = $this->getRequest();
    unset($request['id']);
    $this->setRequest($request);
    return parent::updateEntity($entity_id, $null_missing_fields);
  }

}
