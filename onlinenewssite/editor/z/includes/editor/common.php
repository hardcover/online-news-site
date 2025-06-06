<?php
/**
 * Common variables and functions
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Online News <useTheContactForm@onlinenewssite.com>
 * @copyright 2025 Online News
 * @license   https://onlinenewssite.com/license.html
 * @version   2025 05 12
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/onlinenewsllc/online-news-site
 */
//
// Variables
//
$dbAdvertising = 'sqlite:' . $includesPath . '/databases/advertising.sqlite';
$dbArchive = 'sqlite:' . $includesPath . '/databases/archive.sqlite';
$dbArchive2 = 'sqlite:' . $includesPath . '/databases/archive2.sqlite';
$dbArticleId = 'sqlite:' . $includesPath . '/databases/articleId.sqlite';
$dbCalendar = 'sqlite:' . $includesPath . '/databases/calendar.sqlite';
$dbClassifieds = 'sqlite:' . $includesPath . '/databases/classifieds.sqlite';
$dbEdit = 'sqlite:' . $includesPath . '/databases/edit.sqlite';
$dbEdit2 = 'sqlite:' . $includesPath . '/databases/edit2.sqlite';
$dbEditors = 'sqlite:' . $includesPath . '/databases/editors.sqlite';
$dbLogEditor = 'sqlite:' . $includesPath . '/databases/logEditor.sqlite';
$dbLogSubscriber = 'sqlite:' . $includesPath . '/databases/logSubscriber.sqlite';
$dbMenu = 'sqlite:' . $includesPath . '/databases/menu.sqlite';
$dbPhotoId = 'sqlite:' . $includesPath . '/databases/photoId.sqlite';
$dbPublished = 'sqlite:' . $includesPath . '/databases/published.sqlite';
$dbPublished2 = 'sqlite:' . $includesPath . '/databases/published2.sqlite';
$dbSettings = 'sqlite:' . $includesPath . '/databases/settings.sqlite';
$dbSubscribers = 'sqlite:' . $includesPath . '/databases/subscribers.sqlite';
$dbSurvey = 'sqlite:' . $includesPath . '/databases/survey.sqlite';
//
// Set the default timezone based on the GMT offset in configuration.php
//
$timezone = [
    '-12'     => 'Kwajalein',
    '-11'     => 'Pacific/Midway',
    '-10'     => 'Pacific/Honolulu',
     '-9'     => 'America/Anchorage',
     '-8'     => 'America/Los_Angeles',
     '-7'     => 'America/Denver',
     '-6'     => 'America/Tegucigalpa',
     '-5'     => 'America/New_York',
     '-4.30'  => 'America/Caracas',
     '-4'     => 'America/Halifax',
     '-3.30'  => 'America/St_Johns',
     '-3'     => 'America/Sao_Paulo',
     '-2'     => 'Atlantic/South_Georgia',
     '-1'     => 'Atlantic/Azores',
      '0'     => 'Europe/Dublin',
      '1'     => 'Europe/Belgrade',
      '2'     => 'Europe/Minsk',
      '3'     => 'Asia/Kuwait',
      '3.30'  => 'Asia/Tehran',
      '4'     => 'Asia/Muscat',
      '5'     => 'Asia/Yekaterinburg',
      '5.30'  => 'Asia/Kolkata',
      '5.45'  => 'Asia/Katmandu',
      '6'     => 'Asia/Dhaka',
      '6.30'  => 'Asia/Rangoon',
      '7'     => 'Asia/Krasnoyarsk',
      '8'     => 'Asia/Brunei',
      '9'     => 'Asia/Seoul',
      '9.30'  => 'Australia/Darwin',
      '10'    => 'Australia/Canberra',
      '11'    => 'Asia/Magadan',
      '12'    => 'Pacific/Fiji',
      '13'    => 'Pacific/Tongatapu'
];
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
    if (!empty($str)) {
        $str = stripslashes($str);                             // Magic quotes
        $str = html_entity_decode($str);                       // HTML chars
        $str = preg_replace('{^\xEF\xBB\xBF|\x1A}', '', $str); // UTF-8 BOM
        $str = str_replace("\r\n", "\n", $str);                // Windows line ends
        $str = str_replace("\r", "\n", $str);                  // Old Mac line ends
        $str = str_replace("\t", '', $str);                    // Tabs
    } else {
        $str = '';
    }
    return trim($str);                                     // Extra space & lines
}
/**
 * Function to set a value to either '' or a secure post variable
 *
 * @param mixed $param The post value
 *
 * @return The secure version of the value
 */
