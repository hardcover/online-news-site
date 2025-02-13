<?php
/**
 * Pulls the selected image from the classifieds database
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
    $idAd = base64_decode(str_rot13(substr($_GET['i'], 0, -1)));
    $imageNumber = substr($_GET['i'], -1);
    if (is_numeric($idAd) and is_numeric($imageNumber)) {
        include 'editor/z/system/configuration.php';
        include $includesPath . '/editor/common.php';
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->prepare('SELECT photo' . $imageNumber . ' FROM ads WHERE idAd=?');
        $stmt->setFetchMode(PDO::FETCH_NUM);
        $stmt->execute([$idAd]);
        $row = $stmt->fetch();
        $dbh = null;
        header('Content-Type: image/jpeg');
        echo $row['0'];
    }
}
?>
