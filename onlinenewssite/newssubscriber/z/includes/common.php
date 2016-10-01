<?php
/**
 * Common variables and functions
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2016 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *.@license   http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2016-10-01
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
//
// Variables
//
$dbAdvertising = 'sqlite:' . $includesPath . '/databases/advertising.sqlite';
$dbArchive = 'sqlite:' . $includesPath . '/databases/archive.sqlite';
$dbArchive2 = 'sqlite:' . $includesPath . '/databases/archive2.sqlite';
$dbCalendar = 'sqlite:' . $includesPath . '/databases/calendar.sqlite';
$dbClassifieds = 'sqlite:' . $includesPath . '/databases/classifieds.sqlite';
$dbClassifiedsNew = 'sqlite:' . $includesPath . '/databases/classifiedsNew.sqlite';
$dbEdit = 'sqlite:' . $includesPath . '/databases/edit.sqlite';
$dbEdit2 = 'sqlite:' . $includesPath . '/databases/edit2.sqlite';
$dbEditors = 'sqlite:' . $includesPath . '/databases/editors.sqlite';
$dbLog = 'sqlite:' . $includesPath . '/databases/log.sqlite';
$dbMenu = 'sqlite:' . $includesPath . '/databases/menu.sqlite';
$dbPublished = 'sqlite:' . $includesPath . '/databases/published.sqlite';
$dbPublished2 = 'sqlite:' . $includesPath . '/databases/published2.sqlite';
$dbRemote = 'sqlite:' . $includesPath . '/databases/remote.sqlite';
$dbSettings = 'sqlite:' . $includesPath . '/databases/settings.sqlite';
$dbSubscribers = 'sqlite:' . $includesPath . '/databases/subscribers.sqlite';
$dbSubscribersNew = 'sqlite:' . $includesPath . '/databases/subscribersNew.sqlite';
$dbSurvey = 'sqlite:' . $includesPath . '/databases/survey.sqlite';
//
// Set the default timezone based on the GMT offset in configuration.php
//
$timezone = array(
    -12     => 'Kwajalein',
    -11     => 'Pacific/Midway',
    -10     => 'Pacific/Honolulu',
     -9     => 'America/Anchorage',
     -8     => 'America/Los_Angeles',
     -7     => 'America/Denver',
     -6     => 'America/Tegucigalpa',
     -5     => 'America/New_York',
    "-4.30" => 'America/Caracas',
     -4     => 'America/Halifax',
    "-3.30" => 'America/St_Johns',
     -3     => 'America/Sao_Paulo',
     -2     => 'Atlantic/South_Georgia',
     -1     => 'Atlantic/Azores',
      0     => 'Europe/Dublin',
      1     => 'Europe/Belgrade',
      2     => 'Europe/Minsk',
      3     => 'Asia/Kuwait',
     "3.30" => 'Asia/Tehran',
      4     => 'Asia/Muscat',
      5     => 'Asia/Yekaterinburg',
     "5.30" => 'Asia/Kolkata',
     "5.45" => 'Asia/Katmandu',
      6     => 'Asia/Dhaka',
     "6.30" => 'Asia/Rangoon',
      7     => 'Asia/Krasnoyarsk',
      8     => 'Asia/Brunei',
      9     => 'Asia/Seoul',
     "9.30" => 'Australia/Darwin',
     10     => 'Australia/Canberra',
     11     => 'Asia/Magadan',
     12     => 'Pacific/Fiji',
     13     => 'Pacific/Tongatapu'
);
date_default_timezone_set($timezone[$gmtOffset]);
$today = date("Y-m-d");
/**
 * Function to secure and clean post and get variables
 *
 * @param string $str The value to be secured
 *
 * @return The secure version of the value
 */
function secure($str)
{
    $str = stripslashes($str);                             // Magic quotes
    $str = html_entity_decode($str);                       // HTML chars
    //$str = strip_tags($str);                               // HTML & PHP tags
    $str = preg_replace('{^\xEF\xBB\xBF|\x1A}', '', $str); // UTF-8 BOM
    $str = str_replace("\r\n", "\n", $str);                // Windows line ends
    $str = str_replace("\r", "\n", $str);                  // Old Mac line ends
    $str = str_replace("\t", '    ', $str);                // Tabs
    return trim($str);                                     // Extra space & lines
}
/**
 * Function to set a value to either null or a secure post variable
 *
 * @param mixed $param The post value
 *
 * @return The secure version of the value
 */
function securePost($param)
{
    if (isset($_POST[$param]) and $_POST[$param] != '') {
        $str = secure($_POST[$param]);
    } else {
        $str = null;
    }
    return $str;
}
/**
 * Function like securePost, also removes new lines and multiple spaces
 *
 * @param mixed $param The post value
 *
 * @return The cleaned version of the value
 */
