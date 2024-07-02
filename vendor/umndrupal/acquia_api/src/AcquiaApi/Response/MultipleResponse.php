<?php

namespace Umndrupal\acquia_api\Response;

use Umndrupal\acquia_api\Client\Client;
use InvalidArgumentException;

class MultipleResponse extends AcquiaResponse implements \Iterator
{
  protected $items;
  private $position;
  private $responseType;
  public function __construct($response, Client $client, $responseType)
  {
    $this->position = 0;
    $this->responseType = $responseType;
    $types = $this->getValidTypes();
    if (!in_array($responseType, $types)) {
      throw new InvalidArgumentException(
        'Requested type not one of ' . implode(', ', array_keys($types))
      );
    }
    parent::__construct($response, $client);
    $this->items = array_map(function ($item) use ($client, $responseType) {
      $reflectionClass = new \ReflectionClass('\\Umndrupal\\acquia_api\\Response\\' . $responseType);
      return $reflectionClass->newInstanceArgs([$item, $client]);
    }, $this->getEmbeddedItems());
  }

  protected function getValidTypes(): array
  {
    return [
      'Application',
      'Database',
      'Environment',
      'Cron',
      'Backup',
    ];
  }

  public function current():mixed
  {
    return $this->items[$this->position];
  }

  public function next():void
  {
    $this->position += 1;
  }

  public function key():int
  {
    return $this->position;
  }

  public function valid():bool
  {
    return (isset($this->items[$this->position]));
  }

  public function rewind():void
  {
    $this->position = 0;
  }

  public function __toString():string
  {
    $count = count($this->items);
    return "This is a collection of {$count} {$this->responseType} items.\n";
  }
}
