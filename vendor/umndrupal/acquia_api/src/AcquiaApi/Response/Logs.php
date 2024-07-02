<?php

namespace Umndrupal\acquia_api\Response;

use Umndrupal\acquia_api\Client\Client;

class Logs extends AcquiaResponse {
  /**
   * @var string
   */
  protected $id;

  /**
   * @var string
   */
  protected $log_type;

  /**
   * @var string
   */
  protected $start_time;

  /**
   * @var string
   */
  protected $end_time;


  public function __construct($response, Client $client)
  {
    parent::__construct($response, $client);
    $properties = [
      'id',
      'log_type',
      'start_time',
      'end_time',
    ];
    foreach ($properties as $prop) {
      if (!empty($this->decodedResponse[$prop])) {
        $this->$prop = $this->decodedResponse[$prop];
      } else {
        $this->$prop = null;
      }
    }
  }

  public function getEnvironment(): string
  {
    return $this->id;
  }

  public function getLogType(): string
  {
    return $this->log_type;
  }

  public function getStartTime(): string
  {
    return $this->start_time;
  }

  public function getEndTime(): string
  {
    return $this->end_time;
  }

  public function setTime($time_constraints) {
    $this->start_time = $time_constraints['start'];
    $this->end_time = $time_constraints['end'];
  }

  public function setLogInfo($log_types) {
    $this->id = $log_types['id'];
    $this->log_type = $log_types['log_type'];
    return TRUE;
  }

  public function generateLogBackup() {
    $start = $this->start_time;
    $end = $this->end_time;
    if (isset($start) && isset($end)) {
      $body = [
        'from' => $start,
        'to' => $end,
      ];
      $uri = "environments/{$this->id}/logs/{$this->log_type}";
      $options = [
        'headers' => [
          'Content-Type' => 'application/x-www-form-urlencoded',
        ],
        'form_params' => $body,
      ];
      $response = $this->client->postRequest($uri, $options);
    } else {
      $uri = "environments/{$this->id}/logs/{$this->log_type}";
      $response = $this->client->postRequest($uri);
    }

    return new AcquiaResponse($response, $this->client);
  }

  public function downloadLogs() {
    $uri = "environments/{$this->id}/logs/{$this->log_type}";
    $response = $this->client->getRequest($uri);
    return $response->getBody()->getContents();

  }

}
