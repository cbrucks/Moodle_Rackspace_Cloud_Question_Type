M.qtype_cloud = {

    init: function(Y, params) {
        YUI.namespace('global');

        YUI().use('io-base', 'dump', 'querystring-stringify-simple', function(Y) {
           YUI.global.get_ip_address = function(Y,params) {

               Y.JSON.useNativeParse = true;

               var target = Y.one(params["div_class"]);
           
               // Create the io callback/configuration
               var callback = {
 
                   timeout : 3000,

                   responseType: 'json',
 
                   on : {
                       success : function (x,o) {
//                           Y.log("RAW JSON DATA: " + o.responseText);
 
                           var info = [],
                               html = '', i, l;
 
                           // Process the JSON data returned from the server
                           try {
                               info = Y.JSON.parse(o.responseText);
                           }
                           catch (e) {
                               alert("JSON Parse failed!");
                               handle.cancel();
                               return;
                           }
 
                           if (info == undefined) {
                               target.setContent("Problem with settings sent to php script.");
                               handle.cancel();
                           }
 
                           if (info !== undefined && info.itemNotFound !== undefined && info.itemNotFound.message !== undefined) {
                               target.setContent("Failed: " + info.itemNotFound.message + " code:" + info.itemNotFound.code);
                               handle.cancel();
                           }
 
                           if (info !== undefined && info.addresses !== undefined && info.addresses.public !== undefined) {
//                               Y.log("PARSED DATA: " + Y.dump(info.addresses.public[0].addr));

                               var ipaddress = '';
                               for (i=0; i<info.addresses.public.length; i++) {
                                   if (info.addresses.public[i].version == 4) {
                                       ipaddress = info.addresses.public[i].addr;
                                       break;
                                   }
                               }
 
                              // Use the Node API to apply the new innerHTML to the target
                              target.setContent(ipaddress);
                              handle.cancel();
                              return;
                          }
                      },
 
                      failure : function (x,o) {
                          alert("Async call failed!");
                          handle.cancel();
                          return;
                      }
 
                  },

                  method: 'GET',
                  headers: {
                      'Content-Type': 'application/json',
                      'Accept': 'application/json',
                  },
              };              

              Y.io(location.protocol + '//' + location.host + '/question/type/cloud/getipaddress.php?url=' + params["url"] + '&command_type=GET&extra_headers[]=X-Auth-Token:' + params["auth_token"], callback);
              Y.log(location.protocol + '//' + location.host + '/question/type/cloud/getipaddress.php?url=' + params["url"] + '&command_type=GET&extra_headers[]=X-Auth-Token:' + params["auth_token"]);
           }
       });

       var handle = Y.later(10000, window, YUI.global.get_ip_address, [Y, params], true);
    }
};


