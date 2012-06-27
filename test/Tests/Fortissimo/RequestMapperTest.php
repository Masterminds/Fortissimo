<?php
/**
 * Unit tests for the SimpleFortissimoCommand class.
 */
namespace Fortissimo\Tests;


require_once 'TestCase.php';

/**
 * 
 */
class RequestMapperTest extends TestCase {
  public function setup() {

    $_SERVER['HTTPS'] = 'On';
    $_SERVER['SERVER_PORT'] = 8080;
    $_SERVER['REQUEST_URI'] = '/foo/bar';
    $_SERVER['SCRIPT_NAME'] = '/foo/index.php';
    $_SERVER['HTTP_HOST'] = 'www.example.com';

  }

  public function mapper() {
    return new \Fortissimo\RequestMapper(NULL, NULL, NULL);
  }

  public function testBasePath() {
    $res = $this->mapper()->basePath();

    $this->assertEquals('/foo', $res);
  }
  public function testLocalPath() {
    $res = $this->mapper()->localPath();

    $this->assertEquals('/bar', $res);
  }
  public function testBaseURL() {
    $res = $this->mapper()->baseURL();

    $this->assertEquals('https://www.example.com:8080/foo', $res);
  }
}
