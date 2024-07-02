<?php

namespace Umndrupal\acquia_api\Response;

use Umndrupal\acquia_api\Client\Client;

class Cron extends AcquiaResponse
{

  /**
   * @var string
   */
  protected $id;

  /**
   * @var array
   */
  protected $server;

  /**
   * @var string
   */
  protected $command;

  /**
   * @var string
   */
  protected $minute;

  /**
   * @var string
   */
  protected $hour;

  /**
   * @var string
   */
  protected $day_month;

  /**
   * @var string
   */
  protected $month;

  /**
   * @var string
   */
  protected $day_week;

  /**
   * @var string
   */
  protected $label;

  /**
   * @var array
   */
  protected $flags;
  /**
   * @var array
   */
  protected $environment;

  /**
   * @inheritDoc
   */
  public function __construct($response, Client $client)
  {
    parent::__construct($response, $client);
    $properties = [
      'id',
      'server',
      'command',
      'minute',
      'hour',
      'day_month',
      'month',
      'day_week',
      'label',
      'flags',
      'environment',
    ];
    foreach ($properties as $prop) {
      if (!empty($this->decodedResponse[$prop])) {
        $this->{$prop} = $this->decodedResponse[$prop];
      } else {
        $this->{$prop} = null;
      }
    }
  }

  /**
   * @return string
   */
  public function getId(): string
  {
    return $this->id;
  }

  /**
   * @return string
   */
  public function getCommand(): string
  {
    return $this->command;
  }

  public function enabled(): bool
  {
    return $this->flags['enabled'];
  }

  public function getFrequency(): string
  {
    return "{$this->minute} {$this->hour} {$this->day_month} {$this->month} {$this->day_week}";
  }

  public function enableCron()
  {
    $uri = "environments/{$this->environment['id']}/crons/{$this->id}/actions/enable";
    $request = $this->client->postRequest($uri);
    return new AcquiaResponse($request, $this->client);
  }

  public function disableCron()
  {
    $uri = "environments/{$this->environment['id']}/crons/{$this->id}/actions/disable";
    $request = $this->client->postRequest($uri);
    return new AcquiaResponse($request, $this->client);
  }

  public function __toString()
  {
    return "Cron command: {$this->command}\nFrequency: {$this->getFrequency()}";
  }
}
