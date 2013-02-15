M.qtype_cloud = {

    init: function(Y, params) {
        YUI.namespace('global');

        var loopCount = 0;

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
                            var info = [],
                                html = '', i;
                                
                            var output = "(Building Server. Please wait";
                            for (i=0; i<loopCount; i++) {
                                output += '.';
                            }
                            target.setContent(output + ")");
                            if (loopCount < 3) {
                                loopCount++;
                            } else {
                                loopCount = 0;
                            }

                           Y.log("RAW JSON DATA: " + o.responseText);
 
                           // Process the JSON data returned from the server
                           try {
                               info = Y.JSON.parse(o.responseText);
                           }
                           catch (e) {
                               target.setContent("JSON Parse failed!");
                               handle.cancel();
                               return;
                           }

                            if (info === undefined) {
                                target.setContent("Problem with settings sent to php script.");
                                handle.cancel();
                            }
 
                            if (info.itemNotFound !== undefined && info.itemNotFound.message !== undefined) {
                                target.setContent("Failed: " + info.itemNotFound.message + "    Code:" + info.itemNotFound.code);
                                handle.cancel();
                            }
                           
                           
                           if (info.server !== undefined && info.server.status !== undefined && info.server.status == "ACTIVE" &&
                                       info.server.addresses !== undefined && info.server.addresses.public !== undefined) {
                               var ipaddress = '';
                               for (i=0; i<info.server.addresses.public.length; i++) {
                                   if (info.server.addresses.public[i].version == 4) {
                                       ipaddress = info.server.addresses.public[i].addr;
                                       break;
                                   }
                               }
 
                              // Use the Node API to apply the new innerHTML to the target
                              target.setContent(ipaddress);

                              // replace all environment variables
                              body = Y.one(document.body);
                              var body_text = body.getContent();
                              body_text = body_text.replace("[%=" + params["class"] + "%]", ipaddress);
                              body.setContent(body_text);

                              handle.cancel();
                              return;
                          }
                      },
 
                        failure : function (x,o) {
                            target.setContent("Async call failed!");
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
           }
       });

        var handle = Y.later(3000, window, YUI.global.get_ip_address, [Y, params], true);
    }
};
