<?php
/**
 * Pulls the selected image from the published database
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
    $idPhoto = base64_decode(str_rot13(substr($_GET['i'], 0, -1)));
    $imageSize = substr($_GET['i'], -1);
    if (is_numeric($idPhoto) and (strval($imageSize) === strval('h') or strval($imageSize) === strval('t'))) {
        include 'editor/z/system/configuration.php';
        include $includesPath . '/editor/common.php';
        $dbh = new PDO($dbPublished2);
        $stmt = $dbh->prepare('SELECT image FROM imageSecondary WHERE idPhoto=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idPhoto]);
        $row = $stmt->fetch();
        $dbh = null;
        if ($row) {
            header('Content-Type: image/jpeg');
            echo $row['image'];
        }
    }
}
?>
