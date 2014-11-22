'use strict';

/**
 * @ngdoc overview
 * @name todomvc
 * @description
 * # todomvc
 *
 * Main module of the application.
 */
angular
  .module('todomvc', [
    'ngRoute',
    'config'
  ])
  .config(function ($routeProvider) {
    var routeConfig = {
      controller: 'TodoCtrl',
      templateUrl: 'todomvc-index.html',
      resolve: {
        store: function (todoStorage) {
          // Get the correct module (API or localStorage).
          return todoStorage.then(function (module) {
            module.get(); // Fetch the todo records in the background.
            return module;
          });
        }
      }
    };

    $routeProvider
      .when('/', routeConfig)
      .when('/:status', routeConfig)
      .otherwise({
        redirectTo: '/'
      });

  });


