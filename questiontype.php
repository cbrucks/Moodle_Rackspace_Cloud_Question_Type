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

    /**
     * Get the extra question field names.
     *
     * Use as a work around for function "initialise_question_instance()".
     * Since "initialise_question_instance()" doesn't read anything from the database
     * but instead just assigns values already loaded to their respective form components
     * we can just list ALL of our extra options irrespective of their tables.
     */
/*    public function extra_question_fields() {
        // Retain the table name at the beginning of the array for padding reasons
        // when using with "initialise_question_instance()".
        $fields = account_fields();

        // Append only the field names to the array.
        $fields = array_merge($fields, array_shift(lb_fields()));

        // Append only the field names to the array.
        $fields = array_merge($fields, array_shift(server_fields()));

        return $fields;
    }
*/
    public function account_fields() {
        return array('question_cloud_account', 'username', 'password', 'auth_token', 'api_key', 'api_auth_token');
    }

    public function lb_fields() {
        return array('question_cloud_lb', 'lb_name', 'vip', 'region');
    }

    public function server_fields() {
        return array('question_cloud_server', 'srv_name', 'imagename', 'slicesize');
    }

    /**
     * Saves question-type specific options
     *
     * This is called by {@link save_question()} to save the question-type specific data
     * @return object $result->error or $result->noticeyesno or $result->notice
     * @param object $question  This holds the information from the editing form,
     *      it is not a standard question object.
     */
    public function save_question_options($question) {
        $this->save_generic_question_options($question, $this->account_fields());
        $this->save_generic_question_options($question, $this->lb_fields());
        $this->save_generic_question_options($question, $this->server_fields());
    }

    private function save_generic_question_options($question, $extraquestionfields) {
        global $DB;
        if (is_array($extraquestionfields)) {
            $question_extension_table = array_shift($extraquestionfields);

            $function = 'update_record';
            $questionidcolname = $this->questionid_column_name();
            $options = $DB->get_record($question_extension_table,
                    array($questionidcolname => $question->id));
            if (!$options) {
                $function = 'insert_record';
                $options = new stdClass();
                $options->$questionidcolname = $question->id;
            }
            foreach ($extraquestionfields as $field) {
                if (property_exists($question, $field)) {
                    $options->$field = $question->$field;
                }
            }

            $DB->{$function}($question_extension_table, $options);
        }
    }

    /**
     * Loads the question type specific options for the question.
     *
     * This function loads any question type specific options for the
     * question from the database into the question object. This information
     * is placed in the $question->options field. A question type is
     * free, however, to decide on a internal structure of the options field.
     * @return bool            Indicates success or failure.
     * @param object $question The question object for the question. This object
     *                         should be updated to include the question type
     *                         specific information (it is passed by reference).
     */
    public function get_question_options($question) {
        if (!isset($question->options)) {
            $question->options = new stdClass();
        }

        $results = $this->get_generic_question_options($question, $this->account_fields());

        return $results;
    }

    private function get_generic_question_options($question, $extraquestionfields = NULL) {
        global $CFG, $DB, $OUTPUT;

        if (is_array($extraquestionfields)) {
            $question_extension_table = array_shift($extraquestionfields);
            $extra_data = $DB->get_record($question_extension_table,
                    array($this->questionid_column_name() => $question->id),
                    implode(', ', $extraquestionfields));
            if ($extra_data) {
                foreach ($extraquestionfields as $field) {
                    $question->options->$field = $extra_data->$field;
                }
            } else {
                echo $OUTPUT->notification('Failed to load question options from the table ' .
                        $question_extension_table . ' for questionid ' . $question->id);
                return false;
            }
        }
        return true;
    }

    /**
     * Deletes the question-type specific data when a question is deleted.
     * @param int $question the question being deleted.
     * @param int $contextid the context this quesiotn belongs to.
     */
    public function delete_question($questionid, $contextid) {
        global $DB;

        $this->delete_files($questionid, $contextid);

        $account_fields = $this->account_fields();
//        delete_generic_question_options($questionid, $account_fields);

        $DB->delete_records('question_answers', array('question' => $questionid));

        $DB->delete_records('question_hints', array('questionid' => $questionid));
    }

/*    public function delete_generic_question_options($questionid, $extraquestionfields = NULL) {
        global $DB;

        if (is_array($extraquestionfields)) {
            $question_extension_table = array_shift($extraquestionfields);
            $DB->delete_records($question_extension_table,
                    array($this->questionid_column_name() => $questionid));
        }
    }
*/
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
