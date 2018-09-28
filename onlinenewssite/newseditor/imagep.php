<?php
/**
 * Pulls the selected image from the published database
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2018 09 28
 * @link      https://hardcoverwebdesign.com/
 * @link      https://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
if (isset($_GET['i'])) {
    $idArticle = base64_decode(str_rot13(substr($_GET['i'], 0, -1)));
    $imageSize = substr($_GET['i'], -1);
    if (is_numeric($idArticle) and (strval($imageSize) === strval('h') or strval($imageSize) === strval('t'))) {
        $image = $imageSize == 'h' ? 'hdImage' : 'thumbnailImage';
        include 'z/system/configuration.php';
        include $includesPath . '/common.php';
        $dbh = new PDO($dbPublished);
        $stmt = $dbh->prepare('SELECT ' . $image . ' FROM articles WHERE idArticle=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idArticle]);
        $row = $stmt->fetch();
        $dbh = null;
        if ($row) {
            header('Content-Type: image/jpeg');
            echo $row[$image];
        }
    }
}
?>
