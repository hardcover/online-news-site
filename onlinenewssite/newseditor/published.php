<?php
/**
 * The editing page for currently published articles
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2019 12 7
 * @link      https://hardcoverwebdesign.com/
 * @link      https://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
session_start();
require 'z/system/configuration.php';
require $includesPath . '/authorization.php';
require $includesPath . '/parsedown-master/Parsedown.php';
//
// Variables
//
$editorView = 1;
$links = null;
$menu = "\n" . '  <h4 class="m"><a class="m" href="edit.php">&nbsp;Edit&nbsp;</a><a class="s" href="published.php">&nbsp;Published&nbsp;</a><a class="m" href="preview.php">&nbsp;Preview&nbsp;</a><a class="m" href="archive.php">&nbsp;Archives&nbsp;</a></h4>' . "\n\n";
$publishedIndexAdminLinks = null;
$title = 'Published';
$use = 'published';
//
// Programs
//
require $includesPath . '/common.php';
require $includesPath . '/editor.php';
?>