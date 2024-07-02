<?php

namespace Umndrupal\acquia_api\Response;

use Umndrupal\acquia_api\Client\Client;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class AcquiaResponse
{
  protected $serializer;
  protected $links;
  protected $embedded;
  protected $decodedResponse;
  protected $client;

  /**
   * AcquiaResponse constructor.
   *
   * @param array|string|\GuzzleHttp\Psr7\Response $response
   * @param \Umndrupal\acquia_api\Client\Client $client
   */
  public function __construct($response, Client $client)
  {
    $encoders = [new JsonEncoder()];
    $normalizers = [new ObjectNormalizer()];
    $this->serializer = new Serializer($normalizers, $encoders);
    if (is_array($response)) {
      $this->decodedResponse = $response;
    } else {
      if ($response instanceof \GuzzleHttp\Psr7\Response) {
        $responseToDecode = $response->getBody()->getContents();
      } else {
        $responseToDecode = $response;
      }
      $this->decodedResponse = $this->serializer->decode($responseToDecode, 'json');
    }
    $this->links = array_key_exists('_links', $this->decodedResponse) ? $this->decodedResponse['_links'] : NULL;
    $this->embedded = array_key_exists('_embedded', $this->decodedResponse) ? $this->decodedResponse['_embedded'] : NULL;
    $this->client = $client;
  }

  public function __toString()
  {
    $links = print_r($this->links, TRUE);
    echo "Links are\n";
    echo $links;
  }

  public function getEmbeddedItems(): array
  {
    return !is_null($this->embedded) ? $this->embedded['items'] : [];
  }

  public function getResponse(): array
  {
    return $this->decodedResponse;
  }

  public function getLinks(): array
  {
    return !empty($this->links) ? $this->links : [];
  }
}
