<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Cloud 'question' renderer class.
 *
 * @package    qtype
 * @subpackage cloud
 * @copyright  2013 Chris Brucks
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Generates the output for cloud 'question's.
 *
 * @copyright  2013 Chris Brucks
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_cloud_renderer extends qtype_renderer {
    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {
        global $OUTPUT, $DB;

        $question = $qa->get_question();

        $response = $this->authorize($question);

//        echo $OUTPUT->notification(var_dump($response));

        $cloud_text = '';
        if (!empty($response->unauthorized)) {
            $cloud_text = '<center><font color="red">Failed to authorize based on given credentials.  Contact question administrator.<br />Code: ' . $response->unauthorized->code . '<br />' . $response->unauthorized->message . '</font><br /><br /><br /> <br /></center>';

        } elseif (!empty($response->access->token->id)) {
            $ac_auth_token = $response->access->token->id;

            // Save the authorization token in the database
            // TODO: use public function to save table name and column names like in questiontype.php
            $table = 'question_cloud_account';
            $questionidcolname = 'questionid';
            $function = 'update_record';
            $db_options = $DB->get_record($table,
                    array($questionidcolname => $question->id));
            if (!$db_options) { // oops but, shouldn't happen given our previous authorization
//                // There is not an existing entry.  Initialize needed variables.
//                $function = 'insert_record';
//                $options = new stdClass();
//                $options->$questionidcolname = $question->id;
            } else {
                $db_options->auth_token = $response->access->token->id;
                $DB->{$function}($table, $db_options);
            }


            // get api auth token
            foreach ($question->servers as $key=>$server) {
                foreach ($server as $field=>$value) {
                    // get server region value
                    // find end point for specified region
                    // build json request
                    // review json response for confirmation of server creation
                    // print out server info
                }
            }

            // do the same thing for cloud load balancers
            // do the same thing for cloud databases

        } else {
            $cloud_text = '<center><font color="red">Failed to connect to host.  Contact question administrator.<br />' . var_dump($response) . '</font><br /><br /><br /> <br /></center>';
        }



        $ac_api_key = $question->api_key;
        $ac_api_auth_token = '';


        $html = html_writer::tag('div', $qa->get_question()->format_text($cloud_text, FORMAT_HTML, $qa, 'cloud', 'accountinfo', $question->id));

        $html .= html_writer::tag('div', $qa->get_question()->format_questiontext($qa),
                    array('class' => 'qtext'));


        return $html;
    }

    private function authorize ($question) {
        global $OUTPUT;

        // Initialise the account authorization token variables.
        $ac_username = $question->username;
        $ac_password = $question->password;

        // Initialise the JSON request.
        $headers = array(
            'Content-Type: application/json',
            'Accept: application/json',
            );

        $json_string = sprintf('{"auth":{"passwordCredentials":{"username":"%s", "password":"%s"}}}', $ac_username, $ac_password);

        $path = array();
        $path[] = "https://identity.api.rackspacecloud.com";
        $path[] = "v2.0";
        $path[] = "tokens";
        $url = implode("/", $path);

        // Perform the cURL request
        $curl_ch = curl_init($url);
        curl_setopt($curl_ch, CURLINFO_HEADER_OUT, 1);  // Output message is displayed
        curl_setopt($curl_ch, CURLOPT_RETURNTRANSFER, 1);  // Make silent
        curl_setopt($curl_ch, CURLOPT_CUSTOMREQUEST, 'POST');  // HTTP Post
        curl_setopt($curl_ch, CURLOPT_HTTPHEADER, $headers);  // Set headers
        curl_setopt($curl_ch, CURLOPT_POSTFIELDS, $json_string);  // Set data
        $curl_result = curl_exec($curl_ch);

        echo curl_getinfo($curl_ch, CURLINFO_HEADER_OUT).'\n';

        curl_close($curl_ch);

//        echo $OUTPUT->notification($curl_result);

        // Parse the returned json string
        return json_decode($curl_result);
    }

    public function formulation_heading() {
        return get_string('header', 'qtype_cloud');
    }
}
