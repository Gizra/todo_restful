<?php

use Drupal\DrupalExtension\Context\DrupalContext;
use Behat\Behat\Context\Step\Given;
use Behat\Gherkin\Node\TableNode;
use Guzzle\Service\Client;
use Behat\Behat\Context\Step;

require 'vendor/autoload.php';

class FeatureContext extends Drupal\DrupalExtension\Context\DrupalContext {

  /**
   * Array of flaggings in the tests to revert in the end of testing.
   *
   * Required parameters for every element in the array:
   * - entity_id: Entity ID.
   * - flag_name: Name of the flag to unflag. Entity type is derived
   *   from the flag.
   */
  private $flagged = array();

  /**
   * Initializes context.
   *
   * Every scenario gets its own context object.
   *
   * @param array $parameters.
   *   Context parameters (set them up through behat.yml or behat.local.yml).
   */
  public function __construct(array $parameters) {
    if (!empty($parameters['drupal_users'])) {
      $this->drupal_users = $parameters['drupal_users'];
    }

    if (!empty($parameters['sample_nodes'])) {
      $this->sample_nodes = $parameters['sample_nodes'];
    }
  }

  /**
   * @Given /^I am logged in as a user from "([^"]*)"$/
   */
  public function iAmLoggedInAsAUserFrom($company) {
    // Log-in and then group the created user to the given company.
    $this->assertAuthenticatedByRole('authenticated user');
    $uid = $this->user->uid;
    $nid = $this->getEntityId($company);
    og_group('node', $nid, array('entity' => $uid));
  }

  /**
   * Authenticates a user with password from configuration.
   *
   * @Given /^I am logged in as the "([^"]*)"$/
   */
  public function iAmLoggedInAs($username) {
    $this->user = new stdClass();
    $this->user->name = $username;
    $this->user->pass = $this->drupal_users[$username];
    $this->login();
  }


  /**
   * @Given /^I am on a "([^"]*)" page titled "([^"]*)"(?:, in the tab "([^"]*)"|)$/
   */
  public function iAmOnAPageTitled($bundle, $title, $subpage = NULL) {
    if (!$id = $this->getEntityId($title, 'node', str_replace('-', '_', $bundle))) {
      throw new \Exception("No $bundle with title '$title' was found.");
    }

    // @todo: Remove hardcoding of imanimo.
    $path = "imanimo/$bundle/$id/$subpage";
    return new Given("I am at \"$path\"");
  }

  /**
   * Find entity ID by title.
   */
  private function getEntityId($title, $entity_type = 'node', $bundle = NULL) {
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', $entity_type);

    if ($bundle) {
      $query->entityCondition('bundle', $bundle);
    }

    $result = $query
      ->propertyCondition('title', $title)
      ->range(0, 1)
      ->execute();

    return !empty($result[$entity_type]) ? key($result[$entity_type]) : FALSE;
  }

  /**
   * @Given /^I should see the following <links>$/
   */
  public function iShouldSeeTheFollowingLinks(TableNode $table) {
    $page = $this->getSession()->getPage();
    $table = $table->getHash();

    foreach ($table as $key => $value) {
      $link = $table[$key]['links'];
      $result = $page->findLink($link);
      if(empty($result)) {
        throw new \Exception("The link '$link' was not found");
      }
    }
  }

  /**
   * @Then /^I should see a table titled "([^"]*)" with the following <contents>:$/
   */
  public function iShouldSeeATableTitledWithTheFollowingContents($title, TableNode $expected_table) {
    $page = $this->getSession()->getPage();
    // Find the container of the table with the correct pane title
    $element = $page->find('xpath', '//h2[.="' . $title .'"]/parent::div');
    if (!$element) {
      // If not found, search for a table with $title as its caption. Select
      // the parent of the table element.
      $element = $page->find('xpath', '//caption[.="' . $title .'"]/../..');
    }
    if (!$element) {
      throw new \Exception("No table titled '$title' was found.");
    }

    $table_element = $element->find('css', 'table');
    if (!$table_element) {
      throw new \Exception("No table was found inside the pane titled '$title'.");
    }

    $this->compareTable($table_element, $expected_table);
  }

  /**
   * @Then /^the table "([^"]*)" should have the following <contents>:$/
   */
  public function theTableShouldHaveTheFollowingContents($table_id, TableNode $expected_table) {
    $page = $this->getSession()->getPage();
    // Find the container of the table with the correct pane title
    $selector = strpos($table_id, 'view-') === 0 ? ".$table_id table" : "table#$table_id";
    $table_element = $page->find('css', $selector);
    if (!$table_element) {
      throw new \Exception("No table with id '$table_id' was found.");
    }
    $this->compareTable($table_element, $expected_table);
  }

