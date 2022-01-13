<?php
/**
 * Allows logged in users by, sends others to logout.php
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2021 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2022 01 12
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
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
