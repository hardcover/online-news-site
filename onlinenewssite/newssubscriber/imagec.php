<?php
/**
 * Pulls the selected image from the classifieds database
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2018 12 21
 * @link      https://hardcoverwebdesign.com/
 * @link      https://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
if (isset($_GET['i'])) {
    $idAd = base64_decode(str_rot13(substr($_GET['i'], 0, -1)));
    $imageNumber = substr($_GET['i'], -1);
    if (is_numeric($idAd) and is_numeric($imageNumber)) {
        include 'z/system/configuration.php';
        include $includesPath . '/common.php';
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
