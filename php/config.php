<?php
/**
 * Configuration for DrEdit PHP.  Developers need to define the OAuth
 * and databse configuration in constants.
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
 * Configuration for DrEdit PHP.  Developers need to define the OAuth
 * and databse configuration in constants.
 *
 * @author Ryan Boyd <rboyd@google.com>
 */
class Config {

  /**
   * Define your application ID obtained from the APIs Console project.
   * APIs console is accessible from https://code.google.com/apis/console. These
   * values are specific for your application.
   *
   * Example APP_ID = '892677078282'
   */
  const APP_ID = '';

  /*
   * Define your OAuth 2.0 configuration from APIs Console project.  The
   * APIs console is accessible from https://code.google.com/apis/console. These
   * values are specific for your application.
   */

  /**
   * OAuth 2.0 client ID
   *
   * Example CLIENT_ID = '892677078282.apps.googleusercontent.com'
   */
  const CLIENT_ID = '';

  /**
   * OAuth 2.0 client secret
   */
  const CLIENT_SECRET = '';

  /*
   * Define your redirect URI.  This should be the root of the web application
   * where the index.php file would be served from.
   *
   * Example REDIRECT_URI = 'http://dredit.saasyapp.com/'
   */
  const REDIRECT_URI = '';

  /**
   * Define the correct OAuth 2.0 authorization server URL for the application.
   * This URL is where DrEdit PHP will redirect a user who arrives at the web
   * UI without a valid authorization code.  This will also occur when the user
   * clicks on the DrEdit icon on the Chrome New Tab Page.
   *
   * Documentation available here: https://developers.google.com/accounts/docs/OAuth2WebServer#formingtheurl
   *
   * Example FULL_AUTH_URL = 'https://accounts.google.com/o/oauth2/auth?scope=https://www.googleapis.com/auth/drive.file+https://www.googleapis.com/auth/userinfo.profile+https://www.googleapis.com/auth/userinfo.email&client_id=892677078282.apps.googleusercontent.com&response_type=code&access_type=offline&redirect_uri=http://dredit.saasyapp.com&state={"action":"create"}'
   *
   * Note: the state is set as {"action":"create"}
   */
  const FULL_AUTH_URL = '';

  /*
   * Database configuration.  Any PDO database can be used as long as minimal
   * SELECT/INSERT/UPDATE SQL syntax is supported.  See auth.php for queries.
   */

  /**
   * PDO connection string.  Pre-defined value is to use a local MySQL database
   * called 'dredit'.
   */
  const DB_PDO_CONNECT = 'mysql:host=localhost;dbname=dredit';

  /**
   * PDO database username
   */
  const DB_PDO_USER = '';

  /**
   * PDO database password
   */
  const DB_PDO_PASSWORD = '';
}
