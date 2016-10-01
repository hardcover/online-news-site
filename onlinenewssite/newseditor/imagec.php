<?php
/**
 * Pulls the selected image from the classifieds database
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2016 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *.@license   http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2016-10-01
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
if (isset($_GET['i'])) {
    $idAd = base64_decode(str_rot13(substr($_GET['i'], 0, -1)));
    $photo = substr($_GET['i'], -1);
    if (is_numeric($idAd) and is_numeric($photo)) {
        include 'z/system/configuration.php';
        include $includesPath . '/common.php';
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->prepare('SELECT photo' . $photo . ' FROM ads WHERE idAd=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(array($idAd));
        $row = $stmt->fetch();
        $dbh = null;
        if ($row) {
            header('Content-Type: image/jpeg');
            echo $row['photo' . $photo];
        }
    }
}
?>
