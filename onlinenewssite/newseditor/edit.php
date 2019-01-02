<?php
/**
 * The edit page for articles not yet published
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2019 01 02
 * @link      https://hardcoverwebdesign.com/
 * @link      https://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
session_start();
require 'z/system/configuration.php';
require $includesPath . '/authorization.php';
require $includesPath . '/common.php';
require $includesPath . '/parsedown-master/Parsedown.php';
//
// User-group authorization
//
$dbh = new PDO($dbEditors);
$stmt = $dbh->prepare('SELECT userType FROM users WHERE idUser=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$_SESSION['userId']]);
$row = $stmt->fetch();
$dbh = null;
if (empty($row['userType']) or $row['userType'] != 1) {
    include 'logout.php';
    exit;
}
//
// Variables
//
$editorView = 1;
$links = null;
$menu = "\n" . '  <h4 class="m"><a class="s" href="edit.php">&nbsp;Edit&nbsp;</a><a class="m" href="published.php">&nbsp;Published&nbsp;</a><a class="m" href="preview.php">&nbsp;Preview&nbsp;</a><a class="m" href="archive.php">&nbsp;Archives&nbsp;</a></h4>' . "\n\n";
$publishedIndexAdminLinks = null;
$title = 'Edit';
$use = 'edit';
//
// Programs
//
require $includesPath . '/editor.php';
?>
