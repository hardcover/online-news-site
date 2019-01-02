<?php
/**
 * Summary of articles in edit
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
$html.= "\n" . '  <form class="wait" action="' . $uri . 'edit.php" method="post">' . "\n";
$html.= '    <p> <input type="hidden" name="idArticle" value="' . $idArticle . '"><input type="submit" class="button" value="Delete" name="delete" /> <input type="submit" class="button" value="Edit" name="edit" /> <input type="submit" class="button" value="Publish" name="publish" /></p>' . "\n";
$html.= "  </form>\n";
?>