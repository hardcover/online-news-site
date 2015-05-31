<?php
/**
 * Summary of published articles
 *
 * PHP version 5
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2013-2015 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *.@license   http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version   GIT: 2015-05-31
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
$date = $publicationDate > $today ? 'Pub: ' . $publicationDate : null;
$html.= "\n" . '  <form class="wait" action="' . $uri . 'published.php" method="post">' . "\n";
$html.= '    <p> <input type="hidden" name="publish" value="Publish"><input type="hidden" name="idArticle" value="' . $idArticle . '"><input type="submit" value="Delete" name="delete" class="left" /><input type="submit" value="Edit" name="edit" class="middle" /><input type="submit" value="Archive" name="archive" class="middle" /><input type="submit" value="&uarr;" name="up" class="middle" /><input type="submit" value="&darr;" name="down" class="right" /> ' . $date . '</p>' . "\n";
$html.= "  </form>\n";
?>
