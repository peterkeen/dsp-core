'use strict';

angular.module('AdminApp.apisdk', []).
    directive('apisdk', ['$window', function($window) {

        return {
            restrict: 'E',
            replace:true,
            controller: function($scope, $element) {


                $scope.activeTab = 'swagger';


                $scope.init = function() {
                  $scope.activateTab('swagger');
                    $scope.getCurrentServer();
                };



                $scope.activateTab = function(tabIdStr) {

                    $scope.activeTab = tabIdStr;
                    $scope.$broadcast('tab:activate:tab', tabIdStr);
                };

                $scope.getActiveTab = function() {
                    return $scope.activeTab;
                };


                $scope.getCurrentServer = function() {

                    return $window.location.protocol + '\/\/' + $window.location.host;
                };






                $scope.$on('tab:active', function(e) {

                    $scope.$broadcast('tab:active:tab', $scope.getActiveTab());
                });


                $scope.$on('swagger:getServer', function(e) {

                    $scope.$broadcast('swagger:getServer:address', $scope.getCurrentServer());
                });

                $scope.$on('sdk:getServer', function(e) {

                    console.log('sdk message')
                    $scope.$broadcast('sdk:getServer:address', $scope.getCurrentServer());
                });



                $scope.init();


            },
            templateUrl: 'views/directives/apisdk/main.html',
            link: function(scope, elem, attrs) {



            }
        }
    }])
    .directive('swagger', ['$window', function($window) {

        return {
            restrict: 'E',
            require: '^?apisdk',
            replace: true,
            scope: {},
            templateUrl: 'views/directives/apisdk/swagger.html',
            link: function(scope, elem, attrs) {

                scope.active = false;
                scope.id = 'swagger';
                scope.iframeUrl = null;

                scope.init = function() {


                    if (attrs.standAlone === 'true') {

                        scope.iframeUrl = scope.getCurrentServer() + '/swagger/'
                    }else {

                        scope.$emit('tab:active');
                        scope.$emit('swagger:getServer');
                    }
                };


                scope.getCurrentServer = function() {

                    return $window.location.protocol + '\/\/' + $window.location.host;
                };

                scope.$on('swagger:on', function(e, serviceNameStr) {

                    console.log(serviceNameStr);

                    if (serviceNameStr) {
                        scope.iframeUrl = scope.getCurrentServer() + '/swagger/#!/' + serviceNameStr;
                    }

                    scope.active = true;
                });

                scope.$on('swagger:off', function(e) {

                    scope.active = false;
                    scope.iframeUrl = null;
                });

                scope.$on('tab:activate:tab', function(e, tabIdStr) {

                    scope.active = scope.id === tabIdStr;

                    if (scope.active && !scope.iframeUrl) {
                        scope.$emit('swagger:getServer');
                    }
                });

                scope.$on('tab:active:tab', function(e, tabIdStr) {

                    scope.active = scope.id === tabIdStr;
                });

                scope.$on('swagger:getServer:address', function(e, addressStr) {

                    scope.iframeUrl = addressStr + '/swagger/';
                });


                scope.init();
            }
        }
    }])
    .directive('sdk', [function() {

        return {
            restrict: 'E',
            require: '^apisdk',
            replace: true,
            scope: {},
            templateUrl: 'views/directives/apisdk/sdk.html',
            link: function(scope, elem, attrs) {

                scope.active = false;
                scope.id = 'sdk';
                scope.iframeUrl = null;


                scope.init = function() {

                    scope.$emit('tab:active');
                    scope.$emit('sdk:getServer');
                };

                scope.$on('tab:activate:tab', function(e, tabIdStr) {

                    scope.active = scope.id === tabIdStr;

                    if (scope.active && !scope.iframeUrl) {
                        scope.$emit('sdk:getServer');
                    }
                });

                scope.$on('tab:active:tab', function(e, tabIdStr) {

                    scope.active = scope.id === tabIdStr

                });

                scope.$on('sdk:getServer:address', function(e, addressStr) {

                    scope.iframeUrl = addressStr + '/docs/';

                    angular.element('#docsFrame').attr('src', scope.iframeUrl);
                    console.log(angular.element('#docsFrame'));
                });

                scope.init();
            }
        }
    }]);

