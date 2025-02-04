<?php
/**
 * Available as a link when logged in, called by programs when authorization fails
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2025 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 * @version:  2025 02 03
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
@session_start();
$getAuthorization = $_SESSION['getAuthorization'];
$_SESSION = [];
session_destroy();
setcookie(session_name(), '', time() -90000);
require 'z/system/configuration.php';
if (empty($getAuthorization)) {
    $uri = $uriScheme . '://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
} else {
    $uri = $uriScheme . '://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/?' . $getAuthorization;
}
header('Location: ' . $uri);
?>
