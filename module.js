M.qtype_cloud = {

    init: function(Y, params) {

        // hide the question text until the ip environment variable is set with the ip address
        Y.one(".qtext").setStyle('display', 'none');
        
        YUI.namespace('global');

        var base_url = params['base_url'];
        var auth_token = params['auth_token'];
        var servers = params['servers'];

        
        var loopCount = 0;
        var endCount = 0;
        var ipaddress = '';
        
        YUI.add("uuu", function (Y) {
            var functions = {
                getServerInfo: function (Y, server_id) {
                    var callback = {
                        timeout : 5000,

                        responseType: 'json',
     
                        on : {
                            success : function (x,o) {
                                
                                Y.log("RAW JSON DATA: " + o.responseText);

                                // Process the JSON data returned from the server
                                try {
                                    info = Y.JSON.parse(o.responseText);
                                }
                                catch (e) {
                                    Y.log("JSON Parse failed!");
                                    return NULL;
                                }
                                
                                return info;

                            },
                            failure : function (x,o) {
                                Y.log("Async call failed!");
                                return NULL;
                            }
                        },
                        
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                    };
                    
                    Y.io(location.protocol + '//' + location.host + '/question/type/cloud/getipaddress.php?url=' + base_url + server_id + '&command_type=GET&extra_headers[]=X-Auth-Token:' + auth_token, callback);
                }
            };
            
            Y.server = functions;
        });


        YUI().use('uuu', 'io-base', 'dump', 'querystring-stringify-simple', function(Y) {
            YUI.global.get_ip_address = function(Y, handle_i, server_info) {
                            
                Y.JSON.useNativeParse = true;
                
                var target = Y.one('.' + server_info["class"]);

                if (target) {
                
                    info = Y.server.getServerInfo(Y, server_info['id']);

                    if (info === undefined) {
                        target.setContent("Problem with settings sent to php script.");
                        handle[handle_i].cancel();
                    }

                    if (info.itemNotFound !== undefined && info.itemNotFound.message !== undefined) {
                        target.setContent("Failed: " + info.itemNotFound.message + "    Code:" + info.itemNotFound.code);
                        handle[handle_i].cancel();
                    }
                
                    switch (info.server.status) {

                        case 'reuse':
                            break;
                        
                        case 'new':
                            break;

                        case 'BUILD':
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      
                            if (ipaddress.length === 0 && info.server !== undefined && info.server.addresses !== undefined && info.server.addresses.public !== undefined) {
                                for (i=0; i<info.server.addresses.public.length; i++) {
                                    if (info.server.addresses.public[i].version == 4) {
                                        ipaddress = info.server.addresses.public[i].addr;
                                        break;
                                    }
                                }

                                // replace all environment variables
                                body = Y.one(document.body);
                                var body_text = body.getContent();
                                body_text = body_text.split("[%=" + server_info["class"] + "%]").join(ipaddress);
                                body.setContent(body_text);

                                // reveal the question text with the IP environment variable replaced
//                                            Y.one(".qtext").setStyle('display', 'inline');
                            }
                            break;
                            
                        case 'PASSWORD':
                            break;

                        case 'ACTIVE':
                            // wait a few cycles to ensure that the process is done
                            break;

                        default:
                            break;
                    }
                } else {
                    target.setContent("Could Not Find the Server IP field for server number " + (handle_i+1));
                    handle[handle_i].cancel();
                    return;
                }
            };
        });
        
        var handle = new Array();
        for (var i=0; i<servers.length; i++) {
            handle.push(Y.later(3000, window, YUI.global.get_ip_address, [Y, i, servers[i]], true));
        }
        
    }
};


           
/*
                        success : function (x,o) {
                            var info = [],
                                html = '', i;
                                
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
                           
                           
                           if (ipaddress.length === 0 && info.server !== undefined && info.server.addresses !== undefined && info.server.addresses.public !== undefined) {
                               for (i=0; i<info.server.addresses.public.length; i++) {
                                   if (info.server.addresses.public[i].version == 4) {
                                       ipaddress = info.server.addresses.public[i].addr;
                                       break;
                                   }
                               }

                              // replace all environment variables
                              body = Y.one(document.body);
                              var body_text = body.getContent();
                              body_text = body_text.split("[%=" + params["class"] + "%]").join(ipaddress);
                              body.setContent(body_text);

                              // reveal the question text with the IP environment variable replaced
                              Y.one(".qtext").setStyle('display', 'inline');
                           }

                           // Do this check a few times before continuing.  (weird bug fix)
                           if (endCount < 2) {
                               endCount++;
                           } else
                           if (ipaddress.length !==0 && info.server!== undefined && info.server.status !== undefined && info.server.status === "ACTIVE") {
                              // Use the Node API to apply the new innerHTML to the target
                              target.setContent(ipaddress);

                              handle.cancel();
                              return;
                           }

                           var output = ((ipaddress.length === 0)? "(Building Server. " : ipaddress + " (Configuring Server. " ) + "Please wait";
                           for (i=0; i<loopCount; i++) {
                               output += '.';
                           }
                           target.setContent(output + ")");
                           if (loopCount < 3) {
                               loopCount++;
                           } else {
                               loopCount = 0;
                           }

                        },

*/