function inlinePost($param)
{
    if (isset($_POST[$param]) and trim($_POST[$param]) != '') {
        $str = secure($_POST[$param]);
        $str = preg_replace("'\s+'", ' ', $str);
    } else {
        $str = null;
    }
    return $str;
}
/**
 * Function to convert applicable characters to HTML entities
 *
 * @param string $str The string
 *
 * @return The converted HTML version of the string
 */
function html($str)
{
    return @htmlentities($str, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, "UTF-8");
}
/**
 * Function to change new lines to paragraphs
 *
 * @param string $str The string
 *
 * @return The string with paragraph tags
 */
function nl2p($str)
{
    $str = preg_replace("'\s+'", ' ', html($str));
    $str = str_replace("&NewLine; &NewLine;", "&NewLine;&NewLine;", $str);
    $str = str_replace("&NewLine;&NewLine;", "</p>\n\n  <p>", $str);
    return $str;
}
/**
 * Function to echo information and error messages, when they exist
 *
 * @param string $str The message
 *
 * @return The appropriate HTML and message
 */
function echoIfMessage($str)
{
    if ($str != null) {
        echo "\n" . '  <p class="e">' . $str . "</p>\n";
    }
}
/**
 * Function to echo input values, when they exist
 *
 * @param string $str The value
 *
 * @return The appropriate HTML and value
 */
function echoIfValue($str)
{
    if ($str != null) {
        echo ' value="' . html($str) . '"';
    }
}
/**
 * Function to echo textarea values, when they exist
 *
 * @param string $str The value
 *
 * @return The appropriate HTML and value
 */
function echoIfText($str)
{
    if ($str != null) {
        echo html($str);
    }
}
/**
 * Function to show a selected radio button when the value is 1
 *
 * @param string $str The value
 *
 * @return The appropriate HTML
 */
function echoIfYes($str)
{
    if ($str == 1) {
        echo ' checked';
    }
}
/**
 * Function to show a selcted radio button when the value is null
 *
 * @param string $str The value
 *
 * @return The appropriate HTML
 */
function echoIfNo($str)
{
    if ($str == null) {
        echo ' checked';
    }
}
/**
 * Function to obfuscate a string value for human and simple machine readers
 *
 * @param string $str The original string value
 *
 * @return An encoded muddle
 */
function muddle($str)
{
    if ($str == null or $str == '') {
        return null;
    } else {
        return str_rot13(base64_encode($str));
    }
}
/**
 * Function to make plain a string value obfuscated with muddle
 *
 * @param string $str The encoded muddle
 *
 * @return The original string value before it was muddled
 */
function plain($str)
{
    if ($str == null or $str == '') {
        return null;
    } else {
        return base64_decode(str_rot13($str));
    }
}
/**
 * Function for HTML request/response RPC SOA, posts a request array and captures the response array
 *
 * @param string $uri     The URI to post to
 * @param array  $request The request associated array
 *
 * @return The response associated array
 */
function soa($uri, $request)
{
    include 'crypt.php';
    $request['onus'] = $onus;
    date_default_timezone_set('America/Los_Angeles');
    $request['gig'] = date($gig);
    $request = http_build_query(array_map('base64_encode', $request));
    $ctx = stream_context_create(
        array(
            'ssl' => array(
                'allow_self_signed' => true,
                'verify_peer' => false,
                'verify_peer_name' => false),
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $request))
    );
    $fp = @fopen($uri, 'rb', false, $ctx);
    //
    // Check for and log any errors in the response
    //
    $redFlag = null;
    if (!$fp) {
        $redFlag.= "Can not find the URI.\n";
    }
    $response = @stream_get_contents($fp);
    if ($response == false) {
        $redFlag.= "The URI contained no information.\n";
    }
    if (strpos($response, 'Fatal error:') !== false or strpos($response, 'Notice:') !== false or strpos($response, 'Warning:') !== false) {
        $redFlag.= strip_tags(str_replace(array('[',']'), array('<','>'), $response)) . "\n";
    }
    if ($redFlag != null) {
        $prior = file_exists('error_log') ? file_get_contents('error_log') : null;
        file_put_contents('error_log', date('g:i a, l, F j, Y') . "\n" . $uri . "\n" . $redFlag . "\n" . $prior);
    } else {
        $response = json_decode($response, true);
        if (is_array($response)) {
            //
            // Return the response array when there are no errors
            //
            $response = array_map('base64_decode', $response);
            return $response;
        } else {
            //
            // Write an error to error_log
            //
            $prior = file_exists('error_log') ? file_get_contents('error_log') : null;
            file_put_contents('error_log', date('g:i a, l, F j, Y') . "\n" . $uri . "\nResponse is not an array.\n" . $prior);
        }
    }
}
?>
