angular.module('app',['app.config','app.controllers','ws']);

angular.module('app.controllers',[])
.controller('appcontroller', ['$scope','websocket', function($scope,websocket) {
    $scope.valueofsomething = 'THIS IS VALUE OF SOMETHING';

    $scope.again = function(){
//        $scope.fromwebsocket = Math.random(10000);
        $scope.fromwebsocket = websocket.getCustomers();
        setTimeout(function(){
            $scope.again();$scope.$apply();
        }, 2000);
    }

    $scope.again();


//    $scope.fromwebsocket = websocket.getCustomers();
//        setTimeout(function(){$scope.fromwebsocket = websocket.getCustomers();}, 10000);
//        setTimeout(function(){alert(3);$scope.fromwebsocket = Math.random(10000);}, 2000);

}]);

angular.module('app.config',[])
.config(['$routeProvider', function($routeProvider) {
    $routeProvider
    .otherwise({
        template:'<div ng-controller="appcontroller">{{valueofsomething}}<p>{{fromwebsocket}}</p></div>'
    });
}]);

