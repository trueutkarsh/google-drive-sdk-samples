<?php
/**
 * Include for web requests which ensures that all required configuration
 * for DrEdit PHP is complete, including the database and OAuth config.
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

require_once 'config.php';

/**
 * Check that a database connection can be made to the database configured in
 * Config::DB_PDO_CONNECT.  Verify that the required fields are present in the
 * database schema
 *
 * @return aray Array of error strings or empty if no errors
 */
function CheckDatabase() {
  $errorArray = array();
  try {
    $dbh = new PDO(Config::DB_PDO_CONNECT,
        Config::DB_PDO_USER,
        Config::DB_PDO_PASSWORD);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $requiredFields = array(
        'id' => false,
        'first_name' => false,
        'last_name' => false,
        'email' => false,
        'refresh_token' => false);

    $selectStmt = $dbh->prepare('DESCRIBE users');
    if ($selectStmt->execute()) {
      while ($row = $selectStmt->fetch()) {
        $fieldName = $row['Field'];
        $requiredFields[ $fieldName ] = true;
      }
    }
    foreach ($requiredFields as $requiredFieldName => $requiredFieldValue) {
      if ($requiredFieldValue !== true) {
        $errorArray[] = $requiredFieldName . ' is not defined in the database';
      }
    }
  } catch (Exception $ex) {
    $errorArray[] = "Cannot connect to database '" . 
        Config::DB_PDO_CONNECT . "'" .
        ". Error message: '" .
        $ex->getMessage() . "'";
  }
  return $errorArray;
}

/**
 * Check that the OAuth CLIENT_ID, CLIENT_SECRET, FULL_AUTH_URL, and
 * REDIRECT_URI are configured in Config.
 *
 * @return aray Array of error strings or empty if no errors
 */
function CheckOauthConfig() {
  $errorArray = array();
  if (Config::CLIENT_ID == '') {
    $errorArray[] = 'Config::CLIENT_ID must be set in config.php';
  }
  if (Config::CLIENT_SECRET == '') {
    $errorArray[] = 'Config::CLIENT_SECRET must be set in config.php';
  }
  if (Config::REDIRECT_URI == '') {
    $errorArray[] = 'Config::REDIRECT_URI must be set in config.php';
  }
  if (strpos(Config::FULL_AUTH_URL, Config::CLIENT_ID) === null) {
    $errorArray[] = 'Config::FULL_AUTH_URL must be set in config.php.' .
        'It must reference your Config::CLIENT_ID';
  }
  return $errorArray;
}

$dbErrors = CheckDatabase();
$oauthErrors = CheckOauthConfig();
if (count($dbErrors) > 0 || count($oauthErrors) > 0) {
  /**
   * Output the errors and stop processing
   */
  include 'output_config_errors.php';
  exit;
}
?>
