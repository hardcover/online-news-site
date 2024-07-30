<?php
/**
 * Pulls the selected image from the edit database
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2024 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 * @version:  2024 07 30
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
if (isset($_GET['i'])) {
    $idPhoto = base64_decode(str_rot13(substr($_GET['i'], 0, -1)));
    $imageSize = substr($_GET['i'], -1);
    if (is_numeric($idPhoto) and (strval($imageSize) === strval('h') or strval($imageSize) === strval('t'))) {
        include 'z/system/configuration.php';
        $includesPath = '../' . $includesPath;
        include $includesPath . '/editor/common.php';
        $dbh = new PDO($dbEdit2);
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
