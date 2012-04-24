<?php
/**
 * Class for handling authentication of the user and authorization
 * to access Google Drive.
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

/*
 * Load required libraries for working with REST API to create, read and update
 * files on Google Drive.  Also, load in the UserInfo service for retrieving
 * Google user profile information and the DrEdit configuration.
 */
require_once 'libs/gd-v1-php/apiClient.php';
require_once 'libs/gd-v1-php/contrib/apiOauth2Service.php';
require_once 'oauth_credentials.php';
require_once 'config.php';

session_start();

/**
 * Class for handling authentication of the user and authorization
 * to access Google Drive.
 *
 * Example usage:
 *   $authHandler = new AuthHandler('webui');
 *   $authHandler->VerifyAuth();
 *
 * @author Ryan Boyd <rboyd@google.com>
 */
class AuthHandler {
  /**
   * Text to display in alert modal after authentication.
   */
  private $alertModalText;

  /**
   * PDO database connection for storing refresh tokens.
   *
   * @var PDO
   */
  private $dbConnection;

  /**
   * String representing the type of request - 'webui' or 'webapi'.  Used to
   * determine whether auth errors should result in a redirect to the OAuth
   * provider or just a HTTP status code returned
   *
   * @var string
   */
  private $requestType;

  /**
   * Constructor for AuthHandler.
   *
   * @param string $requestType request type used to set member variable
   * @return AuthHandler constructed class
   */
  function AuthHandler($requestType) {
    $this->requestType = $requestType;
    $this->alertModalText = '';
    $this->dbConnection = $this->GetDbConnection();
    return $this;
  }

  /**
   * Get text to display in alert modal after authentication.
   *
   * @return string Alert modal text
   */
  function GetAlertModalText() {
    return $this->alertModalText;
  }

  /**
   * Exchange an authorization code for OAuth 2.0 credentials.
   *
   * @param String $authorizationCode Authorization code to exchange for an
   *     access token and refresh token.  The refresh token is only returned by
   *     Google on the very first exchange- when a user explicitly approves
   *     the authorization request.
   * @return OauthCredentials OAuth 2.0 credentials object
   */
  function GetOAuth2Credentials($authorizationCode) {
    $client = new apiClient();
    $client->setClientId(Config::CLIENT_ID);
    $client->setClientSecret(Config::CLIENT_SECRET);
    $client->setRedirectUri(Config::REDIRECT_URI);

    /**
     * Ordinarily we wouldn't set the $_GET variable.  However, the API library's
     * authenticate() function looks for authorization code in the query string,
     * so we want to make sure it is set to the correct value passed into the
     * function arguments.
     */
    $_GET['code'] = $authorizationCode;

    $jsonCredentials = json_decode($client->authenticate());

    $oauthCredentials = new OauthCredentials(
        $jsonCredentials->access_token,
        isset($jsonCredentials->refresh_token)?($jsonCredentials->refresh_token):null,
        $jsonCredentials->created,
        $jsonCredentials->expires_in,
        Config::CLIENT_ID,
        Config::CLIENT_SECRET
    );

    return $oauthCredentials;
  }

  /**
   * Retrieve user profile information from Google's UserInfo service.
   *
   * @param OauthCredentials $credentials Object representation of OAuth creds
   * @return Userinfo User profile information
   */
  function GetUserInfo($credentials) {
    $client = new apiClient();
    $client->setUseObjects(true);

    /*
     * Set clientId and clientSecret in case token is expired.
     * and refresh is needed
     */
    $client->setClientId($credentials->clientId);
    $client->setClientSecret($credentials->clientSecret);
    $client->setAccessToken($credentials->toJson());
    $userInfoService = new apiOauth2Service($client);
    $userInfo = $userInfoService->userinfo->get();
    return $userInfo;
  }

  /**
   * Create a PDO database connection.
   *
   * @return PDO created database connection object
   */
  function GetDbConnection() {
    $dbh = new PDO(Config::DB_PDO_CONNECT,
        Config::DB_PDO_USER,
        Config::DB_PDO_PASSWORD);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbConnection = $dbh;
    return $dbConnection;
  }

  /**
   * Get a user row from the database with the specified Google user ID.
   *
   * @param string $userId Google user ID from UserInfo service
   * @return array Array of a database row, keyed on column. Returns null if
   *     no record is found
   */
  function GetUserFromDb($userId) {
    $selectStmt = $this->dbConnection->prepare(
        'SELECT id, refresh_token FROM users WHERE id=:id');
    $selectStmt->bindParam(':id', $userId);
    if ($selectStmt->execute()) {
      $row = $selectStmt->fetch();
      if ($row) {
        return $row;
      }
    }
   return null;
  }

  /**
   * Update the refresh token in the database for the supplied user ID.
   *
   * @param string $userId Google user ID from UserInfo service
   * @param string $refreshToken Refresh token to set in DB
   * @return bool true on success. PDO exceptions thrown otherwise.
   */
  function UpdateRefreshTokenInDb($userId, $refreshToken) {
    $updateStmt = $this->dbConnection->prepare(
        'UPDATE users SET refresh_token=:refresh_token WHERE id=:id');
    $updateStmt->bindParam(':id', $userInfo->id);
    $updateStmt->bindParam(':refresh_token', $refreshToken);
    $updateStmt->execute();
    return true;
  }