  /**
   * @Given /^the order "([^"]*)" should have these <inventory lines>$/
   */
  public function theOrderShouldHaveTheseInventoryLines($order_title, TableNode $expected_table) {
    $page = $this->getSession()->getPage();
    $element = $page->find('xpath', '//a[.="' . $order_title .'"]/../..');
    if (!$element) {
      throw new \Exception("The row holding order '$order_title' was not found.");
    }
    $inventory_wrapper_id = $element->getAttribute('ref');
    $table_element = $page->find('css', "#$inventory_wrapper_id");
    if (!$table_element) {
      throw new \Exception("The inventory lines table of order '$order_title' was not found.");
    }
    $this->compareTable($table_element, $expected_table);
  }

  /**
   * @Given /^the BOM total should be "([^"]*)"$/
   */
  public function theBomTotalShouldBe($total) {
    if ($this->getSession()->getPage()->find('css', '.bom-total .amount')->getText() != $total) {
      throw new \Exception("The BOM has a different total price than '$total'.");
    }
  }

  /**
   * @Given /^the "([^"]*)" price should be "([^"]*)"$/
   */
  public function thePriceShouldBe($price_type, $price) {
    switch ($price_type) {
      case 'production':
        $selector = '.pane-production-price';
        break;

      case 'wholesale':
      case 'retail':
        $selector = ".field-name-field-{$price_type}-price";
        break;
    }
    if ($this->getSession()->getPage()->find('css', "$selector .field-item")->getText() != $price) {
      throw new \Exception("The production price is not '$price'.");
    }
  }

  /**
   * @Given /^the page status is shown as "([^"]*)"$/
   */
  public function thePageStatusIsShownAs($status) {
    if (!$this->getSession()->getPage()->find('xpath', '//div[contains(@class, "field-item") and .="' . $status . '"]')) {
      throw new Exception("Missing indication for status '$status'.");
    }
  }

  /**
   * Compare a present table with an expected table.
   *
   * @param $table_element
   *   A NodeElement containing a table.
   * @param $expected_table
   *   TableNode containing the expected table.
   */
  private function compareTable($table_element, TableNode $expected_table) {
    $element_head = $table_element->find('css', 'thead');
    $expected_rows = $expected_table->getRows();
    $expected_head_row = array_shift($expected_rows);
    // Compare the table header.
    $this->compareTableRow($element_head->findAll('css', 'th'), $expected_head_row);

    // Compare the rows.
    foreach ($table_element->findAll('xpath', "//tbody/tr[not(contains(@class, 'hidden'))]") as $i => $row) {
      if (empty($expected_rows[$i])) {
        break;
      }
      $this->compareTableRow($row->findAll('css', 'td'), $expected_rows[$i]);
    }
  }

  /**
   * Compare a present table row cells with the expected row.
   *
   * @param $cells
   *   Array of NodeElement: Table row cells retrieved with findAll().
   * @param $expected_row
   *   One row from the TableNode object.
   */
  private function compareTableRow($cells, $expected_row) {
    foreach ($cells as $i => $cell) {
      if (!array_key_exists($i, $expected_row)) {
        throw new \Exception("Unexpected cell with text '{$cell->getText()}'.");
      }

      $content = self::getText($cell->getHtml());

      $words = explode(' ', $expected_row[$i]);
      $first_word = !empty($words[0]) ? $words[0] : '';
      switch ($first_word) {
        case '<ignore>':
          continue 2;

        case '<image>':
          // Make sure the cell contains an image tag.
          self::verifyImageExists($cell);
          break;

        case '<date>':
          $expected_time = strtotime($words[1]);
          if (!$expected_time) {
            throw new \Exception("Couldn't parse date '{$words[1]}', use 'MM/DD/YYYY'.");
          }
          $time = strtotime($content);
          if ($expected_time != $time) {
            throw new \Exception("Found '$time' instead of '$expected_time'.");
          }
          break;

        case '<checkbox>':
          $checkbox = $cell->find('xpath', "//input[@type='checkbox']");
          if (!$checkbox) {
            throw new \Exception('Expected checkbox not found.');
          }

          if (!empty($words[1]) && $words[1] == 'checked') {
            if (!$checkbox->getAttribute('checked') && !$checkbox->getAttribute('value')) {
              throw new \Exception('Checkbox found but is not checked.');
            }
          }
          break;

        case '<input>':
          if (!$cell->find('css', 'input')) {
            throw new \Exception('Expected input element not found.');
          }
          break;

        case '<textfield>':
          $input = $cell->find('css', '.form-text');
          if (!$input) {
            throw new \Exception('Textfield not found.');
          }

          if (!empty($words[1])) {
            $value = $input->getAttribute('value');
            if ($value != $words[1]) {
              throw new \Exception("Found '$value' instead of '$words[1]'.");
            }
          }
          break;

        case '<flag>':
          $flag = $cell->find('css', 'a.flag-link-toggle');
          if (!$flag) {
            throw new \Exception('Flag not found.');
          }

          if (!empty($words[1]) && $words[1] == 'unflag' && !$flag->hasClass('unflag-action')) {
            throw new \Exception('Expected unflag link.');
          }
          break;

        default:
          if ($content != $expected_row[$i]) {
            throw new \Exception("Found '$content' instead of '{$expected_row[$i]}'.");
          }
      }
    }

    if (count($expected_row) > $i + 1) {
      throw new \Exception("Missing column '{$expected_row[$i]}'.");
    }
  }

