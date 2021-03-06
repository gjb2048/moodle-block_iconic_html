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
 * Form for editing HTML block instances.
 *
 * @package   block_iconic_html
 * @copyright &copy; 2014-onwards G J Barnard in respect to modifications of the HTML block.
 * @copyright 1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_iconic_html extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_iconic_html');
    }

    function has_config() {
        return true;
    }

    function applicable_formats() {
        return array('all' => true);
    }

    function specialization() {
        $this->title = isset($this->config->title) ? format_string($this->config->title) : format_string(get_string('newiconichtmlblock', 'block_iconic_html'));
        if (!empty($this->config->iconclass)) {
            $this->title = '<span class=\'fa fa-'.$this->config->iconclass.'\'></span>'.$this->title;
        } else {
            $this->title = '<span class=\'fa fa-star\'></span>'.$this->title;
        }
    }

    function instance_allow_multiple() {
        return true;
    }

    function get_content() {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

        if ($this->content !== NULL) {
            return $this->content;
        }

        $filteropt = new stdClass;
        $filteropt->overflowdiv = true;
        if ($this->content_is_trusted()) {
            // Fancy html allowed only on course, category and system blocks.
            $filteropt->noclean = true;
        }

        $this->content = new stdClass;
        $this->content->footer = '';
        if (isset($this->config->text)) {
            // Rewrite url.
            $this->config->text = file_rewrite_pluginfile_urls($this->config->text, 'pluginfile.php', $this->context->id, 'block_iconic_html', 'content', NULL);
            /* Default to FORMAT_HTML which is what will have been used before the
               editor was properly implemented for the block. */
            $format = FORMAT_HTML;
            // Check to see if the format has been properly set on the config.
            if (isset($this->config->format)) {
                $format = $this->config->format;
            }
            $this->content->text = format_text($this->config->text, $format, $filteropt);
        } else {
            $this->content->text = '';
        }

        // See the content of the configdata in the block_instances table to understand that the FontAwesome class is being stored for backup / restore purposes.
        // $this->content->text .= print_r(unserialize(base64_decode('Tzo4OiJzdGRDbGFzcyI6NDp7czo1OiJ0aXRsZSI7czoxNzoiVGVzdCBJY29uaWMgQmxvY2siO3M6NzoiZmFjbGFzcyI7czo1OiJzdGVhbSI7czo2OiJmb3JtYXQiO3M6MToiMSI7czo0OiJ0ZXh0IjtzOjE5OiI8cD5UZXN0IGNvbnRlbnQ8L3A+Ijt9')), true);

        unset($filteropt); // Memory footprint.

        return $this->content;
    }


    /**
     * Serialize and store config data
     */
    function instance_config_save($data, $nolongerused = false) {
        global $DB;

        $config = clone($data);
        // Move embedded files into a proper filearea and adjust HTML links to match.
        $config->text = file_save_draft_area_files($data->text['itemid'], $this->context->id, 'block_iconic_html', 'content', 0, array('subdirs'=>true), $data->text['text']);
        $config->format = $data->text['format'];

        parent::instance_config_save($config, $nolongerused);
    }

    function instance_delete() {
        global $DB;
        $fs = get_file_storage();
        $fs->delete_area_files($this->context->id, 'block_iconic_html');
        return true;
    }

    function content_is_trusted() {
        global $SCRIPT;

        if (!$context = context::instance_by_id($this->instance->parentcontextid, IGNORE_MISSING)) {
            return false;
        }
        //find out if this block is on the profile page
        if ($context->contextlevel == CONTEXT_USER) {
            if ($SCRIPT === '/my/index.php') {
                /* This is exception - page is completely private, nobody else may see content there
                   that is why we allow JS here. */
                return true;
            } else {
                // No JS on public personal pages, it would be a big security issue.
                return false;
            }
        }

        return true;
    }

    /**
     * The block should only be dockable when the title of the block is not empty
     * and when parent allows docking.
     *
     * @return bool
     */
    public function instance_can_be_docked() {
        return (!empty($this->config->title) && parent::instance_can_be_docked());
    }

    /*
     * Add custom html attributes to aid with theming and styling
     *
     * @return array
     */
    function html_attributes() {
        global $CFG;

        $attributes = parent::html_attributes();

        if (!empty($CFG->block_html_allowcssclasses)) {
            if (!empty($this->config->classes)) {
                $attributes['class'] .= ' '.$this->config->classes;
            }
        }

        return $attributes;
    }
}
