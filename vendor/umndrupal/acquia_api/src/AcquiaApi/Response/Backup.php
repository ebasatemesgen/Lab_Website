<?php

namespace Umndrupal\acquia_api\Response;

use Umndrupal\acquia_api\Client\Client;

class Backup extends AcquiaResponse
{

  /**
   * @var integer
   */
  protected $id;

  /**
   * @var array
   */
  protected $database;

  /**
   * @var array
   */
  protected $environment;

  /**
   * @var string
   */
  protected $type;

  /**
   * @var string
   */
  protected $started_at;

  /**
   * @var string
   */
  protected $completed_at;

  public function __construct($response, Client $client)
  {
    parent::__construct($response, $client);
    $properties = [
      'id',
      'database',
      'environment',
      'type',
      'started_at',
      'completed_at',
    ];
    foreach ($properties as $prop) {
      if (!empty($this->decodedResponse[$prop])) {
        $this->{$prop} = $this->decodedResponse[$prop];
      } else {
        $this->{$prop} = null;
      }
    }
  }

  public function download()
  {
    $uri = "environments/{$this->environment['id']}/databases/{$this->database['name']}/backups/{$this->id}/actions/download";
    return $this->client->getRequest($uri);
  }

  public function delete()
  {
    $uri = "environments/{$this->environment['id']}/databases/{$this->database['name']}/backups/{$this->id}";
    $response = $this->client->deleteRequest($uri);
    return new AcquiaResponse($response, $this->client);
  }

  /**
   * @return int
   */
  public function getId(): int
  {
    return $this->id;
  }

  /**
   * @return bool
   */
  public function daily(): bool
  {
    return $this->type === 'daily';
  }

  /**
   * @return bool
   */
  public function onDemand(): bool
  {
    return $this->type === 'ondemand';
  }

  /**
   * @return string
   */
  public function getCompleted()
  {
    return new \DateTime();
  }
}
