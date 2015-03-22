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

require_once($CFG->dirroot.'/question/type/ddimageortext/edit_ddtoimage_form_base.php');
require_once($CFG->dirroot.'/question/type/clickhotspot/shapes.php');

define('QTYPE_CLICKHOTSPOT_ALLOWED_TAGS_IN_MARKER', '<br><i><em><b><strong><sup><sub><u>');


/**
 * Drag-and-drop images onto images  editing form definition.
 *
 * @copyright 2009 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_clickhotspot_edit_form extends qtype_ddtoimage_edit_form_base {

    public function qtype() {
        return 'clickhotspot';
    }

    protected function definition_inner($mform) {
        $mform->addElement('advcheckbox', 'showmisplaced', ' ',
                                                get_string('showmisplaced', 'qtype_clickhotspot'));
        parent::definition_inner($mform);

        $mform->addHelpButton('drops[0]', 'dropzones', 'qtype_clickhotspot');
    }

    public function js_call() {
        global $PAGE;
        $maxsizes =new stdClass();
        $maxsizes->bgimage = new stdClass();
        $maxsizes->bgimage->width = QTYPE_CLICKHOTSPOT_BGIMAGE_MAXWIDTH;
        $maxsizes->bgimage->height = QTYPE_CLICKHOTSPOT_BGIMAGE_MAXHEIGHT;

        $params = array('maxsizes' => $maxsizes,
                        'topnode' => 'fieldset#id_previewareaheader');

        $PAGE->requires->yui_module('moodle-qtype_clickhotspot-form',
                                        'M.qtype_clickhotspot.init_form',
                                        array($params));
    }

    protected function definition_draggable_items($mform, $itemrepeatsatstart) {
        $mform->addElement('header', 'draggableitemheader',
                                get_string('markers', 'qtype_clickhotspot'));
        //$mform->addElement('advcheckbox', 'shuffleanswers', ' ',
        //                                get_string('shuffleimages', 'qtype_'.$this->qtype()));
        $mform->setDefault('shuffleanswers', 0);
        $this->repeat_elements($this->draggable_item($mform), 1,
                $this->draggable_items_repeated_options(),
                'noitems', 'additems', 0,
                '- x -', true);
    }

    protected function draggable_item($mform) {
        $draggableimageitem = array();

        $grouparray= array();
        $grouparray[] = $mform->createElement('text', 'label',
                                                '',
                                                array('size'=>30, 'class'=>'tweakcss' ,'readonly'=>'readonly' ,'value'=>'ʘ'));
        $mform->setType('text', PARAM_RAW_TRIMMED);

        $noofdragoptions = array(0 => get_string('infinite', 'qtype_clickhotspot'));
        foreach (range(1, 1) as $option) {
            $noofdragoptions[$option] = $option;
        }
        $grouparray[] = $mform->createElement('select', 'noofdrags', get_string('noofdrags', 'qtype_clickhotspot'), $noofdragoptions);

        $draggableimageitem[] = $mform->createElement('group', 'drags',
                                            get_string('marker_n', 'qtype_clickhotspot'), $grouparray);
        return $draggableimageitem;
    }

    protected function draggable_items_repeated_options() {
        $repeatedoptions = array();
        $repeatedoptions['drags[label]']['type'] = PARAM_RAW;
        return $repeatedoptions;
    }

    protected function drop_zone($mform, $imagerepeats) {
        $dropzoneitem = array();

        $grouparray = array();
        $shapearray = qtype_clickhotspot_shape::shape_options();
        $grouparray[] = $mform->createElement('select', 'shape',
                                    get_string('shape', 'qtype_clickhotspot'), $shapearray);
        $grouparray[] = $mform->createElement('text', 'coords',
                                                get_string('coords', 'qtype_clickhotspot'),
                                                array('size'=>30, 'class'=>'tweakcss'));
        $mform->setType('coords', PARAM_RAW); // These are validated manually.
        $markernos = array();
        $markernos[0] = '';
        for ($i = 1; $i <= $imagerepeats; $i += 1) {
            $markernos[$i] = $i;
        }
        $grouparray[] = $mform->createElement('select', 'choice',
                                    get_string('marker', 'qtype_clickhotspot'), $markernos);
        $mform->setDefault('choice',1);
        $dropzone = $mform->createElement('group', 'drops',
                get_string('dropzone', 'qtype_clickhotspot', '{no}'), $grouparray);
        return array($dropzone);
    }

    protected function drop_zones_repeated_options() {
        $repeatedoptions = array();
        $repeatedoptions['drops[coords]']['type'] = PARAM_RAW;
        return $repeatedoptions;
    }

    protected function get_hint_fields($withclearwrong = false, $withshownumpartscorrect = false) {
        $mform = $this->_form;

        $repeated = array();
        $repeated[] = $mform->createElement('editor', 'hint', get_string('hintn', 'question'),
                array('rows' => 1), $this->editoroptions);
        $repeatedoptions['hint']['type'] = PARAM_RAW;

        $repeated[] = $mform->createElement('checkbox', 'hintshownumcorrect',
                        get_string('options', 'question'),
                        get_string('shownumpartscorrect', 'question'));
        $repeated[] = $mform->createElement('checkbox', 'hintoptions',
                        '',
                        get_string('stateincorrectlyplaced', 'qtype_clickhotspot'));
        $repeated[] = $mform->createElement('checkbox', 'hintclearwrong',
                        '',
                        get_string('clearwrongparts', 'qtype_clickhotspot'));

        return array($repeated, $repeatedoptions);
    }

    public function data_preprocessing($question) {

        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_combined_feedback($question, true);
        $question = $this->data_preprocessing_hints($question, true, true);

        $dragids = array(); // Drag no -> dragid.
        if (!empty($question->options)) {
            $question->shuffleanswers = $question->options->shuffleanswers;
            $question->showmisplaced = $question->options->showmisplaced;
            $question->drags = array();
            foreach ($question->options->drags as $drag) {
                $dragindex = $drag->no -1;
                $question->drags[$dragindex] = array();
                $question->drags[$dragindex]['label'] = $drag->label;
                if ($drag->infinite == 1) {
                    $question->drags[$dragindex]['noofdrags'] = 0;
                } else {
                    $question->drags[$dragindex]['noofdrags'] = $drag->noofdrags;
                }
                $dragids[$dragindex] = $drag->id;
            }
            $question->drops = array();
            foreach ($question->options->drops as $drop) {
                $droparray = (array)$drop;
                unset($droparray['id']);
                unset($droparray['no']);
                unset($droparray['questionid']);
                $question->drops[$drop->no -1] = $droparray;
            }
        }
        // Initialise file picker for bgimage.
        $draftitemid = file_get_submitted_draft_itemid('bgimage');

        file_prepare_draft_area($draftitemid, $this->context->id, 'qtype_clickhotspot',
                                'bgimage', !empty($question->id) ? (int) $question->id : null,
                                self::file_picker_options());
        $question->bgimage = $draftitemid;

        $this->js_call();

        return $question;
    }
    /**
     * Perform the necessary preprocessing for the hint fields.
     * @param object $question the data being passed to the form.
     * @return object $question the modified data.
     */
    protected function data_preprocessing_hints($question, $withclearwrong = false,
                                                $withshownumpartscorrect = false) {
        if (empty($question->hints)) {
            return $question;
        }
        parent::data_preprocessing_hints($question, $withclearwrong, $withshownumpartscorrect);

        $question->hintoptions = array();
        foreach ($question->hints as $hint) {
            $question->hintoptions[] = $hint->options;
        }

        return $question;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $bgimagesize = $this->get_image_size_in_draft_area($data['bgimage']);
        if ($bgimagesize === null) {
            $errors["bgimage"] = get_string('formerror_nobgimage', 'qtype_clickhotspot');
        }

        $allchoices = array();
        for ($i=0; $i < $data['nodropzone']; $i++) {
            $choice = $data['drops'][$i]['choice'];
            $choicepresent = ($choice !== '0');

            if ($choicepresent) {
                // Test coords here.
                if ($bgimagesize !== null) {
                    $shape = $data['drops'][$i]['shape'];
                    $coordsstring = $data['drops'][$i]['coords'];
                    $shapeobj = qtype_clickhotspot_shape::create($shape, $coordsstring);
                    $interpretererror = $shapeobj->get_coords_interpreter_error();
                    if ($interpretererror !== false) {
                        $errors["drops[{$i}]"] = $interpretererror;
                    } else if (!$shapeobj->inside_width_height($bgimagesize)) {
                        $errorcode = 'shapeoutsideboundsofbgimage';
                        $errors["drops[{$i}]"] =
                                            get_string('formerror_'.$errorcode, 'qtype_clickhotspot');
                    }
                }
            } else {
                if (trim($data['drops'][$i]['coords']) !== '') {
                    $errorcode = 'noitemselected';
                    $errors["drops[{$i}]"] = get_string('formerror_'.$errorcode, 'qtype_clickhotspot');
                }
            }

        }
        for ($dragindex=0; $dragindex < $data['noitems']; $dragindex++) {
            $label = $data['drags'][$dragindex]['label'];
            if ($label != strip_tags($label, QTYPE_CLICKHOTSPOT_ALLOWED_TAGS_IN_MARKER)) {
                $errors["drags[{$dragindex}]"]
                    = get_string('formerror_onlysometagsallowed', 'qtype_clickhotspot',
                                  s(QTYPE_CLICKHOTSPOT_ALLOWED_TAGS_IN_MARKER));
            }
        }
        return $errors;
    }

    public function get_image_size_in_draft_area($draftitemid) {
        global $USER;
        $usercontext = context_user::instance($USER->id);
        $fs = get_file_storage();
        $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id');
        if ($draftfiles) {
            foreach ($draftfiles as $file) {
                if ($file->is_directory()) {
                    continue;
                }
                // Just return the data for the first good file, there should only be one.
                $imageinfo = $file->get_imageinfo();
                $width    = $imageinfo['width'];
                $height   = $imageinfo['height'];
                return array($width, $height);
            }
        }
        return null;
    }
}