  /**
   * Create a user record in the database, with the specified UserInfo profile
   * and refresh token.
   *
   * @param stdClass $userInfo stdClass returned by Google's UserInfo service
   * @param string $refreshToken OAuth refresh token for user
   * @return bool true on sucess.  PDO exceptions thrown otherwise.
   */
  function CreateUserInDb($userInfo, $refreshToken) {
    $insertStmt = $this->dbConnection->prepare(
      'INSERT INTO users(id,first_name,last_name,email,refresh_token) ' .
      'VALUES(:id,:first_name,:last_name,:email,:refresh_token)');

    $insertStmt->bindParam(':id', $userInfo->id);
    $insertStmt->bindParam(':first_name', $userInfo->given_name);
    $insertStmt->bindParam(':last_name', $userInfo->family_name);
    $insertStmt->bindParam(':email', $userInfo->email);
    $insertStmt->bindParam(':refresh_token', $refreshToken);
    $insertStmt->execute();
    return true;
  }

  /**
   * Retrieve the user from the database.
   * - If the user does not exist, create it
   * - If the user does exist and there's a new refresh token, update it
   * Save credentials in the session
   *
   * @param stdClass $userInfo stdClass returned by Google's UserInfo service
   * @param string $refreshToken OAuth refresh token for user
   * @return string User ID of the retrieved or created user
   */
  function GetUser($userInfo, $refreshToken='') {
    $userId = null;

    $sessCredentials = $_SESSION['credentials'];

    $userRow = $this->GetUserFromDb($userInfo->id);
    if ($userRow) {
      if ($refreshToken == '') {
        $sessCredentials->refreshToken = $userRow['refresh_token'];
      } else {
        // we have a new refresh token, update it in the DB
        $this->UpdateRefreshTokenInDb($userRow['id'], $refreshToken);
        $sessCredentials->refreshToken = $refreshToken;
      }
    } else {
      // create user
      $this->CreateUserInDb($userInfo, $refreshToken);
      $sessCredentials->refreshToken = $refreshToken;
    }
    /*
     * Store updated credentials in the user's session, using refreshToken
     * retrieved from database
     */
    $_SESSION['credentials'] = $sessCredentials;
    return $userInfo->id;
  }

  function VerifyAuth() {
    $userInfo = null;

    try {
      /*
       * If an authorization code is in the URL, process it
       */
      if (isset($_GET['code'])) {
        /**
         * Redeemed authorization codes are stored in the session to prevent
         * accidental multiple redemption of the same code causing an exception.
         * ie, if a user refreshes their browser when a code is in the URL.
         * We need to initialize the array of redeemed authorization codes.
         */
        if (!isset($_SESSION['redeemed_codes'])) {
          $_SESSION['redeemed_codes'] = array();
        }

        /*
         * If code has not been used before, we could have a new
         * user authenticating.  Check if this is the case.
         */
        if (array_search($_GET['code'], $_SESSION['redeemed_codes']) === false) {
          $oldUserInfo = (isset($_SESSION['userInfo']))?($_SESSION['userInfo']):null;

          $credentials = $this->GetOAuth2Credentials($_GET['code']);
          $_SESSION['credentials'] = $credentials;

          try {
            $userInfo = $this->GetUserInfo($_SESSION['credentials']);
            $_SESSION['userInfo'] = $userInfo;
          } catch (apiException $e) {
            error_log('Error retrieving user information from UserInfo svc: ' .
                      $e->getMessage(), 0);
            throw $e;
          }
          try {
            $refreshToken = $credentials->refreshToken;
            $userId = $this->GetUser($userInfo, $refreshToken);
          } catch (Exception $e) {
            error_log('Error retrieving user from DB or updating refresh token: ' .
                      $e->getMessage(), 0);
            throw $e;
          }

          $_SESSION['userId'] = $userId;
          $_SESSION['redeemed_codes'][] = $_GET['code'];

          /*
           * If the user has changed during this session, set the alertModalText
           * to display the change in the UI.  This mitigates some types of
           * session fixation attacks.
           */
          if (isset($oldUserInfo->id) && isset($userInfo->id) &&
              $oldUserInfo->id != $userInfo->id) {

              $this->alertModalText =
                  sprintf('<p>Previously signed in as: %s <br />' .
                          'Now signing in as: %s</p>',
                          $oldUserInfo->email,
                          $userInfo->email);
          }
        }
      }
    } catch (apiException $e) {
      error_log('Error when authenticating and authorizing user: ' .
                $e->getMessage(), 0);
      /*
       * Remove all credentials from session, as user isn't properly
       * authorized or there were issues retrieving the user's identity.
       */
      unset($_SESSION['userId']);
      unset($_SESSION['credentials']);
    }

    /*
     * If we dont' have an authenticated user, or don't have OAuth credentials
     * available for interaction with the Drive API, we need to redirect the
     * user to OAuth (for web UI requests) or fail (for JS API request).
     */
    if (!isset($_SESSION['userId']) || ! isset($_SESSION['credentials'])){
      if ($this->requestType == 'webui') {
        /*
         * Request came direct to app's web UI.  Redirect for OAuth 
         * authorization and stop processing the PHP code.
         */
        header('Location: ' . Config::FULL_AUTH_URL);
        exit;
      } else {
        /*
         * Request came to JS API. JS needs to decide how to handle, so throw a 401
         * and stop processing the PHP code.
         */
        header("HTTP/1.1 401 Unauthorized");
        exit;
      }
    }
    return $userInfo;
  }
}
