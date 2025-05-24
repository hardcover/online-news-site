<?php
/**
 * The editing page for currently published articles
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
session_start();
require 'z/system/configuration.php';
$includesPath = '../' . $includesPath;
require $includesPath . '/editor/authorization.php';
require $includesPath . '/parsedown-master/Parsedown.php';
//
// Variables
//
$editorView = '1';
$links = null;
$menu = "\n" . '  <nav class="n">
    <h4 class="m"><a class="m" href="edit.php">Edit</a><a class="s" href="published.php">Published</a><a class="m" href="preview.php">Preview</a><a class="m" href="archive.php">Archives</a></h4>
  </nav>' . "\n\n";
$publishedIndexAdminLinks = null;
$title = 'Published';
$use = 'published';
//
// Programs
//
require $includesPath . '/editor/common.php';
require $includesPath . '/editor/editor.php';
?>
