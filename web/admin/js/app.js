/**
 * Created with JetBrains PhpStorm.
 * User: jasonsykes
 * Date: 1/28/13
 * Time: 12:17 PM
 * To change this template use File | Settings | File Templates.
 */

'use strict';

angular.module("AdminApp", [
        "ngResource",
        "ngGrid",
        "AdminApp.controllers",
        "AdminApp.apisdk"
    ]).
    config(function ($routeProvider) {
        $routeProvider.when('/', { controller: QuickStartCtrl, templateUrl: 'quick-start.html' });
        $routeProvider.when('/app', { controller: AppCtrl, templateUrl: 'applications.html' });
        $routeProvider.when('/user', { controller: UserCtrl, templateUrl: 'users.html' });
        $routeProvider.when('/role', { controller: RoleCtrl, templateUrl: 'roles.html' });
        $routeProvider.when('/group', { controller: GroupCtrl, templateUrl: 'groups.html' });
        $routeProvider.when('/schema', { controller: SchemaCtrl, templateUrl: 'schema.html' });
        $routeProvider.when('/service', { controller: ServiceCtrl, templateUrl: 'services.html' });
        $routeProvider.when('/import', { controller: FileCtrl, templateUrl: 'import.html' });
        $routeProvider.when('/file', { controller: FileCtrl, templateUrl: 'files.html' });
        $routeProvider.when('/package', { controller: PackageCtrl, templateUrl: 'package.html' });
        $routeProvider.when('/config', { controller: ConfigCtrl, templateUrl: 'config.html' });
        $routeProvider.when('/data', { controller: DataCtrl, templateUrl: 'data.html' });

        $routeProvider.when('/api', { controller: 'ApiSDKCtrl', templateUrl: 'apisdk.html' });
    });

angular.module("AdminApp")
    .factory('AppsRelated', function ($resource) {
        return $resource('/rest/system/app/:id/?app_name=admin&fields=*&related=roles', {}, { update: { method: 'PUT' }, query: {
            method: 'GET',
            isArray: false
        } });
    })
    .factory('AppsRelatedToService', function ($resource) {
        return $resource('/rest/system/app/:id/?app_name=admin&fields=*&related=app_service_relations', {}, { update: { method: 'PUT' }, query: {
            method: 'GET',
            isArray: false
        } });
    })
    .factory('App', function ($resource) {
        return $resource('/rest/system/app/:id/?app_name=admin&fields=*', {}, { update: { method: 'PUT' }, query: {
            method: 'GET',
            isArray: false
        } });
    })
    .factory('User', function ($resource) {
        return $resource('/rest/system/user/:id/?app_name=admin&fields=*&order=display_name%20ASC', {send_invite: false}, { update: { method: 'PUT' }, query: {
            method: 'GET',
            isArray: false
        } });
    })
    .factory('Role', function ($resource) {
        return $resource('/rest/system/role/:id/?app_name=admin&fields=*', {}, { update: { method: 'PUT' }, query: {
            method: 'GET',
            isArray: false
        } })
    })
    .factory('RolesRelated', function ($resource) {
        return $resource('/rest/system/role/:id/?app_name=admin&fields=*&related=users,apps,role_service_accesses,role_system_accesses', {}, { update: { method: 'PUT' }, query: {
            method: 'GET',
            isArray: false
        } });
    })
    .factory('Service', function ($resource) {
        return $resource('/rest/system/service/:id/?app_name=admin&fields=*', {}, { update: { method: 'PUT' }, query: {
            method: 'GET',
            isArray: false
        } });
    })
    .factory('Schema', function ($resource) {
        return $resource('/rest/schema/:name/?app_name=admin&fields=*', {}, { update: { method: 'PUT' }, query: {
            method: 'GET',
            isArray: false
        } });
    })
    .factory('DB', function ($resource) {
        return $resource('/rest/db/:name/?app_name=admin&fields=*&include_schema=true', {}, { update: { method: 'PUT' }, query: {
            method: 'GET',
            isArray: false
        } });
    })
    .factory('Group', function ($resource) {
        return $resource('/rest/system/app_group/:id/?app_name=admin&fields=*&related=apps', {}, { update: { method: 'PUT' }, query: {
            method: 'GET',
            isArray: false
        } });
    })
    .factory('Config', function ($resource) {
        return $resource('/rest/system/config/?app_name=admin', {}, { update: { method: 'PUT' }, query: {
            method: 'GET',
            isArray: false
        } });
    })
    .factory('EmailTemplates', function ($resource) {
        return $resource('/rest/system/email_template/:id/?app_name=admin&fields=*', {}, { update: { method: 'PUT' }
        });
    });


var setCurrentApp = function (currentApp) {
    $('.active').removeClass('active');
    $("#nav_" + currentApp).addClass("active");
};

var showFileManager = function () {
    $("#root-file-manager iframe").css('height', $(window).height() - 200).attr("src", CurrentServer + '/filemanager/').show();

};

window.onresize = resize;
window.onload = resize;
function resize() {
    $("#grid-table").css('height', $(window).height() - 60);
}

