<?php
/**
 * Model for storing OAuth credentials used for API requests and interaction
 * with the OAuth authorization server.  
 *
 * Copyright 2012 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Model for storing OAuth credentials used for API requests and interaction
 * with the OAuth authorization server.  Only the access token is ever sent
 * directly to API servers.  The refresh token, client ID and client secret
 * are sent to the OAuth authorization server to obtain access tokens.
 *
 * @author Ryan Boyd <rboyd@google.com>
 */
class OauthCredentials {

  /**
   * Access token obtained when exchanging authorization code with the OAuth
   * provider.
   */
  public $accessToken = '';

  /**
   * Time OAuth token was created.
   */
  public $created = '';

  /**
   * Time in seconds after creation when token will expire
   */
  public $expiresIn = '';

  /**
   * Refresh token obtained when exchanging the authorization code the first
   * time, when a user explicitly approves access.  When the OAuth request is
   * auto-approved by Google due to a previous grant, a refresh token is not
   * returned.  Instead, the refresh token is looked up in the application's 
   * database, indexed based on the user ID returned from the Google UserInfo
   * service.
   */
  public $refreshToken = '';

  /**
   * Client ID, typically obtained from the Google APIs console at 
   * http://code.google.com/apis/console, and configured in the config.php
   */
  public $clientId = '';

  /**
   * Client secret, typically obtained from the Google APIs console at 
   * http://code.google.com/apis/console, and configured in the config.php
   */
  public $clientSecret = '';

  /**
   * Constructor for OauthCredentials.  Sets values of member variables.
   *
   * @param string $accessToken Access token obtained when exchanging code
   * @param string $refreshToken Refresh token obtained when 1st exchaning code
   * @param int $created Time OAuth token was created
   * @param int $expiresIn Time after "created" when access token will expire
   * @param string $clientId OAuth client ID, obtained from APIs Console
   * @param string $clientSecret OAuth client secret, obtained from APIs Console
   */
  public function OauthCredentials($accessToken, $refreshToken, $created, 
                                   $expiresIn, $clientId, $clientSecret) {
    $this->accessToken = $accessToken;
    $this->refreshToken = $refreshToken;
    $this->created = $created;
    $this->expiresIn = $expiresIn;
    $this->clientId = $clientId;
    $this->clientSecret = $clientSecret;
  }

  /**
   * Return JSON object representing the member variables
   *
   * @return string JSON string
   */
  public function toJson() {
    $arr = array('access_token' => $this->accessToken,
                 'refresh_token' => $this->refreshToken,
                 'created' => $this->created,
                 'expires_in' => $this->expiresIn,
                 'client_id' => $this->clientId,
                 'client_secret' => $this->clientSecret
    );
    return json_encode($arr);
  }
}
