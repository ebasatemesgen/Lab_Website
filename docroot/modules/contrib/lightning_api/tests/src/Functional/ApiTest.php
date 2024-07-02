<?php

namespace Drupal\Tests\lightning_api\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\consumers\Entity\Consumer;
use Drupal\Core\Url;
use Drupal\lightning_api\Form\OAuthKeyForm;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\simple_oauth\Functional\RequestHelperTrait;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;

/**
 * Tests that OAuth and JSON:API authenticate and authorize entity operations.
 *
 * @group lightning_api
 * @group headless
 * @group orca_public
 *
 * @requires module simple_oauth
 */
class ApiTest extends BrowserTestBase {

  use RequestHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'lightning_api',
    'node',
    'path',
    'simple_oauth',
    'simple_oauth_test',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Allow writing via JSON:API.
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save();

    // Log in as an administrator so that we can generate security keys for
    // OAuth.
    $account = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($account);

    // Visiting Simple OAuth's token settings form should display a warning
    // that no keys exist yet, and invite the user to use our generator form.
    $this->drupalGet('/admin/config/people/simple_oauth');
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('You may wish to generate a key pair for OAuth authentication.');
    $this->clickLink('generate a key pair');

    $page = $this->getSession()->getPage();
    $page->fillField('Destination', $this->container->get('file_system')->getTempDirectory());
    $page->fillField('Private key name', 'private.key');
    $page->fillField('Public key name', 'public.key');
    $conf = getenv('OPENSSL_CONF');
    if ($conf) {
      $page->fillField('OpenSSL configuration file', $conf);
    }
    $page->pressButton('Generate keys');
    $assert_session->pageTextContains('A key pair was generated successfully.');

    // Check that the keys actually exist and have the correct permissions.
    $config = $this->config('simple_oauth.settings');
    foreach (['public', 'private'] as $which) {
      $path = $config->get("{$which}_key");
      $this->assertNotEmpty($path);
      $this->assertFileExists($path);
      $this->assertSame(OAuthKeyForm::KEY_PERMISSIONS, fileperms($path) & 0777);
    }

