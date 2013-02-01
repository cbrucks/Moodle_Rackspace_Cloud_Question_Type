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
 * This file contains tests that walks a cloud question through its interaction model.
 *
 * @package    qtype
 * @subpackage cloud
 * @copyright  2013 Chris Brucks
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');


class qtype_cloud_walkthrough_test extends qbehaviour_walkthrough_test_base {

    public function test_informationitem_feedback_cloud() {

        // Create a cloud question.
        $cloud = test_question_maker::make_question('cloud');
        $this->start_attempt_at_question($cloud, 'deferredfeedback');

        // Check the initial state.
        $this->assertEquals('informationitem',
                $this->quba->get_question_attempt($this->slot)->get_behaviour_name());

        $this->check_current_output(
                new question_contains_tag_with_contents('h3', get_string('informationtext', 'qtype_cloud'))
        );
    }
}
