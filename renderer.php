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

    public function questionid_column_name() {
        return 'questionid';
    }

    public function account_fields() {
        return array('question_cloud_account', 'username', 'password', 'auth_token', 'api_key', 'api_auth_token', 'region');
    }

    public function lb_fields() {
        return array('question_cloud_lb', 'lb_name', 'vip');
    }

    public function server_fields() {
        return array('question_cloud_server', 'num', 'imagename', 'slicesize');
    }

    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {
        global $OUTPUT, $DB, $PAGE, $CFG;

        $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/question/type/cloud/module.js'));
        $this->jsmodule = array('name'=>'qtype_cloud', 'fullpath'=>'/question/type/cloud/module.js', 'requires'=>array('base', 'io', 'node', 'json'), 'strings'=>array());

        $question = $qa->get_question();

        $cloud_text = $this->create_cloud_assets($question) . '<br /><br /><br /> <br />';

        $html = html_writer::tag('div', $qa->get_question()->format_text($cloud_text, FORMAT_HTML, $qa, 'cloud', 'accountinfo', $question->id));

        $html .= html_writer::tag('div', $qa->get_question()->format_questiontext($qa),
                    array('class' => 'qtext'));

        return $html;
    }

    private function create_cloud_assets($question) {
        global $OUTPUT, $USER, $PAGE;

        // Always call this when the question is created to help insure the token is up to date
        // and to update the services.
        $response = $this->authorize($question);

//        echo $OUTPUT->notification(var_dump($response));
//        echo $OUTPUT->notification(var_dump($question));

        $display_text = '';
        if (!empty($response->unauthorized)) { // Unauthorized username and password
            return '<center><font color="red">Failed to authorize based on given credentials.  Contact question administrator.<br />Code: ' . $response->unauthorized->code . '<br />' . $response->unauthorized->message . '</font></center>';

        } elseif (empty($response->access->token->id)) { // All other unwanted instances
            return '<center><font color="red">Failed to connect to host.  Contact question administrator.<br />' . var_dump($response) . '</font></center>';
        }

        $ac_auth_token = $response->access->token->id;

        $display_text = $this->save_auth_token($question, $ac_auth_token);
        // Something went wrong accessing the account info
        if (!empty($display_text)) {
            return $display_text;
        }

        // get region value
        $region = 0;
        if (!empty($server->region)) {
            $region = $server->region;
        }

        // find end point for specified region
        $server_endpoint_url = '';
        foreach ($response->access->serviceCatalog as $service) {
            if ($service->name === 'cloudServersOpenStack') {
                $server_endpoint_url = $service->endpoints[$region]->publicURL;
                break;
            }
        }
        // Quick validity check
        if (empty($server_endpoint_url)) {
            return '<center><font color="red">Failed to find server service url.</font></center>';
        }


        // Get API authorization token
        $api_response = $this->api_authorize($question);
        if (!empty($api_response->unauthorized)) { // Unauthorized username and password
            return '<center><font color="red">Failed to authorize based on given credentials.  Contact question administrator.<br />Code: ' . $api_response->unauthorized->code . '<br />' . $api_response->unauthorized->message . '</font></center>';

        } elseif (empty($api_response->access->token->id)) { // All other unwanted instances
            return '<center><font color="red">Failed to connect to api host.  Contact question administrator.<br />' . var_dump($api_response) . '</font></center>';
        }

        $api_auth_token = $api_response->access->token->id;

        $display_text = $this->save_api_auth_token($question, $api_auth_token);
        // Something went wrong accessing the account info
        if (!empty($display_text)) {
            return $display_text;
        }


        // Get List of Server Images, Server Flavors, and Servers
        $lists = array();
        $lists["server_images"] = array('images/detail', 'images');
        $lists["server_flavors"] = array('flavors', 'flavors');
        $lists["servers"] = array('servers/detail', 'servers');
        $list = new stdClass();

        foreach ($lists as $field=>$options) {
            $list->$field = $this->get_list($server_endpoint_url . '/' . $options[0] , $ac_auth_token);
            if (!empty($list->$field->unauthorized)) {  // Token has expired
                $response = $this->authorize($question);

                $ac_auth_token = $response->access->token->id;

                $display_text = $this->save_auth_token($question, $ac_auth_token);
                // Something went wrong accessing the account info
                if (!empty($display_text)) {
                    return $display_text;
                }

                $list->$field = $this->get_list($server_endpoint_url . '/' . $options[0] , $ac_auth_token);
                if (empty($list->$field->{$options[1]})) {
                    return '<center><font color="red">Authorization token expired.  Failed to reauthenticate.</font></center>';
                }
            } elseif (empty($list->$field->{$options[1]})) {  // All other unwanted instances
                return '<center><font color="red">Failed to retrieve ' . preg_replace('~_~', ' ', $field) . '.</font></center>';
            }
        }

        // Build Servers
        $server_info = new stdClass();
        foreach ($question->servers as $key=>$server) {
            // Verify Server Image Name
            $server_image_id = NULL;
            $image_name = $question->servers[$key]->imagename;
            foreach ($list->server_images->images as $image) {
                if ($image->name === $image_name) {
                    $server_image_id = $image->id;
                    break;
                }
            }

            if (empty($server_image_id)) {
                return '<center><font color="red">Could not find server image name "'. $image_name .'" in list retreived from server</font></center>';
            }

            // Get Server Flavor
            $server_flavor_id = $question->servers[$key]->slicesize + 2;

            // Build server name.
            $server_name = array();
            $server_name[] = 'mdl';
            $server_name[] = $question->id;
            $server_name[] = '';  // $USER->username
            $server_name[] = $question->servers[$key]->num;

            $server_name[2] = substr(preg_replace('~\s+~', '_', trim($USER->username)), 0, 128-strlen(implode('_', $server_name)));
            $server_name = implode('_', $server_name);

            $server_info->name = $server_name;
            $server_info->image_name = $image_name;
            $server_info->flavor = $server_flavor_id;

            // See if server name already exists and rebuild it if it does.
            // If more than one exist then delete the extras.
            $found_existing_server = FALSE;
            foreach ($list->servers->servers as $server) {
                if ($server->name === $server_name) {
/*                    if (!$found_existing_server) {
                        $found_existing_server = TRUE;
                        // TODO: Rebuild the server with our image and resize to our size
                        echo $OUTPUT->notification('Rebuild Server');
                        // TODO: grab either public or private ip based on preference and use specified version
                        $server_info->ip = $server->addresses->public[0]->addr;
                        $server_info->username = 'admin';
                        $server_info->link = $server->links[1];
                        $server_info->id = $server->id;
                        // Cannot recover password so reset the admin password
//                        $server_info->password = $server->adminPass;

                    } else {*/
                        // Delete the server
                        // TODO: do authorization token check if it fails
                        var_dump($this->delete_server($question, $server_endpoint_url, $ac_auth_token, $server->id));
                        echo $OUTPUT->notification('Delete Server');
//                    }
                }
            }

            // Create the Server if we did not find one
//            $server_info = new stdClass();
            if (!$found_existing_server) {
                $server_response = $this->create_server($question, $server_endpoint_url, $ac_auth_token, $server_name, $server_image_id, $server_flavor_id);
                if (!empty($server_response->unauthorized)) {  // Token has expired
                    $response = $this->authorize($question);

                    $ac_auth_token = $response->access->token->id;

                    $display_text = $this->save_auth_token($question, $ac_auth_token);
                    // Something went wrong accessing the account info
                    if (!empty($display_text)) {
                        return $display_text;
                    }

                    $server_response = $this->create_server($question, $server_endpoint_url, $ac_auth_token, $server_name, $server_image_id, $server_flavor_id);
                    if (empty($server_response->server)) {
                        return '<center><font color="red">Authorization token expired.  Failed to reauthenticate.</font></center>';
                    }

                } elseif (empty($server_response->server)) {
                    return '<center><font color="red">Failed to create the server.<br />' . $server_response . '</font></center>';
                }

                // Store server info
                $server_info->ip = 'no ip yet';
                $server_info->username = 'admin';
                $server_info->password = $server_response->server->adminPass;
                $server_info->link = $server_response->server->links[1];
                $server_info->id = $server_response->server->id;
//                $server_info->id = 'server id number';

//                $server_response = $this->get_list($server_endpoint_url . '/servers' . $server_response->server->id, $ac_auth_token);
                $server_info->ip = '<span class="server_ip_' . $key . '">(Building Server. Please wait...)</span>';
            }

            // print out server info
            $display_text .= '<div style="margin:0 auto 0 auto;"><b>New Server created.</b><br />Name: ' . $server_info->name . '<br />IP: ' .
                    $server_info->ip . '<br />Username: ' . $server_info->username .
                    '<br />Password: ' . $server_info->password . '</div><br />';

            $PAGE->requires->js_init_call('M.qtype_cloud.init', array(array(
                     'class'=>'server_ip_'.$key,
                     'url'=>$server_endpoint_url . '/servers/' . $server_info->id . '/ips',
                     'auth_token'=>$ac_auth_token,
                     'div_class'=>'.server_ip_' .$key,
                     )), false, $this->jsmodule);
        }

        // get api auth token
        $ac_api_key = $question->api_key;
        $ac_api_auth_token = '';

        // do the same thing for cloud load balancers
        // do the same thing for cloud databases

        return $display_text;
    }


    private function delete_server($question, $server_endpoint_url, $ac_auth_token, $server_id){
        // Initialise extra header entries.
        $headers = array(
            sprintf('X-Auth-Token: %s' , $ac_auth_token),
            );

        $url = $server_endpoint_url . '/servers/' . $server_id;

        // Parse the returned json string
        $this->send_json_curl_request($url, 'DELETE', '', $headers);

        return '';
    }

    private function get_list ($url, $ac_auth_token) {
        // Initialise extra header entries.
        $headers = array(
            sprintf('X-Auth-Token: %s' , $ac_auth_token),
            );

        // Parse the returned json string
        return json_decode($this->send_json_curl_request($url, 'GET', '', $headers));
    }

    private function save_auth_token ($question, $token) {
        global $DB;
        $error_text = '';

        // Save the authorization token in the database
        $extratablefields = $this->account_fields();
        $table = array_shift($extratablefields);
        $questionidcolname = $this->questionid_column_name();

        $function = 'update_record';
        $db_options = $DB->get_record($table,
                array($questionidcolname => $question->id));
        if (!$db_options) { // oops but, shouldn't happen given our previous authorization unless the databse is being edited from another user
            // There is not an existing entry.  Initialize needed variables.
            $error_text = '<center><font color="red">Cannot acces the question information in the database. Contact question administrator.</font></center>';

            // Do not fix it because it could be the admin
/*            $function = 'insert_record';
            $db_options = new stdClass();
            $db_options->$questionidcolname = $question->id;
            foreach ($extratablefields as $field) {
                $db_options->$field = 
            }*/
        } else {
            $db_options->auth_token = $token;
            $DB->{$function}($table, $db_options);
        }

    }

    private function save_api_auth_token ($question, $token) {
        global $DB;
        $error_text = '';

        // Save the api authorization token in the database
        $extratablefields = $this->account_fields();
        $table = array_shift($extratablefields);
        $questionidcolname = $this->questionid_column_name();

        $function = 'update_record';
        $db_options = $DB->get_record($table,
                array($questionidcolname => $question->id));
        if (!$db_options) { // oops but, shouldn't happen given our previous authorization unless the databse is being edited from another user
            // There is not an existing entry.  Initialize needed variables.
            $error_text = '<center><font color="red">Cannot acces the question information in the database. Contact question administrator.</font></center>';

            // Do not fix it because it could be the admin
/*            $function = 'insert_record';
            $db_options = new stdClass();
            $db_options->$questionidcolname = $question->id;
            foreach ($extratablefields as $field) {
                $db_options->$field = 
            }*/
        } else {
            $db_options->api_auth_token = $token;
            $DB->{$function}($table, $db_options);
        }

    }

    private function create_server($question, $server_endpoint_url, $ac_auth_token, $server_name, $server_image_id, $server_flavor_id) {
        // Initialise the account authorization token variables.
        $ac_username = $question->username;
        $ac_password = $question->password;

        // Initialise the JSON request.
        $headers = array(
            sprintf('X-Auth-Token: %s', $ac_auth_token),
            sprintf('X-Auth-Project-Id: %d', $question->id),
            );

        $json_string = sprintf('{"server":{"name":"%s", "imageRef":"%s", "flavorRef":"%d", "metadata":{"My Server Name":"%s"}}}', $server_name, $server_image_id, $server_flavor_id, $server_name);

        $path = array();
        $path[] = $server_endpoint_url;
        $path[] = "servers";
        $url = implode("/", $path);

        // Perform the cURL request
        return json_decode($this->send_json_curl_request($url, 'POST', $json_string, $headers));
    }

    private function api_authorize ($question) {
        $api_username = $question->username;
        $api_key = $question->api_key;

        $json_string = sprintf('{"auth":{"RAX-KSKEY:apiKeyCredentials":{"username":"%s", "apiKey":"%s"}}}', $api_username, $api_key);

        $url = "https://identity.api.rackspacecloud.com/v2.0/tokens";

       return json_decode($this->send_json_curl_request($url, 'POST', $json_string));
    }

    private function authorize ($question) {
        global $OUTPUT;

        // Initialise the account authorization token variables.
        $ac_username = $question->username;
        $ac_password = $question->password;

        $json_string = sprintf('{"auth":{"passwordCredentials":{"username":"%s", "password":"%s"}}}', $ac_username, $ac_password);

        $url = "https://identity.api.rackspacecloud.com/v2.0/tokens";

        // Perform the cURL request
        return json_decode($this->send_json_curl_request($url, 'POST', $json_string));
    }

    public function send_json_curl_request ($url, $command_type = 'GET', $json_string = '', $extra_headers = array()) {
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

    public function formulation_heading () {
        return get_string('header', 'qtype_cloud');
    }
}
