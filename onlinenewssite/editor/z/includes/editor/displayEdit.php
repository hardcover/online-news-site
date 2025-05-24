<?php
/**
 * Summary of articles in edit
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
$html.= "\n" . '    <form action="' . $uri . 'edit.php" method="post">' . "\n";
$html.= '      <p> <input type="hidden" name="idArticle" value="' . $idArticle . '"><input type="submit" class="button" value="Delete" name="delete"> <input type="submit" class="button" value="Edit" name="edit"> <input type="submit" class="button" value="Publish" name="publish"></p>' . "\n";
$html.= '    </form>' . "\n";
?>
