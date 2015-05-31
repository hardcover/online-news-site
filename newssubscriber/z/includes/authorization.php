<?php
/**
 * Allows logged in users by, sends others to the appropriate page
 *
 * PHP version 5
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2013-2015 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *.@license   http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version   GIT: 2015-05-31
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
//
// Variables
//
$uri = $uriScheme . '://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
if (isset($_COOKIE['PHPSESSID'])) {
    $oldCookie = $_COOKIE['PHPSESSID'];
} else {
    $oldCookie = null;
}
if (isset($_GET['a'])) {
    $_SESSION['a'] = filter_var($_GET['a'], FILTER_VALIDATE_INT);
}
if (isset($_GET['t'])) {
    $_SESSION['t'] = filter_var($_GET['t'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
}
if ((isset($_SERVER['REQUEST_URI']) and strpos($_SERVER['ORIG_PATH_INFO'], 'archive.php'))
    or (isset($_SERVER['ORIG_PATH_INFO']) and strpos($_SERVER['ORIG_PATH_INFO'], 'archive.php'))
) {
    unset($_SESSION['a']);
    unset($_SESSION['t']);
}
//
// Test authorization
//
if (empty($_SESSION['auth'])
    or (strval(session_id()) !== strval($oldCookie))
    or (strval($_SESSION['auth']) !== strval(hash('sha256', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']) . $_SESSION['userID']))
) {
    header('Location: ' . $uri . '?t=l');
    exit;
}
//
// For the authorized
//
session_regenerate_id(true);
//
// For a destination news article
//
if ((isset($_SESSION['a']) and empty($_GET['a']))
    or (isset($_SESSION['a']) and isset($_GET['t']))
) {
    header('Location: ' . $uri . 'news.php?a=' . $_SESSION['a'], true);
    exit;
}
?>
