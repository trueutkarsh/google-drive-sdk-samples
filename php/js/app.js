'use strict';

google.load('picker', '1');

angular.module('app', ['app.filters', 'app.services', 'app.directives'])
.constant('saveInterval', 15000)
.constant('appId', '892677078282')
.constant('EditorState', {
		CLEAN: 0,// NO CHANGES
		DIRTY: 1, // UNSAVED CHANGES
		SAVE: 2, // SAVE IN PROGRESS
		LOAD: 3,  // LOADING
		READONLY: 4	
})
.config(['$routeProvider', function($routeProvider) {
  	$routeProvider
      .when('/edit/:id', {templateUrl: 'partials/editor.html',   controller: EditorCtrl})
      .otherwise({redirectTo: '/edit/'});
}]);
