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
 * Report Page
 *
 * @package   report_conversations
 * @copyright 2020 Adam King, SHEilds eLearning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$courseid = optional_param('course', null, PARAM_INT);
$conversationid = optional_param('conversation', null, PARAM_INT);

// Cache any users we may use again in queries.
$usercache = [];

if ($courseid != null)
{
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $context = context_course::instance($courseid);

    require_login($course, false);
    require_capability('report/conversations:course', $context);

    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/report/conversations/index.php', [
        'course' => $courseid
    ]));
}
else
{
    $context = context_system::instance();

    require_login();
    require_capability('report/conversations:site', $context);

    admin_externalpage_setup('conversationreport');

    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/report/conversations/index.php'));
}

$PAGE->set_title('Site Message Report');
$PAGE->set_heading('Conversation Report');
$PAGE->set_pagelayout('report');

$output = $PAGE->get_renderer('report_conversations');

/**
 * Query the cache for the given user ID and fetch from the database if it was not found.
 * 
 * @param int $userid The ID of the user to fetch.
 */
function get_user($userid)
{
    global $DB;

    $user = (isset($usercache[$userid]))
        ? $usercache[$userid]
        : $DB->get_record('user', ['id' => $userid], '*');

    $usercache[$userid] = $user;

    return $user;
}

echo $output->header();

if ($conversationid == null)
{
    // TODO:- Figure out how to specify course context.
    $conversations = $DB->get_records('message_conversations', [], 'timecreated');

    foreach ($conversations as $conversation)
    {
        $originalConversation = clone $conversation;

        $members = $DB->get_records('message_conversation_members', [
            'conversationid' => $conversation->id
            // TODO:- Determine course-specific.
        ]);

        // Loop over the conversation members and add them to the conversation data.
        $conversation->members = [];
        foreach ($members as $member)
        {
            $conversation->members[$member->userid] = get_user($member->userid);
        }

        // Loop over the conversation properties and parse the data.
        foreach ($conversation as $key => $value)
        {
            // Link conversations to message reports.
            if ($key == "id" || $key == "name")
            {
                $conversationParams = ['conversation' => $originalConversation->id];
                if ($courseid != null) $conversationParams['course'] = $courseid;

                $conversationUri = new moodle_url('/report/conversations/index.php', $conversationParams);
                $conversation->{$key} = html_writer::tag('a', $value, ['href' => $conversationUri->out(false)]);
            }

            // Convert message types into strings.
            if ($key == "type")
            {
                if (intval($value) === 1) $conversation->{$key} = get_string('individual', 'report_conversations');
                if (intval($value) === 2) $conversation->{$key} = get_string('group', 'report_conversations');
                if (intval($value) === 3) $conversation->{$key} = get_string('self', 'report_conversations');
            }

            // Print timestamps as date-time.
            if ($key == "timecreated" || $key == "timemodified")
            {
                $conversation->{$key} = date('d/m/Y h:i:s', $value);
            }

            // Parse members and flatten the data.
            if ($key == "members")
            {
                $count = 0;
                $newValue = '';
                foreach ($value as $index => $member)
                {
                    $memberUri = new moodle_url('/user/view.php', ['id' => $index]);
                    $memberFullName = ($member != null) ? "$member->firstname $member->lastname" : get_string('usernotfound', 'report_conversations');
                    $newValue .= html_writer::tag('a', $memberFullName, ['href' => $memberUri->out(false)]);

                    // If we're not on the last member, add a comma.
                    if ($count < count($value) - 1 && count($value) != 1)
                    {
                        $newValue .= ', ';
                    }

                    $count++;
                }

                $conversation->{$key} = $newValue;
            }

            // Remove unwanted keys.
            if (in_array($key, ['convhash']))
            {
                unset($conversation->{$key});
            }
        }
    }

    echo $output->conversations_table($conversations);
}
else
{
    $messages = $DB->get_records('messages', ['conversationid' => $conversationid], 'timecreated');

    if (empty($messages))
    {
        echo get_string('nomessagesfounderror', 'report_conversations');
    }

    foreach ($messages as $message)
    {
        foreach ($message as $key => $value)
        {
            // Link user ids to profiles.
            if ($key == "useridfrom")
            {
                $user = get_user($value);

                $userUri = new moodle_url('/user/view.php', ['id' => $value]);
                $userFullName = "$user->firstname $user->lastname";

                $message->{$key} = html_writer::tag('a', $userFullName, ['href' => $userUri->out(false)]);
            }

            // Print timestamps as date-time.
            if ($key == "timecreated" || $key == "timemodified")
            {
                $message->{$key} = date('d/m/Y h:i:s', $value);
            }

            // Remove unwanted keys.
            if (in_array($key, ['fullmessageformat', 'smallmessage', 'fullmessagehtml', 'fullmessagetrust', 'customdata']))
            {
                unset($message->{$key});
            }
        }
    }

    echo $output->messages_table($messages);

    $conversationParams = [];
    if ($courseid != null) $conversationParams['course'] = $courseid;
    $conversationUri = new moodle_url('/report/conversations/index.php', $conversationParams);
    echo html_writer::empty_tag('br');
    echo html_writer::tag('a', get_string('returntoreport', 'report_conversations'), [
        'href' => $conversationUri->out(false)
    ]);
}

echo $output->footer();
