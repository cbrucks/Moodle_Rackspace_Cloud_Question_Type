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
 * Test helper code for the cloud question type.
 *
 * @package    qtype
 * @subpackage cloud
 * @copyright  2013 Chris Brucks
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Test helper class for the cloud question type.
 *
 * @copyright  2013 Chris Brucks
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_cloud_test_helper extends question_test_helper {
    public function get_test_questions() {
        return array('info');
    }

    /**
     * @return qtype_cloud_question
     */
    public static function make_cloud_question_info() {
        question_bank::load_question_cloud_classes('cloud');
        $q = new qtype_cloud_question();

        test_question_maker::initialise_a_question($q);
        $q->defaultmark = 0;
        $q->penalty = 0;
        $q->length = 0;

        $q->name = 'Cloud';
        $q->questiontext = 'Here is some information about the questions you are about to attempt.';
        $q->generalfeedback = 'And here is some more text shown only on the review page.';
        $q->qtype = question_bank::get_qtype('cloud');

        return $q;
    }

    /**
     * Get the question data, as it would be loaded by get_question_options, for
     * the question returned by {@link make_an_oumultiresponse_two_of_four()}.
     * @return object
     */
    public static function get_question_data() {
        global $USER;

        $qdata = new stdClass();
        $qdata->id = 0;
        $qdata->contextid = 0;
        $qdata->category = 0;
        $qdata->parent = 0;
        $qdata->stamp = make_unique_id_code();
        $qdata->version = make_unique_id_code();
        $qdata->timecreated = time();
        $qdata->timemodified = time();
        $qdata->createdby = $USER->id;
        $qdata->modifiedby = $USER->id;
        $qdata->qtype = 'cloud';
        $qdata->name = 'Cloud';
        $qdata->questiontext = 'Here is some information about the questions you are about to attempt.';
        $qdata->questiontextformat = FORMAT_HTML;
        $qdata->generalfeedback = 'And here is some more text shown only on the review page.';
        $qdata->generalfeedbackformat = FORMAT_HTML;
        $qdata->defaultmark = 0;
        $qdata->length = 0;
        $qdata->penalty = 0;
        $qdata->hidden = 0;

        return $qdata;
    }
}
