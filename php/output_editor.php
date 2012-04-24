<?php
/**
 * Outputs DrEdit PHP user interface.  Needs OAuth URLs and the values of
 * query params in order to setup the UI.  These are set as javascript
 * vars based on PHP processing.
 *
 * @author Ryan Boyd <rboyd@google.com>
 *
 * If the user's picture is set, it is included in the interface to mitigate
 * potential session fixation attacks.
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
?><html>
  <head>
    <title>DrEdit PHP</title>
    <link rel="stylesheet" href="static/bootstrap.min.css"></link>
    <link rel="stylesheet" href="static/dredit.css"></link>
    <script src="https://www.google.com/jsapi" charset="utf-8"></script>
    <script src="static/jquery.min.js" 
            type="text/javascript" 
            charset="utf-8"></script>
    <script src="static/bootstrap.min.js" 
            type="text/javascript" 
            charset="utf-8"></script>
    <script src="static/ace/ace.js" 
            type="text/javascript" 
            charset="utf-8"></script>
    <script src="static/ace/mode-html.js" 
            type="text/javascript" 
            charset="utf-8"></script>
    <script src="static/dredit.js" 
            type="text/javascript" 
            charset="utf-8"></script>
  </head>
  <body>

<div class="navbar navbar-fixed-top pull-right">
<ul class="nav pull-right">
  <?php if (isset($userPicture)) { ?>
  <li><img src="<?php echo $userPicture;?>" height="30" /></li>
  <?php } ?>
</ul>
</div>
    <div id="main">
      <div id="nav-pane"></div>
      <div id="action-pane">
        <button type="submit" class="btn" id="create"><i
            class="icon-file icon"></i> New</button>
        <button type="submit" class="btn" id="open"><i
            class="icon-download icon"></i> Open</button>
        <button type="submit" class="btn" id="edit"><i
            class="icon-edit icon"></i> Edit</button>
        <button type="submit" class="btn" id="save"><i
            class="icon-upload icon"></i> Save</button>
        <div id="saving" class="pull-right alert">Talking to Drive</div>
        <div id="saving-error" class="pull-right alert alert-error" 
            style="display: none;">Error Saving</div>
        </div>
      <div id="editor-pane"></div>
    </div>
  </body>
  <script>
    var alertModalText = <?php echo json_encode($alertModalText);?>;
    var dredit = dredit || {};
    dredit.FILE_IDS = <?php echo json_encode($_SESSION['fileIds']);?>;
    dredit.PARENT_ID = <?php echo json_encode($_SESSION['parentId']);?>;
    dredit.APP_ID = '<?php echo Config::APP_ID;?>';
    dredit.OAUTH_AUTH_URL = '<?php echo Config::FULL_AUTH_URL;?>';
    google.load('picker', '1');
  </script>
</html>
