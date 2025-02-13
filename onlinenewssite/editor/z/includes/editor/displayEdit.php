<?php
/**
 * Summary of articles in edit
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2025 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 * @version:  2025 02 03
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
$html.= "\n" . '    <form action="' . $uri . 'edit.php" method="post">' . "\n";
$html.= '      <p> <input type="hidden" name="idArticle" value="' . $idArticle . '"><input type="submit" class="button" value="Delete" name="delete"> <input type="submit" class="button" value="Edit" name="edit"> <input type="submit" class="button" value="Publish" name="publish"></p>' . "\n";
$html.= '    </form>' . "\n";
?>
