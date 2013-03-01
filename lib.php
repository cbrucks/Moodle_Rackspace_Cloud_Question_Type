<?php

function qtype_cloud_cron() {
    global $DB;

    // User Preferences
    $max_server_lifetime = 6; // in hours

    // Get a list of servers
    $servers = get_servers();

    $questions = $DB->get_records('question', array('qtype'=>'cloud'), 'id', 'id');
    mtrace('Found ' . count($questions) . ' cloud question instance(s).');

    $question_ids = array();
    foreach ($questions as $key=>$q) {
        $question_ids[] = $q->id;

        $q_server_info = $DB->get_records('question_cloud_server', null, 'questionid,num');

        mtrace('(I' . $key . ' id:' . $q->id . ')');
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

function get_servers() {

}

function authorize ($question) {
    global $OUTPUT;

    // Initialise the account authorization token variables.
    $ac_username = $question->username;
    $ac_password = $question->password;

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


