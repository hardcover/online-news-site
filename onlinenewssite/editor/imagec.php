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
    $photo = substr($_GET['i'], -1);
    if (is_numeric($idAd) and is_numeric($photo)) {
        include 'z/system/configuration.php';
        $includesPath = '../' . $includesPath;
        include $includesPath . '/editor/common.php';
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->prepare('SELECT photo' . $photo . ' FROM ads WHERE idAd=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idAd]);
        $row = $stmt->fetch();
        $dbh = null;
        if ($row) {
            header('Content-Type: image/jpeg');
            echo $row['photo' . $photo];
        }
    }
}
?>
