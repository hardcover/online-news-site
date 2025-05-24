<?php
/**
 * Allows logged in users by, sends others to logout.php
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
$uri = $uriScheme . '://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
$oldCookie = isset($_COOKIE['PHPSESSID']) ? $_COOKIE['PHPSESSID'] : null;
//
// Test authorization
//
if (!isset($_SESSION['auth'])
    or (strval(session_id()) !== strval($oldCookie))
    or (strval($_SESSION['auth']) !== strval(hash('sha256', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']) . $_SESSION['userID']))
) {
    include 'logout.php';
    exit;
}
session_regenerate_id(true);
?>
