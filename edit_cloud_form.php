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
        $mform->addElement('text', 'ca_username', get_string('ca_username', 'qtype_cloud'));
        $mform->addElement('passwordunmask', 'ca_password', get_string('ca_password', 'qtype_cloud'));
        $mform->addElement('passwordunmask', 'ca_api_key', get_string('ca_api_key', 'qtype_cloud'));
        $mform->addElement('text', 'ca_image', get_string('ca_image', 'qtype_cloud'));

    }

    public function qtype() {
        return 'cloud';
    }
}
