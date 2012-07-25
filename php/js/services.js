'use strict';

var module = angular.module('app.services', []);

module.factory('editor', function(EditorState, backend, $q, $rootScope, $log) {
    var editor = null;
    var EditSession = require("ace/edit_session").EditSession;

    var service = {
        state: EditorState.CLEAN,
        deferredState: EditorState.CLEAN,
				rebind: function(element) {
					editor = ace.edit(element);
				},
        create: function() {
            this.file({
                content: '',
                labels: {
                    starred: false
                },
								editable: true,
                title: 'Untitled document',
                description: '',
                mimeType: 'text/plain',
                resource_id: null
            });
        },
        load: function(id, reload) {
						if (!reload && this.documentInfo && id == this.documentInfo.resource_id) {
								return $q.reject("File already loaded");
						}
            $log.info("Loading resource", id);
            this.deferredState = this.state;
            this.state = EditorState.LOAD;
            return backend.load(id).then(angular.bind(this,
            function(result) {
                $log.log(result);
                this.file(angular.fromJson(result.data));
								$rootScope.$broadcast('loaded', this.documentInfo);
                return result;
            }), angular.bind(this, function(result) {
								$log.warn("Error loading", result);
								this.state = this.deferredState;
								$rootScope.$broadcast('error', {
									action: 'load',
									message: "An error occured while loading the file"
								});
								return result;
						}));
        },
        save: function(newRevision, skipContent) {
            $log.info("Saving file", newRevision);
            if (this.state == EditorState.SAVE) {
                $log.warn("Save called twice...");
                throw 'Save called from incorrect state';
            }
            this.state = EditorState.SAVE;
            // Force revision if first save of the session
            newRevision = newRevision || self.saveCount == 0;
            var file = this.file();
						if (skipContent || !file.editable) {
							  // Either updating just metadata or file not editable, null the content
							  // to force a patch
								file.content = null;
						}
						return backend.save(file, newRevision).then(angular.bind(this,
            function(result) {
                $log.info("Saved", result);
                this.documentInfo.resource_id = angular.fromJson(result.data);
                this.saveCount++;
                this.dirty(false);
								$rootScope.$broadcast('saved', this.documentInfo);
                return result;
            }), angular.bind(this, function(result) {
								$log.warn("Error saving", result);
								this.dirty(true);
								this.dirty(false);
								$rootScope.$broadcast('error', {
									action: 'save',
									message: "An error occured while saving the file"
								});
								return result;
						}));
        },
        file: function(fileInfo) {
            if (arguments.length) {
                var session = new EditSession(fileInfo.content);
                session.on('change', angular.bind(this,
                function() {
                    this.dirty(true);
                    $rootScope.$apply();
                }));
                this.documentInfo = angular.extend({},
                fileInfo, {
                    resource_id: fileInfo.id,
                    content: null
                });
                this.state = this.documentInfo.editable ? EditorState.CLEAN : EditorState.READONLY;
                this.deferredState = EditorState.CLEAN;
                this.saveCount = 0;
                editor.setSession(session);
								editor.setReadOnly(!this.documentInfo.editable);
								editor.focus();
            }
            return angular.extend(this.documentInfo, {
                content: editor.getSession().getValue()
            });
        },
        dirty: function(dirty) {
            if (arguments.length) {
                if (dirty) {
                    if (this.state == EditorState.SAVE) {
                        this.deferredState = EditorState.DIRTY;
                    } else {
                        this.state = EditorState.DIRTY;
                    }
                } else {
                    this.state = this.deferredState;
                    this.deferredState = EditorState.CLEAN;
                }
            }
            return this.state;
        }
    };
    return service;
});

module.factory('backend', function($http, $log) {
    var service = {
        user: function() {
            return $http.get('/dredit/user.php');
        },
        about: function() {
						return $http.get('/dredit/about.php');
				},
        load: function(id) {
            $log.info('Loading', id);
            return $http.get('/dredit/svc.php', {
                params: {
                    'file_id': id
                }
            });
        },
        save: function(fileInfo, newRevision) {
            $log.info('Saving', fileInfo);
            return $http({
                url: '/dredit/svc.php',
                method: fileInfo.resource_id ? 'PUT': 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                params: {
                    'newRevision': newRevision
                },
                data: JSON.stringify(fileInfo)
            });
        }
    };
    return service;
});

module.factory('autosaver', function(editor, EditorState, saveInterval, $log, $timeout) {
    $log.info("Creating autosaver");
    var saveFn = function() {
        if (editor.state == EditorState.DIRTY) {
            $log.info("Auto-saving...");
            editor.save(false);
        }
    };
    var createTimeout = function() {
        return $timeout(saveFn, saveInterval).then(createTimeout);
    }
    return createTimeout();
});
