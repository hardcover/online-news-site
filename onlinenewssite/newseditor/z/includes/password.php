<?php
/**
 * Gets the version of password_verify appropriate to the PHP version
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *            http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2018 01 08
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
//
// Variables
//
$phpVersion = phpversion();
$phpVersionSub = strrchr($phpVersion, '.');
$phpVersionMain = str_replace($phpVersionSub, '', $phpVersion);
$phpVersionSub = ltrim($phpVersionSub, '.');
//
// Select the appropriate password_verify version
//
if ($phpVersion < '5.5') {
    if ($phpVersionMain === '5.4' or ($phpVersionMain === '5.3' and $phpVersionSub > 6)) {
        include 'password_compat/password.php';
    }
}
?>