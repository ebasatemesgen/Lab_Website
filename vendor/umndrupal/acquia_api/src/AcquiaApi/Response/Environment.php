<?php

namespace Umndrupal\acquia_api\Response;

use Umndrupal\acquia_api\Client\Client;

class Environment extends AcquiaResponse
{

  /**
   * @var string
   */
  protected $id;

  /**
   * @var string
   */
  protected $label;

  /**
   * @var string
   */
  protected $name;

  /**
   * @var array
   */
  protected $application;

  /**
   * @var array
   */
  protected $domains;

  /**
   * @var string
   */
  protected $active_domain;

  /**
   * @var string
   */
  protected $default_domain;

  /**
   * @var string
   */
  protected $image_url;

  /**
   * @var string
   */
  protected $ssh_url;

  /**
   * @var string
   */
  protected $region;

  /**
   * @var string
   */
  protected $status;

  /**
   * @var string
   */
  protected $type;

  /**
   * @var string
   */
  protected $size;

  /**
   * @var int
   */
  protected $weight;

  /**
   * @var array
   */
  protected $vcs;

  /**
   * @var array
   */
  protected $insight;

  /**
   * @var array
   */
  protected $flags;

  /**
   * @var array
   */
  protected $configuration;

  /**
   * @var array
   */
  protected $artifact;

  /**
   * Environment constructor.
   *
   * @inheritDoc
   */
  public function __construct($response, Client $client)
  {
    parent::__construct($response, $client);
    $properties = [
      'id',
      'label',
      'name',
      'application',
      'domains',
      'active_domain',
      'default_domain',
      'image_url',
      'ssh_url',
      'region',
      'status',
      'type',
      'size',
      'weight',
      'vcs',
      'flags',
      'configuration',
      'artifact',
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
  public function getName(): string
  {
    return $this->name;
  }

  /**
   * @return string
   */
  public function getApplication(): string
  {
    return $this->application;
  }

  public function getCrons()
  {
    $uri = "environments/{$this->id}/crons";
    $response = $this->client->getRequest($uri);
    return new MultipleResponse($response, $this->client, 'Cron');
  }

  public function databases($databaseName = '')
  {
    $uri = "environments/{$this->getId()}/databases/" . $databaseName;
    $response = $this->client->getRequest($uri);
    if (!empty($databaseName)) {
      return new Database($response, $this->client);
    }
    else {
      return new MultipleResponse($response, $this->client, 'Database');
    }
  }

  public function copyDatabaseToEnv($databaseName, Environment $sourceEnvironment) {
    $uri = "environments/{$this->getId()}/databases";
    $options = [
      'json' => [
        'name' => $databaseName,
        'source' => $sourceEnvironment->getId(),
      ],
    ];
    $response = $this->client->postRequest($uri, $options);
    return new AcquiaResponse($response, $this->client);
  }

  /**
   * Gets list of domains associated with this environment.
   *
   * @return array
   */
  public function getDomains()
  {
    if (count($this->domains) === 0) {
      $this->setDomains();
    }
    return $this->domains;
  }

  protected function setDomains()
  {
    $uri = "environments/{$this->id}/domains";
    $response = $this->client->getRequest($uri);
    $acquia_response = new AcquiaResponse($response, $this->client);
    $domains = array_map(function ($i) {
      return $i['hostname'];
    }, $acquia_response->getEmbeddedItems());
    $this->domains = $domains;
  }

  /**
   * @param string $hostname
   *
   * @return \Umndrupal\acquia_api\Response\AcquiaResponse
   */
  public function addDomain(string $hostname)
  {
    // unset domains since we're changing them
    $this->domains = [];
    $uri = "environments/{$this->id}/domains";
    $options = [
      'json' => [
        'hostname' => $hostname
      ],
    ];
    $response = $this->client->postRequest($uri, $options);
    return new AcquiaResponse($response, $this->client);
  }

  /**
   * @param string $hostname
   *
   * @return \Umndrupal\acquia_api\Response\AcquiaResponse
   */
  public function removeDomain(string $hostname)
  {
    // unset domains since we're changing them
    $this->domains = [];
    $uri = "environments/{$this->id}/domains/{$hostname}";
    $response = $this->client->deleteRequest($uri);
    return new AcquiaResponse($response, $this->client);
  }

  /**
   * @param string[] $domains
   *
   * @return \Umndrupal\acquia_api\Response\AcquiaResponse
   */
  public function clearVarnishOnDomains(array $domains)
  {
    $uri = "environments/{$this->id}/domains/actions/clear-varnish";
    $options = [
      'json' => [
        'domains' => $domains,
      ],
    ];
    $response = $this->client->postRequest($uri, $options);
    return new AcquiaResponse($response, $this->client);
  }

  /**
   * Get information about code deployed to environment.
   *
   * @return array
   */
  public function getVcs()
  {
    return $this->vcs;
  }

  public function __toString()
  {
    $output = "Environment {$this->getName()} with ID {$this->getId()} and label {$this->label}.\n";
    return $output;
  }
}