function securePost($param)
{
    if (!empty($_POST[$param])) {
        $str = secure($_POST[$param]);
    } else {
        $str = '';
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
    if (isset($_POST[$param]) and trim($_POST[$param]) !== '') {
        $str = secure($_POST[$param]);
        $str = preg_replace("'\s+'", ' ', $str);
    } else {
        $str = '';
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
    return @htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, "UTF-8");
}
/**
 * Function to convert a string to UTF-8 encoding
 *
 * @param string $str The string
 *
 * @return The converted UTF-8 version of the string
 */
function utf8($str)
{
    return mb_convert_encoding($str, "UTF-8", mb_detect_encoding($str, "UTF-8, ISO-8859-1, ISO-8859-15", true));
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
    if (!empty($str)) {
        echo "\n" . '    <p class="error">' . $str . "</p>\n";
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
    if (!empty($str)) {
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
    if (!empty($str)) {
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
    if (strval($str) === '1') {
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
    if (empty($str)) {
        return '';
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
    if (empty($str)) {
        return '';
    } else {
        return base64_decode(str_rot13($str));
    }
}
/**
 * Function to echo the maximum upload file size
 *
 * @return The response associated array
 */
function uploadFilesizeMaximum()
{
    $postMaxSize = @intval(@ini_get('post_max_size'));
    $uploadMaxFilesize = @intval(@ini_get('upload_max_filesize'));
    if (!empty($postMaxSize) and !empty($uploadMaxFilesize)) {
        if ($postMaxSize < $uploadMaxFilesize) {
            $maxFileSize = intval($postMaxSize - 1);
        } else {
            $maxFileSize = intval($uploadMaxFilesize - 1);
        }
        $maxFileSize = ', ' . $maxFileSize . ' MB maximum filesize';
    } else {
        $maxFileSize = '';
    }
    echo $maxFileSize;
}
/**
 * Function to return textarea values, when they exist
 *
 * @param string $str The value
 *
 * @return The appropriate HTML and value
 */
function returnIfText($str)
{
    if (!empty($str)) {
        return html($str);
    }
}
/**
 * Function to echo input values, when they exist
 *
 * @param string $str The value
 *
 * @return The appropriate HTML and value
 */
function returnIfValue($str)
{
    if (!empty($str)) {
        return ' value="' . html($str) . '"';
    }
}
/**
 * Function to show a selected radio button when the value is 1
 *
 * @param string $str The value
 *
 * @return The appropriate HTML
 */
function returnIfYes($str)
{
    if ($str === '1') {
        return ' checked';
    }
}
/**
 * Function to retrieve a random advertisement
 *
 * @return An advertisement
 */
function advertisement()
{
    global $includesPath, $dbAdvertising, $today, $adMinParagraphs, $adMaxAdverts;
    $dbh = new PDO($dbAdvertising);
    $stmt = $dbh->prepare('SELECT idAd FROM advertisements WHERE (? >= startDateAd AND ? <= endDateAd) ORDER BY sortOrderAd');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$today, $today]);
    foreach ($stmt as $row) {
        $idAds[] = $row['idAd'];
    }
    if (empty($idAds)) {
        $idAd = '';
        $adLink = '';
    } else {
        //
        // Randomly cycle all ads before repeating
        //
        $adsPrior = [];
        $adsTotal = count($idAds);
        if (file_exists($includesPath . '/adRotate/' . $_SERVER['REMOTE_ADDR'])) {
            $adsPrior = file($includesPath . '/adRotate/' . $_SERVER['REMOTE_ADDR']);
            $adsDisplayed = count($adsPrior);
        } else {
            $adsDisplayed = 0;
        }
        if ($adsDisplayed >= $adsTotal) {
            $adsPrior = [];
        }
        $adKey = array_rand($idAds);
        for ($i = 1; $i < $adsTotal; $i++) {
            if (in_array($adKey, $adsPrior)) {
                $adKey = array_rand($idAds);
            } else {
                break;
            }
        }
        $logDisplayed = '';
        foreach ($adsPrior as $key) {
            $key = str_replace("\n", '', $key);
            $logDisplayed.= $key . "\n";
        }
        $logDisplayed.= $adKey;
        file_put_contents($includesPath . '/adRotate/' . $_SERVER['REMOTE_ADDR'], $logDisplayed);
        //
        // Select the ad
        //
        $idAd = $idAds[$adKey];
        $stmt = $dbh->prepare('SELECT link, linkAlt FROM advertisements WHERE idAd=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idAd]);
        $row = $stmt->fetch();
        if ($row) {
            extract($row);
            if (!empty($link)) {
                $linkHtml1 = '  <p class="a"><a href="' . $link . '" target="_blank" rel="nofollow">';
                $linkHtml2 = '</a>';
            } else {
                $linkHtml1 = '  <p class="a">';
                $linkHtml2 = '';
            }
            $adLink = $linkHtml1 . '<img class="ad border" src="imaged.php?i=' . muddle($idAd) . '" alt="' . $linkAlt . '">' . $linkHtml2 . '</p>' . "\n\n";
        }
    }
    $dbh = null;
    return $adLink;
}
?>
