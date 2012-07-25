<?php
/**
 * Handler for API requests from DrEdit PHP frontend.  These requests 
 * retrieve, save or create files in Google Drive using pre-established
 * authorization information stored in the session. 
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
 * Set configuration for client_id, client_secret and various OAuth endpoints
 */
require_once 'config.php';

/*
 * Include class which handles requests to the Drive API
 */
require_once 'drive_handler.php';

/**
 * Indicate the request type as a web API request.  This prevents the 
 * authorization logic from redirecting the user in case of failures, and
 * instead returns HTTP error codes for interpretation by the JavaScript.
 */
$requestType = 'webapi';
require_once 'auth_handler.php';
$authHandler = new AuthHandler($requestType);
$authHandler->VerifyAuth();

header('Content-type: application/json');

$driveHandler = new DriveHandler($_SESSION['credentials']);

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  echo json_encode($driveHandler->GetAbout());
}
