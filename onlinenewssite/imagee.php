<?php
/**
 * Pulls the selected image from the edit database
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
if (isset($_GET['i'])) {
    $idArticle = base64_decode(str_rot13(substr($_GET['i'], 0, -1)));
    $imageSize = substr($_GET['i'], -1);
    if (is_numeric($idArticle) and (strval($imageSize) === strval('h') or strval($imageSize) === strval('t'))) {
        $image = $imageSize === 'h' ? 'hdImage' : 'thumbnailImage';
        include 'editor/z/system/configuration.php';
        include $includesPath . '/editor/common.php';
        $dbh = new PDO($dbEdit);
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
