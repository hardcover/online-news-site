<?php
/**
 * Pulls the selected image from the classifieds database
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
