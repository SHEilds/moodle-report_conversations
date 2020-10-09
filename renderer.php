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
 * Render functions
 *
 * @package   report_conversations
 * @copyright 2020 Adam King, SHEilds eLearning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class report_conversations_renderer extends plugin_renderer_base
{
    public function header()
    {
        return $this->output->header();
    }

    public function footer()
    {
        return $this->output->footer();
    }

    /**
     * @param array $conversations A one-dimensional array of conversation objects.
     */
    public function conversations_table(array $conversations)
    {
        $out = '';

        if (empty($conversations))
        {
            return $out;
        }

        $vars = get_object_vars(reset($conversations));
        $keys = array_keys($vars);

        $out .= html_writer::start_tag('table', ['class' => 'table table-bordered table-striped table-hover']);
        $out .= $this->thead($keys);
        $out .= $this->tbody($conversations);
        $out .= html_writer::end_tag('table');

        return $out;
    }

    /**
     * @param array $conversation A one-dimensional array of message objects.
     */
    public function messages_table(array $messages)
    {
        $out = '';

        if (empty($messages))
        {
            return $out;
        }

        $vars = get_object_vars(reset($messages));
        $keys = array_keys($vars);

        $out .= html_writer::start_tag('table', ['class' => 'table table-bordered table-striped table-hover']);
        $out .= $this->thead($keys);
        $out .= $this->tbody($messages);
        $out .= html_writer::end_tag('table');

        return $out;
    }

    private function thead($keys)
    {
        $thead = html_writer::start_tag('thead');
        $thead .= html_writer::start_tag('tr');

        foreach ($keys as $header)
        {
            $thead .= html_writer::start_tag('td');
            $thead .= $header;
            $thead .= html_writer::end_tag('td');
        }

        $thead .= html_writer::end_tag('tr');
        $thead .= html_writer::end_tag('thead');

        return $thead;
    }

    private function tbody(array $entities)
    {
        $tbody = html_writer::start_tag('tbody');

        foreach ($entities as $entity)
        {
            $entity = get_object_vars($entity);

            $tbody .= html_writer::start_tag('tr');

            foreach ($entity as $property)
            {
                $tbody .= html_writer::start_tag('td');
                $tbody .= $property;
                $tbody .= html_writer::end_tag('td');
            }

            $tbody .= html_writer::end_tag('tr');
        }

        $tbody .= html_writer::end_tag('tbody');

        return $tbody;
    }
}
