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
 * Strings for the quizaccess_seb plugin.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Safe Exam Browser templates and settings';

// Privacy.
$string['privacy:metadata'] = 'The Safe Exam Browser quiz access rule plugin does not store any personal data.';

// Admin settings.
$string['seb_managetemplates'] = 'Manage Safe Exam Browser templates';

// Quiz form settings.
$string['seb'] = 'Safe Exam Browser';
$string['seb_help'] = 'Setup quiz to use the Safe Exam Browser.';
$string['seb_activateurlfiltering'] = 'Activate URL filtering';
$string['seb_activateurlfiltering_help'] = 'If enabled, URLs will be filtered when loading web pages. The filter set has to be defined below.';
$string['seb_allowreloadinexam'] = 'Allow reload in exam';
$string['seb_allowreloadinexam_help'] = 'If enabled, page reload is allowed (reload button in SEB task bar, browser tool bar, iOS side slider menu, keyboard shortcut F5/cmd+R). Offline caching might break when the user tries to reload a page without internet connection.';
$string['seb_allowspellchecking'] = 'Allow spell checking';
$string['seb_allowspellchecking_help'] = 'If enabled, spell checking in the SEB browser is allowed.';
$string['seb_allowuserquitseb'] = 'Allow user to quit SEB';
$string['seb_allowuserquitseb_help'] = 'If enabled, users can quit SEB with the “Quit” button in the SEB task bar or by pressing the keys Ctrl-Q or by clicking the main browser window close button.';
$string['seb_enableaudiocontrol'] = 'Enable Audio Controls';
$string['seb_enableaudiocontrol_help'] = 'If enabled, the audio control icon is shown in the SEB task bar.';
$string['seb_expressionsallowed'] = 'Expressions allowed';
$string['seb_expressionsallowed_help'] = 'A text field which contains the allowed filtering expressions for the allowed URLs. Use of the wildcard char \'\*\' is possible. Examples for expressions: \'example.com\' or \'example.com/stuff/\*\'. \'example.com\' matches \'example.com\', \'www.example.com\' and \'www.mail.example.com\' \'example.com/stuff/\*\' matches all requests to any subdomain of \'example.com\' that have \'stuff\' as the first segment of the path.';
$string['seb_expressionsblocked'] = 'Expressions blocked';
$string['seb_expressionsblocked_help'] = 'A text field which contains the filtering expressions for the blocked URLs. Use of the wildcard char \'\*\' is possible. Examples for expressions: \'example.com\' or \'example.com/stuff/\*\'. \'example.com\' matches \'example.com\', \'www.example.com\' and \'www.mail.example.com\' \'example.com/stuff/\*\' matches all requests to any subdomain of \'example.com\' that have \'stuff\' as the first segment of the path.';
$string['seb_filterembeddedcontent'] = 'Filter also embedded content';
$string['seb_filterembeddedcontent_help'] = 'If enabled, also all embedded resources will be filtered using the filter set.';
$string['seb_linkquitseb'] = 'Link to quit SEB after exam';
$string['seb_linkquitseb_help'] = 'If enabled, a link to quit SEB will appear after the exam is submitted. When clicking the link, it is possible to quit SEB without having to press the ”Quit” button and to enter a quit password.';
$string['seb_muteonstartup'] = 'Mute on startup';
$string['seb_muteonstartup_help'] = 'If enabled, audio is initially muted when starting SEB.';
$string['seb_quitpassword'] = 'Quit/unlock password';
$string['seb_quitpassword_help'] = 'This password is prompted when users try to quit SEB with the ”Quit” button, Ctrl-Q or the close button in the main browser window. If no quit password is set, then SEB just prompts “Are you sure you want to quit SEB?”.';
$string['seb_regexallowed'] = 'Regex allowed';
$string['seb_regexallowed_help'] = 'A text field which contains the filtering expressions for allowed URLs in a regular expression (Regex) format.';
$string['seb_regexblocked'] = 'Regex blocked';
$string['seb_regexblocked_help'] = 'A text field which contains the filtering expressions for blocked URLs in a regular expression (Regex) format.';
$string['seb_requiresafeexambrowser'] = 'Require the use of Safe Exam Browser';
$string['seb_requiresafeexambrowser_help'] = 'If enabled, students can only attempt the quiz using the Safe Exam Browser.';
$string['seb_sebconfigtemplate'] = 'Safe Exam Browser config template';
$string['seb_sebconfigtemplate_help'] = 'The settings in the selected config template will be used for the configuration of the Safe Exam Browser while attempting the quiz.';
$string['seb_showkeyboardlayout'] = 'Show keyboard layout';
$string['seb_showkeyboardlayout_help'] = 'If enabled, the current keyboard layout is shown in the SEB task bar. It allows you to switch to other keyboard layouts, which have been enabled in the operating system.';
$string['seb_showreloadbutton'] = 'Show reload button';
$string['seb_showreloadbutton_help'] = 'If enabled, a reload button appears in the SEB task bar. This button allows to reload the current web page.';
$string['seb_showsebtaskbar'] = 'Show SEB task bar';
$string['seb_showsebtaskbar_help'] = 'If enabled, a task bar appears at the bottom of the SEB browser window. In case you like to show the WiFi control, the reload button, the time or the keyboard layout to your students, you have to activate the task bar. The task bar is also needed when you permit third party applications, which are displayed as icons in the task bar.';
$string['seb_showtime'] = 'Show time';
$string['seb_showtime_help'] = 'If enabled, the current time is displayed in the SEB task bar.';
$string['seb_showwificontrol'] = 'Show Wifi control';
$string['seb_showwificontrol_help'] = 'If enabled, a WiFi control button appears in the SEB task bar. The button allows to reconnect to WiFi networks which have previously been connected to.';
$string['seb_suppresssebdownloadlink'] = 'Suppress Safe Exam Browser download link';
$string['seb_suppresssebdownloadlink_help'] = 'If enabled, no download link for Safe Exam Browser will be shown on the quiz start page.';
$string['seb_sebconfigfile'] = 'Upload Safe Exam Browser config';
$string['seb_sebconfigfile_help'] = 'Please upload your own Safe Exam Browser config file for this quiz.';
$string['seb_userconfirmquit'] = 'Ask user to confirm quitting';
$string['seb_userconfirmquit_help'] = 'If enabled, users have to confirm quitting of SEB when a quit link is detected.';

// Exceptions.
$string['missingrequiredsettings'] = 'Config settings not provided all required settings.';
$string['noconfigfound'] = 'No SEB config could be found for quiz with cmid: {$a}';

// Capabilities.
$string['seb:bypassseb'] = 'Bypass the requirement to view quiz in Safe Exam Browser.';

// Quiz access information.
$string['invalidkeys'] = "The config key or browser exam keys could not be validated. Please ensure you are using the Safe Exam Browser with correct configuration file.";
$string['sebrequired'] = "This quiz has been configured so that students may only attempt it using the Safe Exam Browser.";
$string['sebdownloadlink'] = 'https://safeexambrowser.org/download_en.html';
$string['sebdownloadbutton'] = 'Download Safe Exam Browser';
$string['seblinkbutton'] = 'Launch Safe Exam Browser';
$string['httplinkbutton'] = 'Download Configuration';
