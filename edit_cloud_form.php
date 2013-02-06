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
 * Defines the editing form for the cloud question type.
 *
 * @package    qtype
 * @subpackage cloud
 * @copyright  2013 Chris Brucks
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Cloud editing form definition.
 *
 * @copyright  2013 Chris Brucks
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_cloud_edit_form extends question_edit_form {
    /**
     * Add question-type specific form fields.
     *
     * @param MoodleQuickForm $mform the form being built.
     */
    protected function definition_inner($mform) {
        // We don't need this default element.
        $mform->removeElement('defaultmark');
        $mform->addElement('hidden', 'defaultmark', 0);
        $mform->setType('defaultmark', PARAM_RAW);
        $mform->removeElement('generalfeedback');
        $mform->addElement('hidden', 'generalfeedback', 0);
        $mform->setType('generalfeedback', PARAM_RAW);

        $mform->addElement('header', 'ca_header', get_string('ca_header', 'qtype_cloud'));
        $mform->addElement('text', 'username', get_string('ca_username', 'qtype_cloud'));
        $mform->addElement('passwordunmask', 'password', get_string('ca_password', 'qtype_cloud'));
        $mform->addElement('passwordunmask', 'api_key', get_string('ca_api_key', 'qtype_cloud'));

        $mform->addRule('username', null, 'required', null, 'client');
        $mform->addRule('password', null, 'required', null, 'client');
        $mform->addRule('api_key', null, 'required', null, 'client');

        $mform->addElement('header', 'lb_header', get_string('lb_header', 'qtype_cloud'));
        $mform->addElement('text', 'lb_name', get_string('lb_name', 'qtype_cloud'));
        $mform->addElement('select', 'vip', get_string('lb_vip', 'qtype_cloud'), array(get_string('lb_vip_public', 'qtype_cloud'), get_string('lb_vip_private', 'qtype_cloud')));
        $mform->addElement('select', 'region', get_string('lb_region', 'qtype_cloud'), array(get_string('lb_region_dfw', 'qtype_cloud'), get_string('lb_region_ord', 'qtype_cloud')));

        $this->add_per_answer_fields($mform, get_string('serverno', 'qtype_cloud', '{no}'),
                question_bank::fraction_options_full(), 1, 1);
    }

    protected function get_per_answer_fields($mform, $label, $gradeoptions,
            &$repeatedoptions, &$answersoption) {

        $repeated = array();
        $repeated[] = $mform->createElement('header', 'server_header', $label);
        $repeated[] = $mform->createElement('text', 'imagename', get_string('srv_image', 'qtype_cloud'));
        $repeated[] = $mform->createElement('select', 'slicesize', get_string('srv_size', 'qtype_cloud'), array(get_string('srv_size_half', 'qtype_cloud'), get_string('srv_size_1', 'qtype_cloud'), get_string('srv_size_2', 'qtype_cloud'), get_string('srv_size_4', 'qtype_cloud'), get_string('srv_size_8', 'qtype_cloud'), get_string('srv_size_15', 'qtype_cloud'), get_string('srv_size_30', 'qtype_cloud')));

        $repeatedoptions['answer']['type'] = PARAM_RAW;
        $repeatedoptions['fraction']['default'] = 0;
        $answersoption = 'answer';

        return $repeated;
    }

    protected function add_per_answer_fields(&$mform, $label, $gradeoptions,
            $minoptions = QUESTION_NUMANS_START, $addoptions = QUESTION_NUMANS_ADD) {
        $answersoption = '';
        $repeatedoptions = array();
        $repeated = $this->get_per_answer_fields($mform, $label, $gradeoptions,
                $repeatedoptions, $answersoption);

        if (isset($this->question->options)) {
            $countanswers = count($this->question->options->$answersoption);
        } else {
            $countanswers = 0;
        }
        if ($this->question->formoptions->repeatelements) {
            $repeatsatstart = max($minoptions, $countanswers + $addoptions);
        } else {
            $repeatsatstart = $countanswers;
        }

        $this->repeat_elements($repeated, $repeatsatstart, $repeatedoptions,
                'noservers', 'addanswers', $addoptions,
                get_string('addmoreserverblanks', 'qtype_cloud'), TRUE);
    }

    public function qtype() {
        return 'cloud';
    }
}
