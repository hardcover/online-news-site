<?php
/**
 * Summary of published articles
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2018 05 13
 * @link      https://hardcoverwebdesign.com/
 * @link      https://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
$date = $publicationDate > $today ? 'Pub: ' . $publicationDate : null;
$html.= "\n" . '  <form class="wait" action="' . $uri . 'published.php" method="post">' . "\n";
$html.= '    <p><input type="hidden" name="publish" value="Publish"><input type="hidden" name="idArticle" value="' . $idArticle . '"> <input type="submit" class="button" value="Delete" name="delete" /> <input type="submit" class="button" value="Edit" name="edit" /> <input type="submit" class="button" value="Archive" name="archive" /> <input type="submit" class="button" value="&uarr;" name="up" /> <input type="submit" class="button" value="&darr;" name="down" /> ' . $date . '</p>' . "\n";
$html.= "  </form>\n";
?>
