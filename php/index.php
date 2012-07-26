<?php
/**
 * Main entry point for web requests to the DrEdit PHP application.  Checks
 * authentication and authorization using auth_handler.php, checks configuration
 * using check_config.php, and then spawns the output_editor.php to load the web
 * interface.
 *
 * @author Ryan Boyd <rboyd@google.com>
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
 * Type of request: webui, webapi
*/
$requestType = 'webui';

/*
 * Set configuration for client_id, client_secret and various OAuth endpoints
 */
require_once 'config.php';

/*
 * Check the configuration
 */
require_once 'check_config.php';

/*
 * Perform all authorization and authentication logic - exchanging auth
 * code for access token, retrieving user profile info, looking up
 * pre-existing refresh token in database, updating it (if applicable),
 * and more.
 */
require_once 'auth_handler.php';
$authHandler = new AuthHandler($requestType);
$authHandler->VerifyAuth();

/*
 * If an authorization 'code' is set, then we assume the user came from
 * Google Drive and check to see if the 'state' parameter exists, with
 * the mode and potially specified file IDs (on open) or a folder parentId
 * (on create).
 */
if (isset($_GET['code'])) {
  /*
   * State should always be defined
   */
  if (isset($_GET['state'])) {
    $state = json_decode(stripslashes($_GET['state']));
    $_SESSION['mode'] = $state->action;

    if (isset($state->ids)){
      $_SESSION['fileIds'] = $state->ids;
    } else {
      $_SESSION['fileIds'] = array();
    }
    if (isset($state->parentId)) {
      $_SESSION['parentId'] = $state->parentId;
    } else {
      $_SESSION['parentId'] = null;
    }
  } else {
    $error = 'Code defined, but no state.  Condition shouldn\'t exist.';
    throw new Exception($error);
  }
}


if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  /*
   * User information should be set in the session via auth_handler.php
   */
  if (isset($_SESSION['userInfo'])) {
    $userInfo = $_SESSION['userInfo'];

    /*
     * Retrieve the user's picture from UserInfo.  Google allows
     * adding an imgmax query param to the picture to get a max
     * size (any any dimension) of the indicated size.  Reduces
     * bandwidth and improves rendering time.
     */
    if ($userInfo->picture == null) {
      $userPicture = null;
    } else {
      $userPicture = $userInfo->picture . '?imgmax=50';
    }
    $userName = $userInfo->given_name;

    $alertModalText = $authHandler->GetAlertModalText(); 
    include 'output_editor.php';
  } else {
    /*
     * User came direct to app, redirect for oauth
     */
    header('Location: ' . Config::FULL_AUTH_URL);
  }
}
