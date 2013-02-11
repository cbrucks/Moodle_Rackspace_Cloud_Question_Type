M.qtype_cloud = {

    init: function(Y, params) {
        YUI.namespace('global');
        YUI().use('io-base', 'node', 'event', 'json', function(Y) {
            YUI.global.get_ip_address = function(Y, params) {
                Y.io(params["url"], {
                    method: "GET",
                    headers: {
                        'X-Auth-Token' : params["auth_token"],
                    },
                    
                });

                Y.on('io:success', function(id, response, params) {
                        alert('success');
                    },
                    Y, params);
                Y.on('io:failure', function(id, response, params) {
                        alert('failure ' + response.responseText);
                    },
                    Y, params);
            };
       });

       var handle = Y.later(2000, window, YUI.global.get_ip_address, [Y, params], true);
    }
};


