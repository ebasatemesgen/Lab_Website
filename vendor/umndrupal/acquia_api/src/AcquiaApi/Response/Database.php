<?php

namespace Umndrupal\acquia_api\Response;

use Umndrupal\acquia_api\Client\Client;

class Database extends AcquiaResponse
{
  protected $id;
  /**
   * @var string
   */
  protected $name;

  /**
   * @var string
   */
  protected $environmentId;

  /**
   * @var string
   */
  protected $applicationId;

  /**
   * @var \Umndrupal\acquia_api\Client\Client
   */
  protected $client;

  /**
   * Database constructor.
   */
  public function __construct($response, Client $client)
  {
    parent::__construct($response, $client);
    $this->id = $this->decodedResponse['id'];
    $this->name = $this->decodedResponse['name'];
    $this->client = $client;
    $this->environmentId = $this->decodedResponse['environment']['id'];
    $this->applicationId = '';
  }

  /**
   * @return string
   */
  public function getEnvironmentId(): string
  {
    return $this->environmentId;
  }

  /**
   * @return string
   */
  public function getApplicationId(): string
  {
    return $this->applicationId;
  }

  /**
   * @return string
   */
  public function getName(): string
  {
    return $this->name;
  }

  public function backups()
  {
    $uri = "environments/{$this->getEnvironmentId()}/databases/{$this->name}/backups";
    $response = $this->client->getRequest($uri);
    return new MultipleResponse($response, $this->client, 'Backup');
  }

  public function createBackup()
  {
    $uri = "environments/{$this->getEnvironmentId()}/databases/{$this->name}/backups";
    $response = $this->client->postRequest($uri);
    return new AcquiaResponse($response, $this->client);
  }
}
