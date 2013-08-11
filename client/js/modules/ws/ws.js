angular.module('ws',['ws.services','ws.controllers']);

angular.module('ws.services',[])
.factory('websocket', ['$q', '$rootScope', function($q, $rootScope) {

//    var connection = new WebSocket('ws://wsserver:8000');
//    
//    // When the connection is open, send some data to the server
//    connection.onopen = function () {
//      connection.send('Ping'); // Send the message 'Ping' to the server
//    };
//
//    // Log errors
//    connection.onerror = function (error) {
//      console.log('WebSocket Error ' + error);
//    };
//
//    // Log messages from the server
//    connection.onmessage = function (e) {
//      console.log('Server: ' + e.data);
//    };

    var Service = {},
        callbacks = {},
        currentCallbackId = 0,
        ws = new WebSocket("ws://wsserver:55555/");

    ws.onopen = function(){  
        console.log("Socket has been opened!");  
    };
    
    ws.onmessage = function(message) {
        listener(JSON.parse(message.data));
    };

    function sendRequest(request) {
      var defer = $q.defer();
      var callbackId = getCallbackId();
      callbacks[callbackId] = {
        time: new Date(),
        cb:defer
      };
      request.callback_id = callbackId;
//      console.log('Sending request', request,JSON.stringify(request));
        if (request.callback_id > 2) {
            console.log(request.callback_id);
//            for(var i=Math.ceil(Math.random(3));i>0;i--){
//                ws.send("abcde"+i);
//            }

            for(var i=Math.ceil(Math.random()*5); i>0; i--){
                ws.send("abcde"+i);
            }

        }
//      console.log(ws.send);
//      ws.send('fffffffff');
//      console.log(request);
//      ws.send(JSON.stringify(request));
      return defer.promise;
    }

    function listener(data) {
      var messageObj = data;
      console.log("Received data from websocket: ", messageObj);
      // If an object exists with callback_id in our callbacks object, resolve it
      if(callbacks.hasOwnProperty(messageObj.callback_id)) {
        console.log(callbacks[messageObj.callback_id]);
        $rootScope.$apply(callbacks[messageObj.callback_id].cb.resolve(messageObj.data));
        delete callbacks[messageObj.callbackID];
      }
    }

    function getCallbackId() {
      currentCallbackId += 1;
      if(currentCallbackId > 10000) {
        currentCallbackId = 0;
      }
      return currentCallbackId;
    }

    Service.getCustomers = function() {
      var request = {
        type: "get_customers"
      }
      // Storing in a variable for clarity on what sendRequest returns
      var promise = sendRequest(request); 
      return promise;
    }

    return Service;
}]);

angular.module('ws.controllers',['ws.services'])
.controller('a', ['$scope', function($scope){
    console.log('ggggggggg');
    document.write('tttttttt');
}]);