  /**
   * TODO: This method should be in a class extending BrowserKitDriver.
   *
   * Strip HTML but insert spaces between elements. Taken from the comments on:
   * http://php.net/manual/en/function.strip-tags.php
   *
   * @param $html
   *   Plain HTML.
   *
   * @return
   *   Stripped contents of the HTML.
   */
  private static function getText($html) {
     // Remove HTML tags.
    $html = preg_replace ('/<[^>]*>/', ' ', $html);

    // Remove control characters.
    $html = str_replace("\r", '', $html);
    $html = str_replace("\n", ' ', $html);
    $html = str_replace("\t", ' ', $html);

    // Remove multiple spaces.
    return trim(preg_replace('/ {2,}/', ' ', $html));
  }

  /**
   * Make sure that a DOM element contains an image tag, and that the image
   * itself is accessible to GET requests.
   *
   * @param $element
   *   NodeElement that should contain an image tag.
   */
  private static function verifyImageExists($element) {
    // Fetch the image tag.
    if (!$image_element = $element->find('css', 'img')) {
      throw new \Exception('Missing image tag.');
    }

    /* TODO: Find a way to enable styled images creation on Travis ci.
    // Send a GET request to the image to make sure it's accessible.
    $image_url = $image_element->getAttribute('src');
    $client = new Client();
    $response = $client->get($image_url)->send();
    $info = $response->getInfo();
    if ($info['http_code'] != 200) {
      throw new \Exception("Image not accessible. URL: $image_url");
    }*/
  }

  /**
   * @Given /^I go to create "([^"]*)" node page$/
   */
  public function iGoToCreateNodePage($node_type) {
    // TODO: The "imanimo" should be removed once it's added automatically.
    $path = 'imanimo/node/add/' . $node_type;
    return new Given("I am at \"$path\"");
  }

  /**
   * @When /^I visit the front page$/
   */
  public function iVisitTheFrontPage() {
    return new Given("I am at \"/\"");
  }

  /**
   * @Then /^I should be on a page titled "([^"]*)"$/
   */
  public function iShouldBeOnAPageTitled($expected_title) {
    $title = $this->getSession()->getPage()->find('css', 'head title')->getText();
    if ($title != $expected_title) {
      throw new \Exception("Expected title '$expected_title', found instead '$title'.");
    }
  }

  /**
   * @Then /^the URL query "([^"]*)" should have the id of "([^"]*)"$/
   */
  public function theUrlQueryShouldHaveTheIdOf($query_key, $node_title) {
    $nid = self::getEntityId($node_title);
    $query = "$query_key=$nid";

    if (!strstr($this->getSession()->getCurrentUrl(), $query)) {
      throw new \Exception("The URL doesn't contain '$query'.");
    }
  }

  /**
   * @Given /^I (uncheck|check) "([^"]*)" in row containing "([^"]*)" in table "([^"]*)"$/
   */
  public function iUncheckInRowContainingOfTable($check, $column_title, $value_in_row, $table_id) {
    $page = $this->getSession()->getPage();
    $table_element = $page->find('css', "table#$table_id");

    $cell = self::findTableCellByColumTitleAndRowValue($table_element, $column_title, $value_in_row);
    $checkbox = $cell->find('xpath', "//input[@type='checkbox']");
    if ($check == 'check') {
      $checkbox->check();
    }
    else {
      $checkbox->uncheck();
    }
  }

