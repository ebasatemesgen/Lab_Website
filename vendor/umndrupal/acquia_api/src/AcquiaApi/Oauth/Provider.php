<?php

namespace Umndrupal\acquia_api\Oauth;

use GuzzleHttp\Psr7\Request;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;

class Provider extends GenericProvider
{

  public static $token_url = 'https://accounts.acquia.com/api/auth/oauth/token';
  public static $api_base = 'https://cloud.acquia.com/api/';
  public $access_token;

  /**
   * Provider constructor.
   *
   * @param $id
   * @param $secret
   */
  public function __construct($id, $secret)
  {

    parent::__construct([
      'clientId' => $id,
      'clientSecret' => $secret,
      'urlAuthorize' => 'https://cloud.acquia.com/api/authorize',
      'urlResourceOwnerDetails' => 'https://cloud.acquia.com/api/resource',
      'urlAccessToken' => self::$token_url,
    ]);
  }

  private function getToken()
  {
    try {

      // Try to get an access token using the client credentials grant.
      $accessToken = $this->getAccessToken('client_credentials');
      $this->access_token = $accessToken;
    } catch (IdentityProviderException $e) {

      // Failed to get the access token
      exit($e->getMessage());
    }
  }

  private function refreshToken()
  {
    try {
      $token = $this->getAccessToken('refresh_token', [
        'refresh_token' => $this->access_token->getRefreshToken()
      ]);

      $this->access_token = $token;
    } catch (IdentityProviderException $e) {

      // Failed to refresh the access token
      exit($e->getMessage());
    }
  }

  public function request($method, $uri, array $options = [])
  {
    if (!$this->access_token instanceof AccessToken) {
      $this->getToken();
    } else if ($this->access_token->hasExpired()) {
      // Initial token request does not appear to supply refresh token, but leave
      // the call to refreshToken() here in case that changes.
      if (is_null($this->access_token->getRefreshToken())) {
        $this->getToken();
      } else {
        $this->refreshToken();
      }
    }

    return $this->getAuthenticatedRequest($method, $uri, $this->access_token, $options);
  }
}
