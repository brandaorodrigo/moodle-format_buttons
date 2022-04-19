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
 * format_buttons_renderer
 *
 * @package    format_buttons
 * @author     Rodrigo Brandão <https://www.linkedin.com/in/brandaorodrigo>
 * @copyright  2020 Rodrigo Brandão <rodrigo.brandao.contato@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/format/topics/renderer.php');

/**
 * Format_buttons_renderer
 *
 * @package    format_buttons
 * @author     Rodrigo Brandão <https://www.linkedin.com/in/brandaorodrigo>
 * @copyright  2020 Rodrigo Brandão <rodrigo.brandao.contato@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_buttons_renderer extends format_topics\output\renderer
{

    /**
     * Get_button_section
     *
     * @param stdclass $course
     * @param string $name
     * @return string
     */
    protected function get_color_config($course, $name) {
        $return = false;
        if (isset($course->{$name})) {
            $color = str_replace('#', '', $course->{$name});
            $color = substr($color, 0, 6);
            if (preg_match('/^#?[a-f0-9]{6}$/i', $color)) {
                $return = '#'.$color;
            }
        }
        return $return;
    }

    /**
     * Number_to_roman
     *
     * @param integer $number
     * @return string
     */
    protected function number_to_roman($number) {
        $number = intval($number);
        $return = '';
        $romanarray = [
            'M' => 1000,
            'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C' => 100,
            'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X' => 10,
            'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1
        ];
        foreach ($romanarray as $roman => $value) {
            $matches = intval($number / $value);
            $return .= str_repeat($roman, $matches);
            $number = $number % $value;
        }
        return $return;
    }

    /**
     * Number_to_alphabet
     *
     * @param integer $number
     * @return string
     */
    protected function number_to_alphabet($number) {
        $number = $number - 1;
        $alphabet = range("A", "Z");
        if ($number <= 25) {
            return $alphabet[$number];
        } else if ($number > 25) {
            $dividend = ($number + 1);
            $alpha = '';
            while ($dividend > 0) {
                $modulo = ($dividend - 1) % 26;
                $alpha = $alphabet[$modulo] . $alpha;
                $dividend = floor((($dividend - $modulo) / 26));
            }
            return $alpha;
        }
    }

    /**
     * Get_button_section
     *
     * @param stdclass $course
     * @param string $sectionvisible
     * @return string
     */
    protected function get_button_section($course, $sectionvisible) {
        global $PAGE;
        $html = '';
        $css = '';
        if ($colorcurrent = $this->get_color_config($course, 'colorcurrent')) {
            $css .=
            '#buttonsectioncontainer .buttonsection.current {
                background: ' . $colorcurrent . ';
            }
            ';
        }
        if ($colorvisible = $this->get_color_config($course, 'colorvisible')) {
            $css .=
            '#buttonsectioncontainer .buttonsection.sectionvisible {
                background: ' . $colorvisible . ';
            }
            ';
        }
        if ($css) {
            $html .= html_writer::tag('style', $css);
        }
        $withoutdivisor = true;
        for ($k = 1; $k <= 12; $k++) {
            if ($course->{'divisor' . $k}) {
                $withoutdivisor = false;
            }
        }
        if ($withoutdivisor) {
            $course->divisor1 = 999;
        }
        $divisorshow = false;
        $count = 1;
        $currentdivisor = 1;
        $modinfo = get_fast_modinfo($course);
        $inline = '';
        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            if ($section == 0) {
                continue;
            }
            if ($section > $course->numsections) {
                continue;
            }
            if ($course->hiddensections && !(int)$thissection->visible) {
                continue;
            }
            if (isset($course->{'divisor' . $currentdivisor}) &&
                $count > $course->{'divisor' . $currentdivisor}) {
                $currentdivisor++;
                $count = 1;
            }
            if (isset($course->{'divisor' . $currentdivisor}) &&
                $course->{'divisor' . $currentdivisor} != 0 &&
                !isset($divisorshow[$currentdivisor])) {
                $currentdivisorhtml = format_string($course->{'divisortext' . $currentdivisor});
                $currentdivisorhtml = str_replace('[br]', '<br>', $currentdivisorhtml);
                $currentdivisorhtml = html_writer::tag('div', $currentdivisorhtml, ['class' => 'divisortext']);
                if ($course->inlinesections) {
                    $inline = 'inlinebuttonsections';
                }
                $html .= html_writer::tag('div', $currentdivisorhtml, ['class' => "divisorsection $inline"]);
                $divisorshow[$currentdivisor] = true;
            }
            $id = 'buttonsection-' . $section;
            if ($course->sequential) {
                $name = $section;
            } else {
                if (isset($course->{'divisor' . $currentdivisor}) &&
                $course->{'divisor' . $currentdivisor} == 1) {
                    $name = '&bull;&bull;&bull;';
                } else {
                    $name = $count;
                }
            }
            if ($course->sectiontype == 'alphabet' && is_numeric($name)) {
                $name = $this->number_to_alphabet($name);
            }
            if ($course->sectiontype == 'roman' && is_numeric($name)) {
                $name = $this->number_to_roman($name);
            }
            $class = 'buttonsection';
            $onclick = 'M.format_buttons.show(' . $section . ',' . $course->id . ')';
            if (!$thissection->available &&
                !empty($thissection->availableinfo)) {
                $class .= ' sectionhidden';
            } else if (!$thissection->uservisible || !$thissection->visible) {
                $class .= ' sectionhidden';
                $onclick = false;
            }
            if ($course->marker == $section) {
                $class .= ' current';
            }
            if ($sectionvisible == $section) {
                $class .= ' sectionvisible';
            }
            if ($PAGE->user_is_editing()) {
                $onclick = false;
            }
            $html .= html_writer::tag('div', $name, ['id' => $id, 'class' => $class, 'onclick' => $onclick]);
            $count++;
        }
        $html = html_writer::tag('div', $html, ['id' => 'buttonsectioncontainer', 'class' => $course->buttonstyle]);
        if ($PAGE->user_is_editing()) {
            $html .= html_writer::tag('div', get_string('editing', 'format_buttons'), ['class' => 'alert alert-warning alert-block fade in']);
        }
        return $html;
    }

    /**
     * Start_section_list
     *
     * @return string
     */
    protected function start_section_list() {
        return html_writer::start_tag('ul', ['class' => 'buttons']);
    }

    /**
     * Section_header
     *
     * @param stdclass $section
     * @param stdclass $course
     * @param bool $onsectionpage
     * @param int $sectionreturn
     * @return string
     */
    protected function section_header($section, $course, $onsectionpage, $sectionreturn = null) {
        global $PAGE;

        $o = '';
        $currenttext = '';
        $sectionstyle = '';

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            }
            if (course_get_format($course)->is_section_current($section)) {
                $sectionstyle = ' current';
            }
        }

        $o.= html_writer::start_tag('li', array('id' => 'section-'.$section->section,
             'class' => 'section main clearfix'.$sectionstyle, 'role'=>'region',
             'aria-label'=> get_section_name($course, $section)));

        // Create a span that contains the section title to be used to create the keyboard section move menu.
        $o .= html_writer::tag('span', get_section_name($course, $section), array('class' => 'hidden sectionname'));

        //$leftcontent = $this->section_left_content($section, $course, $onsectionpage);
        $leftcontent = '';

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (course_get_format($course)->is_section_current($section)) {
                $leftcontent = get_accesshide(get_string('currentsection', 'format_' . $course->format));
            }
        }
        $o.= html_writer::tag('div', $leftcontent, array('class' => 'left side'));

        //$rightcontent = $this->section_right_content($section, $course, $onsectionpage);
        $format = course_get_format($course);
        $modinfo = $format->get_modinfo();

        if ($onsectionpage) {
            $format->set_section_number($section->section);
        }

        // We need a section_info object, not a record.
        $section = $modinfo->get_section_info($section->section);

        $widgetclass = $format->get_output_classname('content\\section\\controlmenu');
        $widget = new $widgetclass($format, $section);
        $controls = $widget->section_control_items();
        $rightcontent = "";
        if (!empty($controls)) {
            $menu = new action_menu();
            $menu->set_menu_trigger(get_string('edit'));
            $menu->attributes['class'] .= ' section-actions';
            foreach ($controls as $value) {
                $url = empty($value['url']) ? '' : $value['url'];
                $icon = empty($value['icon']) ? '' : $value['icon'];
                $name = empty($value['name']) ? '' : $value['name'];
                $attr = empty($value['attr']) ? array() : $value['attr'];
                $class = empty($value['pixattr']['class']) ? '' : $value['pixattr']['class'];
                $al = new action_menu_link_secondary(
                    new moodle_url($url),
                    new pix_icon($icon, '', null, array('class' => "smallicon " . $class)),
                    $name,
                    $attr
                );
                $menu->add($al);
            }

            $rightcontent .= html_writer::div(
                $this->render($menu),
                'section_action_menu',
                array('data-sectionid' => $section->id)
            );
        }

        $o.= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
        $o.= html_writer::start_tag('div', array('class' => 'content'));

        // When not on a section page, we display the section titles except the general section if null
        $hasnamenotsecpg = (!$onsectionpage && ($section->section != 0 || !is_null($section->name)));

        // When on a section page, we only display the general section title, if title is not the default one
        $hasnamesecpg = ($onsectionpage && ($section->section == 0 && !is_null($section->name)));

        $classes = ' accesshide';
        if ($hasnamenotsecpg || $hasnamesecpg) {
            $classes = '';
        }
        $sectionname = html_writer::tag('span', $this->section_title($section, $course));

        // Button format - ini
        if ($course->showdefaultsectionname) {
            $o.= $this->output->heading($sectionname, 3, 'sectionname' . $classes);
        }
        // Button format - end

        //$o .= $this->section_availability($section);
        $context = context_course::instance($section->course);
        $canviewhidden = has_capability('moodle/course:viewhiddensections', $context);
        
        $course = $section->course;
        $format = course_get_format($course);
        $widgetclass = $format->get_output_classname('content\\section\\availability');
        $widget = new $widgetclass($format, $section);
        $availability_message = $this->render($widget);

        $o .= html_writer::div($availability_message, 'section_availability');




        $o .= html_writer::start_tag('div', array('class' => 'summary'));
        if ($section->uservisible || $section->visible) {
            // Show summary if section is available or has availability restriction information.
            // Do not show summary if section is hidden but we still display it because of course setting
            // "Hidden sections are shown in collapsed form".
            //$o .= $this->format_summary_text($section);
            $format = course_get_format($section->course);
            if (!($section instanceof section_info)) {
                $modinfo = $format->get_modinfo();
                $section = $modinfo->get_section_info($section->section);
            }
            $summaryclass = $format->get_output_classname('content\\section\\summary');
            $summary = new $summaryclass($format, $section);
            $o .= $summary->format_summary_text();
        }
        $o .= html_writer::end_tag('div');

        return $o;
    }

    /**
     * Renders HTML to display a list of course modules in a course section
     * Also displays "move here" controls in Javascript-disabled mode.
     *
     * This function calls {@link core_course_renderer::course_section_cm()}
     *
     * @param stdClass $course course object
     * @param int|stdClass|section_info $section relative section number or section object
     * @param int $sectionreturn section number to return to
     * @param int $displayoptions
     * @return void
     */
    public function section_cm_list($course, $section, $sectionreturn = null, $displayoptions = []) {
        global $USER;

        $output = '';

        $format = course_get_format($course);
        $modinfo = $format->get_modinfo();

        if (is_object($section)) {
            $section = $modinfo->get_section_info($section->section);
        } else {
            $section = $modinfo->get_section_info($section);
        }
        $completioninfo = new completion_info($course);

        // Check if we are currently in the process of moving a module with JavaScript disabled
        $ismoving = $format->show_editor() && ismoving($course->id);

        if ($ismoving) {
            $strmovefull = strip_tags(get_string("movefull", "", "'$USER->activitycopyname'"));
        }

        // Get the list of modules visible to user (excluding the module being moved if there is one)
        $moduleshtml = [];
        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $modnumber) {
                $mod = $modinfo->cms[$modnumber];

                if ($ismoving and $mod->id == $USER->activitycopy) {
                    // do not display moving mod
                    continue;
                }

                if ($modulehtml = $this->section_cm_list_item($course,
                        $completioninfo, $mod, $sectionreturn, $displayoptions)) {
                    $moduleshtml[$modnumber] = $modulehtml;
                }
            }
        }

        $sectionoutput = '';
        if (!empty($moduleshtml) || $ismoving) {
            foreach ($moduleshtml as $modnumber => $modulehtml) {
                if ($ismoving) {
                    $movingurl = new moodle_url('/course/mod.php', array('moveto' => $modnumber, 'sesskey' => sesskey()));
                    $sectionoutput .= html_writer::tag('li',
                        html_writer::link($movingurl, '', array('title' => $strmovefull, 'class' => 'movehere')),
                        array('class' => 'movehere'));
                }

                $sectionoutput .= $modulehtml;
            }

            if ($ismoving) {
                $movingurl = new moodle_url('/course/mod.php', array('movetosection' => $section->id, 'sesskey' => sesskey()));
                $sectionoutput .= html_writer::tag('li',
                    html_writer::link($movingurl, '', array('title' => $strmovefull, 'class' => 'movehere')),
                    array('class' => 'movehere'));
            }
        }

        // Always output the section module list.
        $output .= html_writer::tag('ul', $sectionoutput, array('class' => 'section img-text'));

        return $output;
    }

    /**
     * Renders HTML to display one course module for display within a section.
     *
     * This function calls:
     * {@link core_course_renderer::course_section_cm()}
     *
     * @param stdClass $course
     * @param completion_info $completioninfo
     * @param cm_info $mod
     * @param int|null $sectionreturn
     * @param array $displayoptions
     * @return String
     */
    public function section_cm_list_item($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = []) {

        $output = '';
        if ($modulehtml = $this->course_section_cm($course, $completioninfo, $mod, $sectionreturn, $displayoptions)) {
            $modclasses = 'activity ' . $mod->modname . ' modtype_' . $mod->modname . ' ' . $mod->extraclasses;
            $output .= html_writer::tag('li', $modulehtml, array('class' => $modclasses, 'id' => 'module-' . $mod->id));
        }
        return $output;
    }

    /**
     * Renders HTML to display one course module in a course section
     *
     * This includes link, content, availability, completion info and additional information
     * that module type wants to display (i.e. number of unread forum posts)
     *
     * @param stdClass $course
     * @param completion_info $completioninfo
     * @param cm_info $mod
     * @param int|null $sectionreturn
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = []) {

        if (!$mod->is_visible_on_course_page()) {
            return '';
        }

        $format = course_get_format($course);
        $modinfo = $format->get_modinfo();
        // Output renderers works only with real section_info objects.
        if ($sectionreturn) {
            $format->set_section_number($sectionreturn);
        }
        $section = $modinfo->get_section_info($format->get_section_number());

        $cmclass = $format->get_output_classname('content\\cm');
        $cm = new $cmclass($format, $section, $mod, $displayoptions);
        // The course outputs works with format renderers, not with course renderers.
        $renderer = $format->get_renderer($this->page);
        $data = $cm->export_for_template($renderer);
        return $this->output->render_from_template('core_courseformat/local/content/cm', $data);
    }

    /**
     * Print_multiple_section_page
     *
     * @param stdclass $course
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $PAGE;

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        $context = context_course::instance($course->id);
        // Title with completion help icon.
        $completioninfo = new completion_info($course);

        // Buttons format - ini
        if (isset($_COOKIE['sectionvisible_' . $course->id])) {
            $sectionvisible = $_COOKIE['sectionvisible_' . $course->id];
        } else if ($course->marker > 0) {
            $sectionvisible = $course->marker;
        } else {
            $sectionvisible = 1;
        }
        $htmlsection = false;
        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            $htmlsection[$section] = '';
            if ($section == 0) {
                $section0 = $thissection;
                continue;
            }
            if ($section > $course->numsections) {
                continue;
            }
            /* If is not editing verify the rules to display the sections */
            if (!$PAGE->user_is_editing()) {
                if ($course->hiddensections && !(int)$thissection->visible) {
                    continue;
                }
                if (!$thissection->available && !empty($thissection->availableinfo)) {
                    $htmlsection[$section] .= $this->section_header($thissection, $course, false, 0);
                    continue;
                }
                if (!$thissection->uservisible || !$thissection->visible) {
                    $htmlsection[$section] .= $this->section_hidden($section, $course->id);
                    continue;
                }
            }
            $htmlsection[$section] .= $this->section_header($thissection, $course, false, 0);
            if ($thissection->uservisible) {
                $htmlsection[$section] .= $this->section_cm_list($course, $thissection, 0);
                $htmlsection[$section] .= $this->courserenderer->course_section_add_cm_control($course, $section, 0);
            }
            $htmlsection[$section] .= $this->section_footer();
        }
        if ($section0->summary || !empty($modinfo->sections[0]) || $PAGE->user_is_editing()) {
            $htmlsection0 = $this->section_header($section0, $course, false, 0);
            $htmlsection0 .= $this->section_cm_list($course, $section0, 0);
            $htmlsection0 .= $this->courserenderer->course_section_add_cm_control($course, 0, 0);
            $htmlsection0 .= $this->section_footer();
        }

        echo $this->output->heading($this->page_title(), 2, 'accesshide');
        //$this->course_activity_clipboard($course, 0);
        
        // If currently moving a file then show the current clipboard.
        if (ismoving($course->id)) {
            $o = '';
            $url = new moodle_url(
                '/course/mod.php',
                array(
                    'sesskey' => sesskey(),
                    'cancelcopy' => true,
                    'sr' => 0,
                )
            );

            $o .= html_writer::start_tag('div', array('class' => 'clipboard'));
            $o .= strip_tags(get_string('activityclipboard', '', $USER->activitycopyname));
            $o .= ' (' . html_writer::link($url, get_string('cancel')) . ')';
            echo html_writer::end_tag('div');
        }

        echo $this->start_section_list();
        if ($course->sectionposition == 0 and isset($htmlsection0)) {
            echo html_writer::tag('span', $htmlsection0, ['class' => 'above']);
        }
        echo $this->get_button_section($course, $sectionvisible);
        foreach ($htmlsection as $current) {
            echo $current;
        }
        if ($course->sectionposition == 1 and isset($htmlsection0)) {
            echo html_writer::tag('span', $htmlsection0, ['class' => 'below']);
        }
        if ($PAGE->user_is_editing() and has_capability('moodle/course:update', $context)) {
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $course->numsections or empty($modinfo->sections[$section])) {
                    continue;
                }
                echo $this->stealth_section_header($section);
                echo $this->section_cm_list($course, $thissection, 0);
                echo html_writer::end_tag('div');
                echo html_writer::end_tag('li');
            }
            echo html_writer::end_tag('ul');
            echo html_writer::start_tag('div', ['id' => 'changenumsections', 'class' => 'mdl-right']);
            $straddsection = get_string('increasesections', 'moodle');
            $url = new moodle_url('/course/changenumsections.php', ['courseid' => $course->id,
                'increase' => true, 'sesskey' => sesskey()]);
            $icon = $this->output->pix_icon('t/switch_plus', $straddsection);
            echo html_writer::link($url, $icon.get_accesshide($straddsection), ['class' => 'increase-sections']);
            if ($course->numsections > 0) {
                $strremovesection = get_string('reducesections', 'moodle');
                $url = new moodle_url('/course/changenumsections.php', ['courseid' => $course->id,
                    'increase' => false, 'sesskey' => sesskey()]);
                $icon = $this->output->pix_icon('t/switch_minus', $strremovesection);
                echo html_writer::link(
                    $url,
                    $icon.get_accesshide($strremovesection),
                    ['class' => 'reduce-sections']
                );
            }
            echo html_writer::end_tag('div');
        } else {
            echo html_writer::end_tag('ul');
        }
        if (!$PAGE->user_is_editing()) {
            $PAGE->requires->js_init_call('M.format_buttons.init', [$course->numsections, $sectionvisible, $course->id]);
        }
        // Button format - end
    }

    /**
     * Generate the display of the footer part of a section.
     *
     * This element is integrated into core_courseformat\output\local\content\section output component and it is
     * displayed using mustache templates instead of a renderer method.
     *
     * @return string HTML to output.
     */
    protected function section_footer() {

        $o = html_writer::end_tag('div');
        $o .= html_writer::end_tag('li');
        return $o;
    }

    /**
     * Generate the header html of a stealth section
     *
     * This element is now a core_courseformat\output\content\section output component and it is displayed using
     * mustache templates instead of a renderer method.
     *
     * @param int $sectionno The section number in the course which is being displayed
     * @return string HTML to output.
     */
    protected function stealth_section_header($sectionno) {

        $o = '';
        $o .= html_writer::start_tag('li', [
            'id' => 'section-' . $sectionno,
            'class' => 'section main clearfix orphaned hidden',
            'data-sectionid' => $sectionno
        ]);
        $o .= html_writer::tag('div', '', array('class' => 'left side'));
        $course = course_get_format($this->page->course)->get_course();
        $section = course_get_format($this->page->course)->get_section($sectionno);
        //$rightcontent = $this->section_right_content($section, $course, false);
        $format = course_get_format($course);
        $modinfo = $format->get_modinfo();

        // We need a section_info object, not a record.
        $section = $modinfo->get_section_info($section->section);

        $widgetclass = $format->get_output_classname('content\\section\\controlmenu');
        $widget = new $widgetclass($format, $section);
        $controls = $widget->section_control_items();
        $rightcontent = "";
        if (!empty($controls)) {
            $menu = new action_menu();
            $menu->set_menu_trigger(get_string('edit'));
            $menu->attributes['class'] .= ' section-actions';
            foreach ($controls as $value) {
                $url = empty($value['url']) ? '' : $value['url'];
                $icon = empty($value['icon']) ? '' : $value['icon'];
                $name = empty($value['name']) ? '' : $value['name'];
                $attr = empty($value['attr']) ? array() : $value['attr'];
                $class = empty($value['pixattr']['class']) ? '' : $value['pixattr']['class'];
                $al = new action_menu_link_secondary(
                    new moodle_url($url),
                    new pix_icon($icon, '', null, array('class' => "smallicon " . $class)),
                    $name,
                    $attr
                );
                $menu->add($al);
            }

            $rightcontent .= html_writer::div(
                $this->render($menu),
                'section_action_menu',
                array('data-sectionid' => $section->id)
            );
        }
        $o .= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
        $o .= html_writer::start_tag('div', array('class' => 'content'));
        $o .= $this->output->heading(
            get_string('orphanedactivitiesinsectionno', '', $sectionno),
            3,
            'sectionname'
        );
        return $o;
    }
}
