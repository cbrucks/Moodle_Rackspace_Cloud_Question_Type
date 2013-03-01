<?php

function qtype_cloud_cron() {
    global $DB;

    // User Preferences
    $max_server_lifetime = 6; // in hours

    $questions = $DB->get_records('question', array('qtype'=>'cloud'), 'id', 'id');
    mtrace('Found ' . count($questions) . ' cloud question instance(s).');

    // Delete all unused servers for each existing question instance
    foreach ($questions as $key=>$q) {
        mtrace('(I' . $key . ' id:' . $q->id . ')');

        // Get all courses containing this question
        $course_ids = array();
        if ($quizes = $DB->get_records('quiz', null, '', 'id,course,questions')){
            foreach ($quizes as $quiz) {
                $question_ids = explode(',', $quiz->questions);
                if (in_array($q->id, $question_ids)) {
                    $course_ids[] = $quiz->course;
                }
            }
        } else {
            mtrace('  Failed to find any courses.');
            return;
        }
        $course_ids = array_unique($course_ids);
        mtrace('  Found question in course(s): ' . implode(',', $course_ids));

        // Get enrolment method ids for the containing courses
        $enrol_ids = array();
        foreach ($course_ids as $course_id) {
            if ($enrolment_method_ids = $DB->get_records('enrol', array('courseid'=>$course_id), '', 'id')) {
                $enrol_ids = array_merge($enrol_ids, array_keys($enrolment_method_ids));
            } else {
                mtrace('  Could not find enrolment methods for course id : ' . $course_id);
            }
        }
        mtrace('  Enrolment method ids associated with this question: ' . implode(',', $enrol_ids));

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

        // Get Server Info from database
        if (! $q_server_info = $DB->get_records('question_cloud_server', array('questionid'=>$q->id), null, 'num,srv_name')) {
            mtrace('  Failed to retreive server information for the account.');
            continue;
        }

        // Get attempts associated with this question
        if (! $attempts = $DB->get_records('question_attempts', array('questionid'=>$q->id), null, 'questionusageid')) {
            mtrace('  Failed to retreive question attempt information.');
            continue;
        }
        $attempts = array_keys($attempts);
        mtrace('  ' . count($attempts) . ' current attempt(s). (' . implode(', ', $attempts) . ')');

        foreach ($servers as $server) {
            foreach ($q_server_info as $s_info) {
                // Look for the question admin specified server name at the beginning of the name
                if (preg_match('/^' . $s_info->srv_name . '\./', $server->name)) {
                    $server_name = substr($server->name, strlen($s_info->srv_name) + 1); // remove the user specified name and period
                    $server_name = explode('_', $server_name); // seperate the information
                    $len = count($server_name); // get the length of the array

                    // Ensure the rest of the name fits the overal schema
                    if ($len >= 4) {
                        $server_num = $server_name[$len-1]; // save the server number
                        $server_q_attempt = $server_name[$len-2]; // save the server question attempt
                        $server_q_id = $server_name[$len-3]; // save the server question id
                        $user_name = implode('_', array_slice($server_name, 0, $len-3)); // put the username back together

                        // Ensure the information pulled from the name is of the correct format
                        if (is_numeric($server_num) && is_numeric($server_q_attempt) && is_numeric($server_q_id) && strlen($user_name) > 0) {
                            if (!in_array($server_q_attempt, $attempts)) {
                                // Old question attempt
                                mtrace('  DELETE: old attempt.      server : ' . $server->name);
                                delete_server($auth_res, $server_end, $server);
                            } elseif ($user = $DB->get_record('user', array('username'=>$user_name), 'id')) {
                                // User exists
                                $enroled = FALSE;
                                foreach ($enrol_ids as $enrol_id) {
                                    if ($DB->count_records('user_enrolments', array('userid'=>$user->id, 'enrolid'=>$enrol_id))) {
                                        // User is enrolled in the course
                                        $enroled = TRUE;
                                        break;
                                    }
                                }
                                if ($enroled) {
                                    // User is enrolled in the course

                                    if ($server_num > count($q_server_info)) {
                                        // Server is an extra server
                                        mtrace('  DELETE: extra server.     server : ' . $server->name);
                                        delete_server($auth_res, $server_end, $server);
                                    }
                                } else {
                                    // User is not enrolled in the course
                                    mtrace('  DELETE: user unenrolled.  server : ' . $server->name);
                                    delete_server($auth_res, $server_end, $server);
                                }
                            } else {
                                // User doesn't exist
                                mtrace('  DELETE: user DNE.         server : ' . $server->name);
                                delete_server($auth_res, $server_end, $server);
                            }
                        }
                    }
                }
            }
        }


//        foreach ($attempts as $attempt) {
//            $attempt_steps = $DB->get_records('question_attempt_steps', array('questionattemptid'=>$attempt->id), null, 'state,timecreated,userid');

            // Check if user is still enrolled in course


            // Check if user has had the servers associated with the attempt
            // for more than a specified amount of time.
//        }
    }

    // any additional servers that match naming scheme but don't belong to any question instance
    // should be taken care of during the question delete process

}

function delete_server($auth_res, $endpoint, $server) {
    // Initialise extra header entries.
    $headers = array(
        sprintf('X-Auth-Token: %s' , $auth_res->token->id),
        );

    $url = $endpoint . '/servers/' . $server->id;

    // Parse the returned json string
    send_json_curl_request($url, 'DELETE', '', $headers);

    return '';
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
