M.qtype_cloud = {

    init: function(Y, params) {
        YUI.namespace('global');

        YUI().use('io-base', 'json', 'querystring-stringify-simple', function(Y) {
           YUI.global.get_ip_address = function(Y,params) {
               // Create the io callback/configuration
               var callback = {
 
                   timeout : 3000,
 
                   on : {
                       success : function (x,o) {
                           Y.log("RAW JSON DATA: " + o.responseText);
 
                           var messages = [],
                               html = '', i, l;
 
                           // Process the JSON data returned from the server
                           try {
                               messages = Y.JSON.parse(o.responseText);
                           }
                           catch (e) {
                               alert("JSON Parse failed!");
                               handle.cancel();
                               return;
                           }
 
                           Y.log("PARSED DATA: " + Y.Lang.dump(messages));
 
                          // The returned data was parsed into an array of objects.
                          // Add a P element for each received message
                          for (i=0, l=messages.length; i < l; ++i) {
                          html += '<p>' + messages[i].animal + ' says &quot;' +
                                          messages[i].message + '&quot;</p>';
                          }
 
                          // Use the Node API to apply the new innerHTML to the target
                          target.setContent(html);
                      },
 
                      failure : function (x,o) {
                          alert("Async call failed!");
                          handle.cancel();
                      }
 
                  }
              };
              
              Y.io(location.protocol + '//' + location.host + '/question/type/cloud/getipaddress.php?url=' + params["url"] + '&command_type=GET&extra_headers=X-Auth_Token:' + params["auth_token"], callback);
           }
       });

       var handle = Y.later(2000, window, YUI.global.get_ip_address, [Y, params], true);
    }
};


