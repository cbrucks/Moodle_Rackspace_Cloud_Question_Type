M.qtype_cloud = {

    init: function(Y, params) {
        YUI.namespace('global');
        YUI().use('io-base', 'node', 'event', 'json', function(Y) {
            YUI.global.get_ip_address = function(Y, params) {
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
                                return;
                            }

                            Y.log("PARSED DATA: " + Y.Lang.dump(messages));

                            // The returned data was parsed into an array of objects.
                            // Add a P element for each received message
                            for (i=0, l=messages.length; i < l; ++i) {
                                html += '<p>' + messages[i].animal + ' says "' +
                                                messages[i].message + '"</p>';
                            }

                            // Use the Node API to apply the new innerHTML to the target
                            target.setHTML(html);
                        },

                        failure : function (x,o) {
                            alert("Async call failed!");
                        }

                    }
                };

                Y.io(params["url"], callback, Y, []);
            };
       });

       var handle = Y.later(2000, window, YUI.global.get_ip_address, [Y, params], true);
    }
};


