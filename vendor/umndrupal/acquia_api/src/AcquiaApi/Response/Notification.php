<?php

namespace Umndrupal\acquia_api\Response;

use Umndrupal\acquia_api\Client\Client;

class Notification extends AcquiaResponse {

  /**
   * @var string
   */
  protected $notification_id;

  public function __construct($response, Client $client) {
    parent::__construct($response, $client);
    $properties = [
      'notification_id',
    ];
    foreach ($properties as $prop) {
      if (!empty($this->decodedResponse[$prop])) {
        $this->$prop = $this->decodedResponse[$prop];
      } else {
        $this->$prop = null;
      }
    }
  }

  public function getStatus() {
    $uri = "notifications/{$this->notification_id}";
    $response = $this->client->getRequest($uri);
    return json_decode($response->getBody()->getContents());
  }

  public function getNotificationId() {
    return $this->notification_id;
  }
}
