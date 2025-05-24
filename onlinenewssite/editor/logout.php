<?php
/**
 * Available as a link when logged in, called by programs when authorization fails
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
@session_start();
if (isset($_SESSION['getAuthorization'])) {
    $getAuthorization = $_SESSION['getAuthorization'];
} else {
    $getAuthorization = '';
}
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
