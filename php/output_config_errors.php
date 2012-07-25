<?php
/**
 * Include to output configuration errors.
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

echo <<<HTML
<html>
<head>
  <title>Checking DrEdit Configuration</title>
  <style type="text/css">
    .success  { color: green }
    .failure {
        color: white;
        background-color: red;
        font-weight: bold;
    }
    .instructions { color: darkgray }
  </style>
</head>
<body>
HTML;

if (count($dbErrors) > 0) {
  echo '<span class="failure">DB check failure</span> ';
  echo '<ul>';
  foreach ($dbErrors as $dbError) {
    echo '<li>' . $dbError . '</li>';
  }
  echo '</ul>';
  echo '<span class="instructions">';
  echo "Database should be created with: <pre>";
  echo "-- Script\n";
  echo "-- \n";
  readfile('create_db.sql');
  echo "</pre>";
  echo '</span></span>';
}
if (count($oauthErrors) > 0) {
  echo '<span class="failure">OAuth check failure</span> ';
  echo '<ul>';
  foreach ($oauthErrors as $oauthError) {
    echo '<li>' . $oauthError . '</li>';
  }
  echo '</ul>';
}

echo '</body></html>';
?>
