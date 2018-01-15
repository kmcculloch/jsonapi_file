<?php

namespace Drupal\Tests\jsonapi_file\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\Tests\jsonapi\Functional\JsonApiFunctionalTestBase;

/**
 * JSON API File functional test suite.
 *
 * @group jsonapi_file
 */
class JsonApiFileFunctionalTest extends JsonApiFunctionalTestBase {

  public static $modules = [
    'basic_auth',
    'jsonapi_file',
    'serialization',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->user = $this->drupalCreateUser([
      'create files',
    ]);
  }

  /**
   * Test authenticated upload.
   */
  public function testAuthenticatedUpload() {
    $uri = 'public://test1.txt';
    $body = $this->getBody($uri);
    $options = $this->getRequestOptions($body);
    $response = $this->postDocument($options);

    $this->assertEquals(201, $response->getStatusCode());
    $this->assertTrue($this->uploadedFileExists($uri));
  }

  /**
   * Test anonymous upload.
   */
  public function testAnonymousUpload() {
    $uri = 'public://test2.txt';
    $body = $this->getBody($uri);
    $options = $this->getRequestOptions($body);
    unset($options['auth']);
    $response = $this->postDocument($options);

    $this->assertEquals(403, $response->getStatusCode());
    $this->assertFalse($this->uploadedFileExists($uri));
  }

  /**
   * Test dangerous extension rewriting.
   */
  public function testDangerousExtension() {
    $uri = 'public://test3.php';
    $safe_uri = 'public://test3.php.txt';
    $body = $this->getBody($uri);
    $options = $this->getRequestOptions($body);
    $response = $this->postDocument($options);

    $this->assertEquals(201, $response->getStatusCode());
    $this->assertFalse($this->uploadedFileExists($uri));
    $this->assertTrue($this->uploadedFileExists($safe_uri));
  }

  /**
   * Get a default body array.
   */
  protected function getBody($uri) {
    return [
      'data' => [
        'type' => 'file--document',
        'attributes' => [
          'data' => 'SGV5LCBpdCB3b3JrcyE=',
          'uri' => $uri,
        ],
      ],
    ];
  }

  /**
   * Get default request options.
   */
  protected function getRequestOptions($body) {
    return [
      'body' => Json::encode($body),
      'auth' => [$this->user->getUsername(), $this->user->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ];
  }

  /**
   * Post a document.
   */
  protected function postDocument($options) {
    $url = Url::fromRoute('jsonapi.file--document.collection');

    return $this->request('POST', $url, $options);
  }

  /**
   * Check to see whether the uploaded file exists on the file system.
   */
  protected function uploadedFileExists($uri) {
    $path = $this->container->get('file_system')->realpath($uri);
    return file_exists($path);
  }

}