    $this->drupalLogout();
  }

  /**
   * {@inheritdoc}
   */
  protected function createContentType(array $values = []) {
    $node_type = $this->drupalCreateContentType($values);
    // The router needs to be rebuilt in order for the new content type to be
    // available to JSON:API.
    $this->container->get('router.builder')->rebuild();
    return $node_type;
  }

  /**
   * Creates an API user with all privileges for a single content type.
   *
   * @param string $node_type
   *   The content type ID.
   *
   * @return string
   *   The API access token.
   */
  private function getCreator($node_type) {
    return $this->createApiUser([
      "access content",
      "bypass node access",
      "create $node_type content",
      "create url aliases",
      "delete $node_type revisions",
      "edit any $node_type content",
      "edit own $node_type content",
      "revert $node_type revisions",
      "view all revisions",
      "view own unpublished content",
      "view $node_type revisions",
    ]);
  }

  /**
   * Creates a user account with privileged API access.
   *
   * @see ::createUser()
   *
   * @return string
   *   The user's access token.
   */
  private function createApiUser(array $permissions = [], $name = NULL, $admin = FALSE) {
    // We should not be logged in right now.
    $this->assertEmpty($this->loggedInUser);

    $permissions[] = 'grant simple_oauth codes';
    $account = $this->createUser($permissions, $name, $admin);
    $this->drupalLogin($account);

    $roles = $account->getRoles(TRUE);
    $secret = $this->randomString(32);

    $redirect_url = Url::fromRoute('oauth2_token.test_token')
      ->setAbsolute()
      ->toString();

    $client = Consumer::create([
      'label' => 'API Test Client',
      'secret' => $secret,
      'confidential' => TRUE,
      'user_id' => $account->id(),
      'roles' => reset($roles),
      'client_id' => $this->randomMachineName(16),
      'grant_types' => ['authorization_code'],
      'redirect' => $redirect_url,
    ]);
    $client->save();

    // Ask for an access code, which we can swap for a token.
    $url = Url::fromRoute('oauth2_token.authorize');
    $this->drupalGet($url, [
      'query' => [
        'response_type' => 'code',
        'client_id' => $client->getClientId(),
        'client_secret' => $secret,
        'redirect_uri' => $redirect_url,
      ],
    ]);
    $session = $this->getSession();
    $session->getPage()->pressButton('Grant');
    // We should now have an authorization code.
    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);
    $parsed_url = parse_url($session->getCurrentUrl());
    $this->assertArrayHasKey('query', $parsed_url);
    $query = [];
    parse_str($parsed_url['query'], $query);
    $this->assertNotEmpty($query['code']);

    // Exchange the authorization code for a shiny token.
    $url = Url::fromRoute('oauth2_token.token');
    $response = $this->post($url, [
      'grant_type' => 'authorization_code',
      'client_id' => $client->getClientId(),
      'client_secret' => $secret,
      'code' => $query['code'],
      'scope' => implode(' ', $roles),
      'redirect_uri' => $redirect_url,
    ]);
    $this->assertGreaterThanOrEqual(200, $response->getStatusCode());
    $this->assertLessThan(300, $response->getStatusCode());
    $body = $this->decodeResponse($response);
    $this->assertNotEmpty($body['access_token']);

    return $body['access_token'];
  }

  /**
   * Tests create, read, and update of content entities via the API.
   */
  public function testEntities() {
    $access_token = $this->createApiUser(['administer taxonomy'], NULL, TRUE);

    // Create a taxonomy vocabulary. This cannot currently be done over the API
    // because jsonapi doesn't really support it, and will not be able to
    // properly support it until config entities can be internally validated
    // and access controlled outside of the UI.
    $vocabulary = Vocabulary::create([
      'name' => "I'm a vocab",
      'vid' => 'im_a_vocab',
      'status' => TRUE,
    ]);
    $vocabulary->save();

    $endpoint = '/jsonapi/taxonomy_vocabulary/taxonomy_vocabulary/' . $vocabulary->uuid();

    // Read the newly created vocabulary.
    $response = $this->request($endpoint, 'get', $access_token);
    $body = $this->decodeResponse($response);
    $this->assertSame($vocabulary->label(), $body['data']['attributes']['name']);

    $vocabulary->set('name', 'Still a vocab, just a different title');
    $vocabulary->save();
    // The router needs to be rebuilt in order for the new vocabulary to be
    // available to JSON:API.
    $this->container->get('router.builder')->rebuild();

    // Read the updated vocabulary.
    $response = $this->request($endpoint, 'get', $access_token);
    $body = $this->decodeResponse($response);
    $this->assertSame($vocabulary->label(), $body['data']['attributes']['name']);

    // Assert that the newly created vocabulary's endpoint is reachable.
    $response = $this->request('/jsonapi/taxonomy_term/im_a_vocab');
    $this->assertSame(200, $response->getStatusCode());

    $name = 'zebra';
    $term_uuid = $this->container->get('uuid')->generate();
    $endpoint = '/jsonapi/taxonomy_term/im_a_vocab/' . $term_uuid;

    // Create a taxonomy term (content entity).
    $this->request('/jsonapi/taxonomy_term/im_a_vocab', 'post', $access_token, [
      'data' => [
        'type' => 'taxonomy_term--im_a_vocab',
        'id' => $term_uuid,
        'attributes' => [
          'name' => $name,
          'uuid' => $term_uuid,
        ],
        'relationships' => [
          'vid' => [
            'data' => [
              'type' => 'taxonomy_vocabulary--taxonomy_vocabulary',
              'id' => $vocabulary->uuid(),
            ],
          ],
        ],
      ],
    ]);

    // Read the taxonomy term.
    $response = $this->request($endpoint, 'get', $access_token);
    $body = $this->decodeResponse($response);
    $this->assertSame($name, $body['data']['attributes']['name']);

    $new_name = 'squid';

    // Update the taxonomy term.
    $this->request($endpoint, 'patch', $access_token, [
      'data' => [
        'type' => 'taxonomy_term--im_a_vocab',
        'id' => $term_uuid,
        'attributes' => [
          'name' => $new_name,
        ],
      ],
    ]);

    // Read the updated taxonomy term.
    $response = $this->request($endpoint, 'get', $access_token);
    $body = $this->decodeResponse($response);
    $this->assertSame($new_name, $body['data']['attributes']['name']);
  }

  /**
   * Tests Getting data as anon and authenticated user.
   */
  public function testAllowed() {
    $this->createContentType(['type' => 'page']);
    // Create some sample content for testing. One published and one unpublished
    // basic page.
    $published_node = $this->drupalCreateNode();
    $unpublished_node = $published_node->createDuplicate()->setUnpublished();
    $unpublished_node->save();

    // Get data that is available anonymously.
    $response = $this->request('/jsonapi/node/page/' . $published_node->uuid());
    $this->assertSame(200, $response->getStatusCode());
    $body = $this->decodeResponse($response);
    $this->assertSame($published_node->getTitle(), $body['data']['attributes']['title']);

    // Get data that requires authentication.
    $access_token = $this->getCreator('page');
    $response = $this->request('/jsonapi/node/page/' . $unpublished_node->uuid(), 'get', $access_token);
    $this->assertSame(200, $response->getStatusCode());
    $body = $this->decodeResponse($response);
    $this->assertSame($unpublished_node->getTitle(), $body['data']['attributes']['title']);

    // Post new content that requires authentication.
    $count = (int) \Drupal::entityQuery('node')->accessCheck(TRUE)->count()->execute();
    $this->request('/jsonapi/node/page', 'post', $access_token, [
      'data' => [
        'type' => 'node--page',
        'attributes' => [
          'title' => 'With my own two hands',
        ],
      ],
    ]);
    $this->assertSame(++$count, (int) \Drupal::entityQuery('node')->accessCheck(TRUE)->count()->execute());
  }

  /**
   * Tests access to unauthorized data is denied, regardless of authentication.
   */
  public function testForbidden() {
    $this->createContentType(['type' => 'page']);

    // Cannot get unauthorized data (not in role/scope) even when authenticated.
    $response = $this->request('/jsonapi/user_role/user_role', 'get', $this->getCreator('page'));
    $body = $this->decodeResponse($response);
    $this->assertSame('array', gettype($body['meta']['omitted']['links']));
    $this->assertNotEmpty($body['meta']['omitted']['links']);
    unset($body['meta']['omitted']['links']['help']);

    foreach ($body['meta']['omitted']['links'] as $link) {
      // This user/client should not have access to any of the roles' data.
      $this->assertSame(
        "The current user is not allowed to GET the selected resource. The 'administer permissions' permission is required.",
        $link['meta']['detail']
      );
    }

    // Cannot get unauthorized data anonymously.
    $unpublished_node = $this->drupalCreateNode()->setUnpublished();
    $unpublished_node->save();
    $url = $this->buildUrl('/jsonapi/node/page/' . $unpublished_node->uuid());

    // Unlike the roles test which requests a list, JSON API sends a 403 status
    // code when requesting a specific unauthorized resource instead of list.
    $this->expectException(ClientException::class);
    $this->expectExceptionMessage("Client error: `GET $url` resulted in a `403 Forbidden`");
    $this->container->get('http_client')->get($url);
  }

  /**
   * Makes a request to the API using an optional OAuth token.
   *
   * @param string $endpoint
   *   Path to the API endpoint.
   * @param string $method
   *   The RESTful verb.
   * @param string $token
   *   (optional) A valid OAuth token to send as an Authorization header with
   *   the request.
   * @param array $data
   *   (optional) Additional JSON data to send with the request.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response from the request.
   */
  private function request($endpoint, $method = 'get', $token = NULL, array $data = NULL) {
    $options = [];
    if ($token) {
      $options = [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
          'Content-Type' => 'application/vnd.api+json',
        ],
      ];
    }
    if ($data) {
      $options['json'] = $data;
    }

    $url = $this->buildUrl($endpoint);
    return $this->getHttpClient()->$method($url, $options);
  }

  /**
   * Decodes a JSON response from the server.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response object.
   *
   * @return mixed
   *   The decoded response data. If the JSON parser raises an error, the test
   *   will fail, with the bad input as the failure message.
   */
  private function decodeResponse(ResponseInterface $response) {
    $body = (string) $response->getBody();

    $data = Json::decode($body);
    if (json_last_error() === JSON_ERROR_NONE) {
      return $data;
    }
    else {
      $this->fail($body);
    }
  }

}
