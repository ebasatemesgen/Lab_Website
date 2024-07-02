<?php

namespace Umndrupal\acquia_api\Client;

use Psr\Http\Message\ResponseInterface;
use Umndrupal\acquia_api\Response\Application;
use Umndrupal\acquia_api\Response\Environment;
use Umndrupal\acquia_api\Response\MultipleResponse;
use Umndrupal\acquia_api\Response\Logs;
use Umndrupal\acquia_api\Response\Notification;
use GuzzleHttp\Client as GClient;
use Umndrupal\acquia_api\Oauth\Provider;
use Psr\Http\Message\RequestInterface;

interface ClientInterface
{
  /**
   * Sends a PSR-7 request and returns a PSR-7 response.
   *
   * @param RequestInterface $request
   *
   * @return ResponseInterface
   *
   * @throws \Psr\Http\Client\ClientExceptionInterface If an error happens while processing the request.
   */
  public function sendRequest(RequestInterface $request): ResponseInterface;
}

class Client extends GClient
{

  /**
   * @var Provider
   */
  private $provider;

  public function __construct($key, $secret, array $config = [])
  {
    $config['base_uri'] = Provider::$api_base;
    $this->provider = new Provider($key, $secret);
    parent::__construct($config);
  }

  public function applications($applicationId = '')
  {
    $uri = 'applications/' . $applicationId;
      $response = $this->getRequest($uri);
    if (!empty($applicationId)) {
      return new Application($response, $this);
    } else {
      return new MultipleResponse($response, $this, 'Application');
    }
  }

  /**
   * @param string $environmentId
   */
  public function environment($environmentId)
  {
    $uri = 'environments/' . $environmentId;
    $response = $this->getRequest($uri);
    return new Environment($response, $this);
  }

  public function logs($log_types) {
    return new Logs($log_types, $this);
  }

  public function notification($notification_id) {
    return new Notification($notification_id, $this);
  }

  public function getRequest($uri, $options = [])
  {
    $request = $this->provider->request('GET', $uri);
    return $this->sendRequest($request, $options);
  }

  public function postRequest($uri, array $options = [])
  {
    $request = $this->provider->request('POST', $uri, $options);
    //    $test = $this->request('POST', $uri, $options);
    return $this->sendRequest($request, $options);
  }

  public function deleteRequest($uri)
  {
    $request = $this->provider->request('DELETE', $uri);
    return $this->sendRequest($request);
  }

  /**
   * @param \Psr\Http\Message\RequestInterface $request
   *
   * @return mixed|\Psr\Http\Message\ResponseInterface
   */
  public function sendRequest(RequestInterface $request, array $options = []): ResponseInterface
  {
    return $this->send($request, $options);
  }

}
