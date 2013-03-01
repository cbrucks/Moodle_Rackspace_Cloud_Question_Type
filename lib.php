<?php

function qtype_cloud_cron() {
    global $DB;

    $max_server_lifetime = 6; // in hours

    $questions = $DB->get_records('question', array('qtype'=>'cloud'), 'id', 'id');
    mtrace('Found ' . count($questions) . ' cloud question instance(s).');

    $question_ids = array();
    foreach ($questions as $key=>$q) {
        $question_ids[] = $q->id;

        $q_server_info = $DB->get_records('question_cloud_server', null, 'questionid,num');
        mtrace(var_dump($q_server_info));

        mtrace('(I' . $key . ')');
        $attempts = $DB->get_records('question_attempts', array('questionid'=>$q->id), null, 'id');
        mtrace('    ' . count($attempts) . ' current attempt(s).');

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

