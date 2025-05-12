<?php
/**
 * The edit page for articles not yet published
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2025 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 * @version:  2025 05 12
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
session_start();
require 'z/system/configuration.php';
$includesPath = '../' . $includesPath;
require $includesPath . '/editor/authorization.php';
require $includesPath . '/editor/common.php';
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
if (empty($row['userType']) or strval($row['userType']) !== '1') {
    include 'logout.php';
    exit;
}
//
// Variables
//
$editorView = '1';
$links = null;
$menu = "\n" . '  <nav class="n">
    <h4 class="m"><a class="s" href="edit.php">Edit</a><a class="m" href="published.php">Published</a><a class="m" href="preview.php">Preview</a><a class="m" href="archive.php">Archives</a></h4>
  </nav>' . "\n\n";
$publishedIndexAdminLinks = null;
$title = 'Edit';
$use = 'edit';
//
// Programs
//
require $includesPath . '/editor/editor.php';
?>
