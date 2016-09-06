<?php

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawDrupalContext implements SnippetAcceptingContext {

  protected $client;

  protected $verb;

  protected $endpoint;

  protected $headers = [];

  protected $request;

  protected $response;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->client = new \GuzzleHttp\Client(['base_uri' => 'http://127.0.0.1:8080/']);
  }

  /**
   * @Given I create a :verb request to :endpoint
   */
  public function iCreateARequestTo($verb, $endpoint) {
    $this->verb = $verb;
    $this->endpoint = $endpoint;
  }

  /**
   * @Given I set the header :header_name with the value :header_value
   */
  public function iSetTheHeaderWithTheValue($header_name, $header_value) {
    $this->headers[$header_name] = $header_value;
  }

  /**
   * @Given I set the headers :headers_array
   */
  public function iSetTheHeaders(array $headers_array) {
    if (!empty($headers_array)) {
      foreach ($headers_array as $header_name => $header_value) {
        $this->iSetTheHeaderWithTheValue($header_name, $header_value);
      }
    }
  }

  /**
   * @Given I execute the request
   */
  public function iExecuteTheRequest() {
    $this->request = new \GuzzleHttp\Psr7\Request($this->verb, $this->endpoint, $this->headers);
    $this->response = $this->client->send($this->request);
  }

  /**
   * @Then I should get a :response_code HTTP response code
   */
  public function iShouldGetAHttpResponseCode($response_code) {
    if ($this->response instanceof \GuzzleHttp\Psr7\Response) {
      $code = $this->response->getStatusCode();
      expect($code == $response_code);
    }
  }

}
