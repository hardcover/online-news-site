<?php
/**
 * The editing page for currently published articles
 *
 * PHP version 5
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2013-2015 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *.@license   http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version   GIT: 2015-07-21
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
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