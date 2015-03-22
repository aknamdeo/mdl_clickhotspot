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
 * @package   qtype_clickhotspot
 * @copyright 2015 EddyTools.com
 * @author    aknamdeo <aknamdeo@eddytools.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * @var string label to use for drag items when converting image target questions to clickhotspot question type
 */
define('QTYPE_CLICKHOTSPOT_LABEL_FOR_MARKER_FOR_IMAGE_TARGET_QS', 'X');

/**
 * Checks file access for essay questions.
 */
function qtype_clickhotspot_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG;
    require_once($CFG->libdir . '/questionlib.php');
    question_pluginfile($course, $context, 'qtype_clickhotspot', $filearea, $args, $forcedownload, $options);
}

function qtype_clickhotspot_course_context_id($catcontextid) {
    $context = context::instance_by_id($catcontextid);
    while ($context->contextlevel != CONTEXT_COURSE) {
        $context = $context->get_parent_context();
    }
    return $context->id;
}

function qtype_clickhotspot_convert_image_target_question($question, $imgfilename, $answers) {
    global $DB, $OUTPUT;
    $correctfeedback = '';
    $correctfeedbackformat = 1;
    $incorrectfeedback = '';
    $incorrectfeedbackformat = 1;
    $foundincorrectanswer = false;
    foreach ($answers as $answer) {
        $no = 1;
        if ('*' !== $answer->answer) {
            $drop = new stdClass();
            $drop->questionid = $question->id;
            $drop->shape = 'rectangle';
            $drop->no = $no;
            list($x1, $y1, $x2, $y2) = explode(',', $answer->answer);
            $width = $x2 - $x1;
            $height = $y2 - $y1;
            $drop->coords = "{$x1},{$y1};{$width},{$height}";
            $drop->choice = 1;
            $DB->insert_record('qtype_clickhotspot_drops', $drop);
            $no++;
            $correctfeedback = $answer->feedback;
            $correctfeedbackformat = $answer->feedbackformat;
        } else {
            $foundincorrectanswer = true;
            $incorrectfeedback = $answer->feedback;
            $incorrectfeedbackformat = $answer->feedbackformat;
        }
    }
    if (count($answers) < 2) {
        echo $OUTPUT->notification('There are less than 2 answers. '.
                                    '(Normally we expect at least a correct and incorrect answer). '.
                                    'For question id '.$question->id.' "'.$question->name.'".',
                                    'notifyproblem');
    }
    if (!$foundincorrectanswer) {
        echo $OUTPUT->notification('No incorrect answer found for question id '.$question->id.' "'.$question->name.'".',
                                    'notifyproblem');
    }
    $drag = new stdClass();
    $drag->questionid = $question->id;
    $drag->no = 1;
    $drag->label = QTYPE_CLICKHOTSPOT_LABEL_FOR_MARKER_FOR_IMAGE_TARGET_QS;
    $drag->infinite = 0;
    $DB->insert_record('qtype_clickhotspot_drags', $drag);

    $clickhotspot = new stdClass();
    $clickhotspot->questionid = $question->id;
    $clickhotspot->shuffleanswers = 0;
    $clickhotspot->correctfeedback = $correctfeedback;
    $clickhotspot->correctfeedbackformat = $correctfeedbackformat;
    $clickhotspot->partiallycorrectfeedback = '';
    $clickhotspot->partiallycorrectfeedbackformat = 1;
    $clickhotspot->incorrectfeedback = $incorrectfeedback;
    $clickhotspot->incorrectfeedbackformat = $incorrectfeedbackformat;
    $clickhotspot->shownumcorrect = 0;
    $clickhotspot->showmisplaced = 0;
    $DB->insert_record('qtype_clickhotspot', $clickhotspot);

    $newrec = clone($question);
    unset($newrec->contextid);
    $newrec->qtype = 'clickhotspot';
    $newrec->timemodified = time();
    $DB->update_record('question', $newrec);

    $fs = get_file_storage();
    // We need to look in the course legacy files area for file.
    $bgimagefile = $fs->get_file(qtype_clickhotspot_course_context_id($question->contextid),
                                    'course',
                                    'legacy',
                                    '0',
                                    '/'.dirname($imgfilename).'/',
                                    basename($imgfilename));
    if ($bgimagefile === false) {
        echo $OUTPUT->notification('File "'.$imgfilename.'" not found in legacy course files area. '.
                                    'For question id '.$question->id.' "'.$question->name.'".',
                                    'notifyproblem');
    } else {
        $newbgimagefile = new stdClass();
        $newbgimagefile->component = 'qtype_clickhotspot';
        $newbgimagefile->filearea = 'bgimage';
        $newbgimagefile->filepath = '/';
        $newbgimagefile->itemid = $question->id;
        $newbgimagefile->contextid = $question->contextid;
        $fs->create_file_from_storedfile($newbgimagefile, $bgimagefile);
    }
}
