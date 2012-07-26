'use strict';

function UserCtrl($scope, backend) {
		$scope.user = null;
		$scope.login = function() {
				backend.user().then(function(response) {
						$scope.user = response.data;
				});
		}
		$scope.login();
}

function EditorCtrl($scope, $location, $routeParams, $timeout, editor, autosaver) {
		console.log($routeParams);
		$scope.editor = editor;
		$scope.$on('saved', function(event, info) {
			$location.path('/edit/' + info.resource_id);
		});
		if ($routeParams.id) {	
				$scope.id = $routeParams.id;
				editor.load($scope.id);
		} else {
			  // New doc, but defer to next event cycle to ensure init
				$timeout(function() {
						editor.create();
				}, 1);
	  }
}	

function MenuCtrl($scope, $location, appId) {
		var onFilePicked = function(data) {
				$scope.$apply(function() {
						if (data.action == 'picked') {
								var id = data.docs[0].id;
								$location.path('/edit/'+id);
						}
				});
		};
		$scope.open = function() {
				var view = new google.picker.View(google.picker.ViewId.DOCS);
				view.setMimeTypes('text/plain');
				var picker = new google.picker.PickerBuilder()
				.setAppId(appId)
				.addView(view)
				.setCallback(angular.bind(this, onFilePicked))
				.build();
				picker.setVisible(true);
		};
		$scope.create = function() {
				this.editor.create();
		};
		$scope.save = function() {
				this.editor.save(true);
		}
}

function RenameCtrl($scope, editor) {
		$('#rename-dialog').on('show', function() {
				$scope.$apply(function() {
					$scope.newFileName = editor.documentInfo.title;					
				});
		});
		$scope.save = function() {
				editor.documentInfo.title = $scope.newFileName;
				editor.dirty(true);
				$('#rename-dialog').modal('hide');
		};
}

function AboutCtrl($scope, backend) {
		$('#about-dialog').on('show', function() {
				backend.about().then(function(result) {
						$scope.info = angular.fromJson(result.data);
				});
		});
}