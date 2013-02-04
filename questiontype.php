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
 * Question type class for the cloud 'question' type.
 *
 * @package    qtype
 * @subpackage cloud
 * @copyright  2013 Chris Brucks
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');


/**
 * The cloud 'question' type.
 *
 * @copyright  2013 Chris Brucks
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_cloud extends question_type {
    public function is_real_question_type() {
        return false;
    }

    public function is_usable_by_random() {
        return false;
    }

    public function can_analyse_responses() {
        return false;
    }

    public function save_question($question, $form) {
        global $USER, $DB, $OUTPUT;

        list($question->category) = explode(',', $form->category);
        $context = $this->get_context_by_category_id($question->category);

        // This default implementation is suitable for most
        // question types.

        // First, save the basic question itself
        $question->name = trim($form->name);
        $question->parent = isset($form->parent) ? $form->parent : 0;
        $question->length = $this->actual_number_of_questions($question);
        $question->penalty = 0;

        if (empty($form->questiontext['text'])) {
            $question->questiontext = '';
        } else {
            $question->questiontext = trim($form->questiontext['text']);;
        }
        $question->questiontextformat = !empty($form->questiontext['format']) ?
                $form->questiontext['format'] : 0;

        if (empty($question->name)) {
            $question->name = shorten_text(strip_tags($form->questiontext['text']), 15);
            if (empty($question->name)) {
                $question->name = '-';
            }
        }

        if (isset($form->defaultmark)) {
            $question->defaultmark = $form->defaultmark;
        }

        $question->ca_username = $form->ca_username;
        $question->ca_password = $form->ca_password;
        $question->ca_api_key = $form->ca_api_key;

        // If the question is new, create it.
        if (empty($question->id)) {
            // Set the unique code
            $question->stamp = make_unique_id_code();
            $question->createdby = $USER->id;
            $question->timecreated = time();
            $question->id = $DB->insert_record('question', $question);
        }

        // Now, whether we are updating a existing question, or creating a new
        // one, we have to do the files processing and update the record.
        /// Question already exists, update.
        $question->modifiedby = $USER->id;
        $question->timemodified = time();

        if (!empty($question->questiontext) && !empty($form->questiontext['itemid'])) {
            $question->questiontext = file_save_draft_area_files($form->questiontext['itemid'],
                    $context->id, 'question', 'questiontext', (int)$question->id,
                    $this->fileoptions, $question->questiontext);
        }
        if (!empty($question->generalfeedback) && !empty($form->generalfeedback['itemid'])) {
            $question->generalfeedback = file_save_draft_area_files(
                    $form->generalfeedback['itemid'], $context->id,
                    'question', 'generalfeedback', (int)$question->id,
                    $this->fileoptions, $question->generalfeedback);
        }
        $DB->update_record('question', $question);

        // Now to save all the answers and type-specific options
        $form->id = $question->id;
        $form->qtype = $question->qtype;
        $form->category = $question->category;
        $form->questiontext = $question->questiontext;
        $form->questiontextformat = $question->questiontextformat;
        // current context
        $form->context = $context;

        $result = $this->save_question_options($form);

        if (!empty($result->error)) {
            print_error($result->error);
        }

        if (!empty($result->notice)) {
            notice($result->notice, "question.php?id=$question->id");
        }

        if (!empty($result->noticeyesno)) {
            throw new coding_exception(
                    '$result->noticeyesno no longer supported in save_question.');
        }

        // Give the question a unique version stamp determined by question_hash()
        $DB->set_field('question', 'version', question_hash($question),
                array('id' => $question->id));

        return $question;
    }

    public function initialise_question_instance(question_definition $question, $questiondata) {
//        parent::initialise_question_instance($question, $questiondata);

//        $question->ca_username = $questiondata->ca_username;
    }

    public function actual_number_of_questions($question) {
        /// Used for the feature number-of-questions-per-page
        /// to determine the actual number of questions wrapped
        /// by this question.
        /// The question type description is not even a question
        /// in itself so it will return ZERO!
        return 0;
    }

    public function get_random_guess_score($questiondata) {
        return null;
    }
}
