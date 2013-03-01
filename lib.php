<?php

function qtype_cloud_cron() {
    global $DB;

    // User Preferences
    $max_server_lifetime = 6; // in hours

    $questions = $DB->get_records('question', array('qtype'=>'cloud'), 'id', 'id');
    mtrace('Found ' . count($questions) . ' cloud question instance(s).');

    foreach ($questions as $key=>$q) {
        mtrace('(I' . $key . ' id:' . $q->id . ')');

        // Get Account Info for Question Instance
        if (! $account = $DB->get_record('question_cloud_account', array('questionid'=>$q->id), 'username,password')) {
            mtrace('  Failed to retrieve account info from table question_cloud_account');
            continue;
        }

        // Get API Authorization
        $auth_res = authorize($account);
        if (!empty($auth_res->unauthorized)) {
            mtrace('  Failed to authorize the account.');
            continue;
        } elseif (empty($auth_res->access->token->id)) {
            mtrace('  Failed to retrieve useful information from auth request.');
            continue;
        }

        // Get Server Endpoint
        $server_end = get_server_endpoint($auth_res);
        if (empty($server_end)) {
            mtrace(' TODO: server enpoint url empty.');
            continue;
        }

        // Get a list of existing servers
        $servers = get_servers($auth_res, $server_end);

        $q_server_info = $DB->get_records('question_cloud_server', null, 'questionid,num');

        $attempts = $DB->get_records('question_attempts', array('questionid'=>$q->id), null, 'id');
        mtrace('  ' . count($attempts) . ' current attempt(s).');

        // Check if servers are for current attempt


        foreach ($attempts as $attempt) {
            $attempt_steps = $DB->get_records('question_attempt_steps', array('questionattemptid'=>$attempt->id), null, 'state,timecreated,userid');

            // Check if user is still enrolled in course


            // Check if user has had the servers associated with the attempt
            // for more than a specified amount of time.
        }
    }

    // Check if servers are for current question instances

}

function get_server_endpoint($auth_res) {
    return get_endpoint($auth_res, 'cloudServersOpenStack');
}

function get_endpoint($auth_res, $service_name) {
    foreach ($auth_res->access->serviceCatalog as $service) {
        if ($service->name === $service_name) {
            foreach ($service->endpoints as $endpoint) {
                if ($endpoint->region === "DFW") {
                    return $endpoint->publicURL;
                }
            }
        }
    }
}

function get_servers ($auth_res , $endpoint) {
    $res = get_list($auth_res, $endpoint . '/servers');
    if (!empty($res->unauthorized)) {
        // re-get API Authorization
        $a_res = authorize($account);
        $auth_res->access = $a_res->access;

        $res = get_list($auth_res, $endpoint . '/servers');
        if (empty($res->servers)) {
            mtrace('  Could not reauthorize the account.');
            return;
        }
    } elseif (empty($res->servers)) {
        mtrace('  Failed to retrieve useful information with server list request.');
        return;
    }

    return $res->servers;
}

function get_list($auth_res, $endpoint) {
    $token = $auth_res->access->token->id;

    // Initialise extra header entries.
    $headers = array(
        sprintf('X-Auth-Token: %s' , $token),
        );

    // Parse the returned json string
    $res = json_decode(send_json_curl_request($endpoint, 'GET', '', $headers));


    return $res;
}

function authorize ($account) {
    global $OUTPUT;

    // Initialise the account authorization token variables.
    $ac_username = $account->username;
    $ac_password = $account->password;

    $json_string = sprintf('{"auth":{"passwordCredentials":{"username":"%s", "password":"%s"}}}', $ac_username, $ac_password);

    $url = "https://identity.api.rackspacecloud.com/v2.0/tokens";

    // Perform the cURL request
    return json_decode(send_json_curl_request($url, 'POST', $json_string));
}

function send_json_curl_request ($url, $command_type = 'GET', $json_string = '', $extra_headers = array()) {
    // Build the header.
    $headers = array(
        'Content-Type: application/json',
        'Accept: application/json',
        );
    $headers = array_merge($headers, $extra_headers);

    // Perform the cURL request
    $curl_ch = curl_init($url);
    curl_setopt($curl_ch, CURLINFO_HEADER_OUT, 1);  // Output message is displayed
    curl_setopt($curl_ch, CURLOPT_RETURNTRANSFER, 1);  // Make silent
    curl_setopt($curl_ch, CURLOPT_CUSTOMREQUEST, $command_type);  // HTTP Post
    curl_setopt($curl_ch, CURLOPT_HTTPHEADER, $headers);  // Set headers
    curl_setopt($curl_ch, CURLOPT_POSTFIELDS, $json_string);  // Set data
    $curl_result = curl_exec($curl_ch);
    curl_close($curl_ch);

    // Parse the returned json string
    return $curl_result;
}