  /**
   * @When /^I click "([^"]*)" in row containing "([^"]*)" in table "([^"]*)"$/
   */
  public function iClickInRowContainingInTable($column_title, $value_in_row, $table_id) {
    $page = $this->getSession()->getPage();
    $table_element = $page->find('css', "table#$table_id");

    $cell = self::findTableCellByColumTitleAndRowValue($table_element, $column_title, $value_in_row);
    $element = $cell->find('xpath', "//input");
    $element->click();
  }

  /**
   * @Given /^I fill in "([^"]*)" with "([^"]*)" in row containing "([^"]*)" in table "([^"]*)"$/
   */
  public function iFillInWithInRowContainingOfTable($column_title, $content, $value_in_row, $table_id) {
    $page = $this->getSession()->getPage();
    $table_element = $page->find('css', "table#$table_id");

    $cell = self::findTableCellByColumTitleAndRowValue($table_element, $column_title, $value_in_row);
    $input = $cell->find('css', 'input');
    $input->setValue($content);
  }

  /**
   * "Triangulate" a table cell by header and row content.
   */
  private static function findTableCellByColumTitleAndRowValue($table_element, $column_title, $value_in_row) {
    // Find the column index.
    $column_index = 0;
    foreach ($table_element->findAll('css', 'thead th') as $index => $th) {
      if (self::getText($th->getHtml()) == $column_title) {
        $column_index = $index + 1;
        break;
      }
    }
    if (!$column_index) {
      throw new \Exception("No column titled '$column_title' was found.");
    }

    // Find the row containing $value_contained.
    $row_found = FALSE;
    foreach ($table_element->findAll('css', 'tbody tr') as $index => $tr) {
      foreach ($tr->findAll('css', 'td') as $td) {
        if (self::getText($td->getHtml()) == $value_in_row) {
          $row_found = TRUE;
        }
      }

      if ($row_found) {
        $td = $tr->find('xpath', "//td[$column_index]");
        return $td;
      }
    }

    if (!$row_found) {
      throw new \Exception("No cell containing '$value_in_row' was found.");
    }
  }

  /**
   * @Then /^the "([^"]*)" column of "([^"]*)" should be "([^"]*)"$/
   */
  public function theColumnOfShouldBe($column_title, $value_in_row, $content) {
    $page = $this->getSession()->getPage();
    $table_element = $page->find('css', "table#$table_id");

    $cell = self::findTableCellByColumTitleAndRowValue($table_element, $column_title, $value_in_row);
    $input = $cell->find('css', 'input');
    $input->setValue($content);
  }

  /**
   * @Then /^the "([^"]*)" column of "([^"]*)" in table "([^"]*)" should be "([^"]*)"$/
   */
  public function theColumnOfInTableShouldBe($column_title, $value_in_row, $table_id, $content) {
    $page = $this->getSession()->getPage();
    $table_element = $page->find('css', "table#$table_id");

    $cell = self::findTableCellByColumTitleAndRowValue($table_element, $column_title, $value_in_row);
    $found = $cell->getText();
    if ($found != $content) {
      throw new \Exception("Found '$found' instead of '$content'.");
    }
  }

  /**
   * @Given /^the "([^"]*)" input should have the value "([^"]*)"$/
   */
  public function theInputShouldHaveTheValue($label, $value) {
    $page = $this->getSession()->getPage();
    $input = $page->find('xpath', "//label[.=\"$label \"]/../input");
    if (!$input) {
      throw new \Exception("An label with the value '$label' was not found.");
    }
    $found = $input->getValue();
    if ($found != $value) {
      throw new \Exception("Found '$found' instead of '$value'.");
    }
  }

  /**
   * @Then /^the "([^"]*)" checkbox in row containing "([^"]*)" in table "([^"]*)" should be unchecked$/
   */
  public function theCheckboxInRowContainingInTableShouldBeUnchecked($column_title, $value_in_row, $table_id) {
    $page = $this->getSession()->getPage();
    $table_element = $page->find('css', "table#$table_id");

    $cell = self::findTableCellByColumTitleAndRowValue($table_element, $column_title, $value_in_row);
    $checkbox = $cell->find('xpath', "//input[@type='checkbox']");
    if (!$checkbox) {
      throw new \Exception('No such checkbox found.');
    }
    if ($checkbox->getAttribute('checked')) {
      throw new \Exception('Checkbox is checked.');
    }
  }

