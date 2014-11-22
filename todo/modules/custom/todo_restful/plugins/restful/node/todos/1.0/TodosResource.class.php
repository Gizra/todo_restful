<?php

/**
 * @file
 * Contains TodosResource.
 */

class TodosResource extends \RestfulEntityBaseNode {

  /**
   * Overrides \RestfulDataProviderEFQ::controllersInfo().
   *
   * The app allows clearing "completed" todos. As it is not a typical RESTful
   * task we handle it in a custom callback.
   *
   */
  public static function controllersInfo() {
    $controllers = parent::controllersInfo();
    $controllers[''][\RestfulInterface::DELETE] = 'deleteCompleted';

    return $controllers;
  }

  /**
   * Overrides \RestfulEntityBaseNode::getQueryForList().
   *
   * Expose only nodes that belong to the current user.
   */
  public function getQueryForList() {
    $query = parent::getQueryForList();
    $query->propertyCondition('uid', $this->getAccount()->uid);
    return $query;
  }


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
   * Unset the ID and self properties from the request if it was sent.
   */
  protected function updateEntity($entity_id, $null_missing_fields = FALSE) {
    $request = $this->getRequest();
    unset($request['id']);
    unset($request['self']);
    $this->setRequest($request);
    return parent::updateEntity($entity_id, $null_missing_fields);
  }

  /**
   * Delete all the completed todos by the acting user.
   */
  protected function deleteCompleted() {
    $account = $this->getAccount();

    $query = new EntityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', 'node')
      ->entityCondition('bundle', $this->getBundle())
      ->propertyCondition('uid', $account->uid)
      ->propertyCondition('status', NODE_PUBLISHED)
      ->fieldCondition('field_completed', 'value', TRUE)
      ->execute();

    if (empty($result['node'])) {
      // Set the HTTP headers.
      $this->setHttpHeaders('Status', 204);
      return;
    }

    // We don't call node_delete() directly, as we want to confirm user
    // has access to the delete.
    foreach (array_keys($result['node']) as $entity_id) {
      $this->deleteEntity($entity_id);
    }
  }

}
