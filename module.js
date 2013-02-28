M.qtype_cloud = {

    init: function(Y, params) {

        YUI.namespace('global');


        var loopCount = 0;
        var endCount = 0;
        var base_url = params['base_url'];
        var auth_token = params['auth_token'];
        var servers = params['servers'];


        // hide the question text until the ip environment variable is set with the ip address
        Y.one(".qtext").setStyle('display', 'none');

        YUI().use('io-base', 'dump', 'querystring-stringify-simple', function(Y) {
            YUI.global.resetPassword = function(Y, server_id, new_password) {
                var callback = {
                    on : {
                        success : function (x,o) {
                        },
                        
                        failure : function (x,o) {
                        },
                    },
                    
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                };
                
                Y.io(location.protocol + '//' + location.host + '/question/type/cloud/makeAPIcall.php?url=' + base_url + server_id + '/action&command_type=POST&extra_headers[]=X-Auth-Token:' + auth_token + '&json_string={"changePassword":{"adminPass":"' + new_password + '"}}', callback);
            }

            YUI.global.get_ip_address = function(Y, handle_i, server_info) {
                Y.JSON.useNativeParse = true;

                target[handle_i] = Y.one('.' + server_info["class"]);
           
                // Create the io callback/configuration
                var callback = {
 
                    timeout : 5000,

                    responseType: 'json',
 
                    on : {
                        success : function (x,o) {
                            var info = [], i;
                                
                           Y.log("RAW JSON DATA: " + o.responseText);
 
                           // Process the JSON data returned from the server
                           try {
                               info = Y.JSON.parse(o.responseText);
                           }
                           catch (e) {
                               target[handle_i].setContent("JSON Parse failed!");
                               handle[handle_i].cancel();
                               handle[handle_i] = null;
                               return;
                           }

                            if (info === undefined) {
                                target[handle_i].setContent("Problem with settings sent to php script.");
                                handle[handle_i].cancel();
                                handle[handle_i] = null;
                                return;
                            }
 
                            if (info.itemNotFound !== undefined && info.itemNotFound.message !== undefined) {
                                target[handle_i].setContent("Failed: " + info.itemNotFound.message + "    Code:" + info.itemNotFound.code);
                                handle[handle_i].cancel();
                                handle[handle_i] = null;
                                return;
                            }
                           
                           
                           if (ipaddress[handle_i].length === 0 && info.server !== undefined && info.server.addresses !== undefined && info.server.addresses.public !== undefined) {
                               for (i=0; i<info.server.addresses.public.length; i++) {
                                   if (info.server.addresses.public[i].version == 4) {
                                       ipaddress[handle_i] = info.server.addresses.public[i].addr;
                                       break;
                                   }
                               }

                              // replace all environment variables
                              var body = Y.one(document.body);
                              var body_text = body.getContent();
                              body_text = body_text.split("[%=" + server_info["class"] + "%]").join(ipaddress[handle_i]);
                              body.setContent(body_text);

                              // reveal the question text with the IP environment variable replaced
                              
                              for (i=0; i<ipaddress.length; i++) {
                                  if (ipaddress[i].length === 0) {
                                      break;
                                  } else
                                  if (i === ipaddress.length-1) {
                                      Y.one(".qtext").setStyle('display', 'inline');
                                  }
                              }
                           }

                           // Do this check a few times before continuing.  (weird bug fix)
                           if (endCount < 3) {
                               endCount++;
                           } else
                           if (ipaddress[handle_i].length !==0 && info.server!== undefined && info.server.status !== undefined && info.server.status === "ACTIVE") {
                              // If this is a reused server reset the password
                              if (server_info["status"] === 'reuse') {
                                  YUI.global.resetPassword(Y, server_info["id"], server_info["password"]);
                                  server_info["status"] = 'done';
                                  endCount = 0;
                                  return;
                              }

                              // Use the Node API to apply the new innerHTML to the target
                              target[handle_i].setContent(ipaddress[handle_i]);


                              handle[handle_i].cancel();
                              handle[handle_i] = null;
                              return;
                           }

                           var output = ((ipaddress[handle_i].length === 0)? "(Building Server. " : ipaddress[handle_i] + " (Configuring Server. " ) + "Please wait";
                           for (i=0; i<loopCount; i++) {
                               output += '.';
                           }
                           target[handle_i].setContent(output + ")");
                           if (loopCount < 3) {
                               loopCount++;
                           } else {
                               loopCount = 0;
                           }

                      },
 
                        failure : function (x,o) {
                            target[handle_i].setContent("Async call failed!");
                            handle[handle_i].cancel();
                            handle[handle_i] = null;
                            return;
                        }
 
                    },

                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                };              
                
                Y.io(location.protocol + '//' + location.host + '/question/type/cloud/makeAPIcall.php?url=' + base_url + server_info['id'] + '&command_type=GET&extra_headers[]=X-Auth-Token:' + auth_token, callback);
           }
       });

        var target = new Array();
        var ipaddress = new Array();
        var handle = new Array();
        for (var i=0; i<servers.length; i++) {
            ipaddress.push('');
            target.push(null);
            handle.push(Y.later(3000, window, YUI.global.get_ip_address, [Y, i, servers[i]], true));
        }
    }
};
