<?php
/**
 * Summary of published articles
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
$date = $publicationDate > $today ? 'Pub: ' . $publicationDate : null;
$html.= "\n" . '    <form action="' . $uri . 'published.php" method="post">' . "\n";
$html.= '      <p><input type="hidden" name="publish" value="Publish"><input type="hidden" name="idArticle" value="' . $idArticle . '"> <input type="submit" class="button" value="Delete" name="delete"> <input type="submit" class="button" value="Edit" name="edit"> <input type="submit" class="button" value="Archive" name="archive"> <input type="submit" class="button" value="&uarr;" name="up"> <input type="submit" class="button" value="&darr;" name="down"> ' . $date . '</p>' . "\n";
$html.= '    </form>' . "\n";
?>
