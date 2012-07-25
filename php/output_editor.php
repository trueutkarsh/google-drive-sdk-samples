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
?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>DrEdit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Le styles -->
    <link href="css/bootstrap.css" rel="stylesheet">
    <link href="css/font-awesome.css" rel="stylesheet">
    <style>
      body {
        padding-top: 60px; /* 60px to make the container go all the way to the bottom of the topbar */
      }
    </style>
    <link href="css/bootstrap-responsive.css" rel="stylesheet">
    <link href="css/app.css" rel="stylesheet">

    <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

    <!-- Le fav and touch icons -->
    <link rel="shortcut icon" href="../assets/ico/favicon.ico">
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="img/ico/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="img/ico/apple-touch-icon-114-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="img/ico/apple-touch-icon-72-precomposed.png">
    <link rel="apple-touch-icon-precomposed" href="img/ico/apple-touch-icon-57-precomposed.png">
  </head>

  <body ng-cloak ng-app="app">

    <div class="navbar navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <a class="brand" href>DrEdit</a>
          <ul class="nav pull-right" ng-controller="UserCtrl">
            <li class="dropdown">
              <a class="dropdown-toggle" data-toggle="dropdown" href>
                  {{user.email}}
                  <!--<img class="profile-picture" src="{{user.picture}}"/>-->
              </a>
              <ul class="dropdown-menu">
                <li><a href="{{user.link}}" target="_blank">Profile</a></li>
              </ul>
            </li>
          </ul>          
        </div>
      </div>
    </div>
    <div ng-view></div>

    <div id="editor" ace-editor></div>
    <!--
    <div class="container-fluid">
      <div id="doc-bar" ng-controller="DocBarCtrl" class="row-fluid">
        <span class="span4">
            <h3 ng-show="editor.documentInfo.editable" class="doc-title" 
                data-toggle="modal" href="#rename-dialog">{{editor.documentInfo.title}}</h3>
            <h3 ng-hide="editor.documentInfo.editable" class="doc-title">{{editor.documentInfo.title}}</h3>
            <star value="editor.documentInfo.labels.starred" click="editor.dirty(true)"></star></span>
        <span class="span4">{{editor.state | saveStateFormatter }}</span>
        <span class="span4"><a class="btn pull-right" href="#"> Share</a></span>
      </div>
      <div class="navbar subnav">
        <ul ng-controller="MenuCtrl" class="nav">
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
              File <b class="caret"></b>
            </a>
            <ul class="dropdown-menu">
              <li><a target="_blank" href="#">New</a></li>
              <li><a ng-click="open()" href="#">Open</a></li>
              <li>
                  <a ng-show="editor.documentInfo.editable" data-toggle="modal" href="#rename-dialog">Rename</a>
                  <span ng-hide="editor.documentInfo.editable">Rename</span>
              </li>
              <li><a ng-click="save()" href="#">Save</a></li>
            </ul>
          </li>
          <li><a href="#about-dialog" data-toggle="modal">About</a></li>
        </ul>
      </div>
      
    </div>
    
    <div id="editor" ng-non-bindable ></div>      

    <div class="modal hide" id="rename-dialog" ng-controller="RenameCtrl">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">��</button>
        <h3>Rename File</h3>
      </div>
      <div class="modal-body">
        <form>
            <label>Enter a new file name:</label>
            <input type="text" ng-model="newFileName" autofocus required/>
      </div>
      <div class="modal-footer">
        <a href="#" class="btn" data-dismiss="modal">Cancel</a>
        <a href="#" ng-click="save()" class="btn btn-primary">Save</a>
      </div>
    </div>
    
    <div class="modal hide" id="about-dialog" ng-controller="AboutCtrl">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">��</button>
        <h3>About</h3>
      </div>
      <div class="modal-body">
          <div><b>Total Drive Quota:</b> {{info.quotaBytesTotal | bytes}}</div>
          <div><b>Drive Quota Used:</b> {{info.quotaBytesUsed | bytes}}</div>
      </div>
      <div class="modal-footer">
        <a href="#" class="btn btn-primary" data-dismiss="modal">Close</a>
      </div>
    </div>
    -->

    <alert id="error"></alert>

    <!-- Le javascript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="lib/jquery/jquery-1.7.2.min.js"></script>
    <script src="lib/ace/ace.js" charset="utf-8"></script>
    <script src="https://apis.google.com/js/api.js"></script>
    <script src="https://www.google.com/jsapi"></script>
    <script src="lib/bootstrap/bootstrap.js"></script>
    <script src="lib/angular-1.0.0/angular-1.0.0.js"></script>
    <script src="js/app.js"></script>
    <script src="js/services.js"></script>
    <script src="js/controllers.js"></script>
    <script src="js/filters.js"></script>
    <script src="js/directives.js"></script>

  </body>
</html>