  /**
   * @When /^I click the row of "([^"]*)"$/
   */
  public function iClickTheRowOf($value_in_row) {
    $page = $this->getSession()->getPage();
    $row = $page->find('xpath', "//td[.='$value_in_row']/..");
    if (!$row) {
      throw new \Exception("A row containing '$value_in_row' was not found.");
    }
    $row->click();
  }

  /**
   * @Then /^the following <row> should appear in the table "([^"]*)":$/
   */
  public function theFollowingRowShouldAppearInTheTable($table_id, TableNode $table) {
    $page = $this->getSession()->getPage();
    $table_element = $page->find('css', "table#$table_id");
    if (!$table_element) {
      throw new \Exception("Table '$table_id' was not found.");
    }

    $expectedRow = $table->getRow(0);

    // Search for the row in the table
    foreach ($table_element->findAll('css', 'tr') as $i => $row) {
      // Compare the given row to all table rows. If no exception is thrown it
      // means the row was found.
      try {
        $this->compareTableRow($row->findAll('css', 'td'), $expectedRow);
      }
      catch (\Exception $e) {
        // Try the next row.
        continue;
      }

      // Found the row.
      return;
    }

    throw new \Exception('Row not found.');
  }

  /**
   * @When /^I am on (a|the) "([^"]*)" page of the default "([^"]*)"(?: of "([^"]*)"|)$/
   */
  public function iAmOnThePageOfTheDefault($the, $page_name, $node_type, $company = 'Imanimo') {
    $company = strtolower($company);
    $nid = $this->sample_nodes[$company][$node_type];

    switch($page_name) {
      case 'Node view':
        $path = "node/$nid";
        break;

      case 'Add a production order':
        $path = "$company/node/add/production-order?field_season=$nid";
        break;

      case 'Season inventory':
        $path = "$company/season/$nid/inventory";
        break;

      case 'Season items':
        $path = "$company/season/$nid/items";
        break;

      case 'Season orders':
        $path = "$company/season/$nid/orders";
        break;

      case 'Season tasks':
        $path = "$company/season/$nid/tasks";
        break;

      case 'Season production orders':
        $path = "$company/season/$nid/production-orders";
        break;

      case 'Season line sheet':
        $path = "$company/season/$nid/line-sheet";
        break;

      case 'Production delivery':
        $path = "$company/production-order/$nid/delivery";
        break;

      default:
        throw new \Exception("Page '$page_name' not defined.");
    }

    return new Step\When("I am at \"$path\"");
  }

  /**
   * @When /^I am on the default "([^"]*)" page$/
   */
  public function iAmOnTheDefaultPage($node_type) {
    $company = 'imanimo';
    $nid = $this->sample_nodes[$company][$node_type];
    $path = $company . '/node/' . $nid;
    return new Step\When("I am at \"$path\"");
  }

  /**
   * @When /^I add an item variant titled "([^"]*)" to line sheet$/
   */
  public function iAddAnItemVariantTitledToLineSheet($title) {
    // Trace what flags have we flagged in the test.
    $this->flagged[] = array(
      'entity_id' => $this->getEntityId($title),
      'flag_name' => 'line_sheet',
    );

    // Add the item variant to the line sheet.
    return array(
      new Given('I am on a "item-variant" page titled "'. $title. '"'),
      new Given('I click "Add to line sheet"'),
    );
  }

  /**
   * Unflag used flags.
   *
   * @AfterScenario
   */
  public function cleanFlags($event) {
    if (empty($this->flagged)) {
      // No flags to unflag.
      return;
    }

    // Unflag every flagged flag.
    $account = user_load(1);
    foreach ($this->flagged as $flag) {
      $entity_id = $flag['entity_id'];
      $flag_name = $flag['flag_name'];

      flag('unflag', $flag_name, $entity_id, $account);
    }
    // Clean the flagged flags list.
    $this->flagged = array();
  }

  /**
   *
   * @Then /^I should see the following <contents>:$/
   */
  public function iShouldSeeTheFollowing($contents) {
    $steps = array();
    foreach ($contents as $row) {
      foreach ($row as $cell) {
        $steps[] = new Step\When('I should see "'. $cell . '"');
      }
    }
    return $steps;
  }

  /**
   * @Given /^I wait$/
   */
  public function iWait() {
    sleep(10);
  }
}
