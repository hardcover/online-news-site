<?php
/**
 * Pulls the selected image from the archive database
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2021 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2021 3 15
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
if (isset($_GET['i'])) {
    $idArticle = base64_decode(str_rot13(substr($_GET['i'], 0, -1)));
    $imageSize = substr($_GET['i'], -1);
    if (is_numeric($idArticle) and (strval($imageSize) === strval('h') or strval($imageSize) === strval('t'))) {
        $image = $imageSize === 'h' ? 'hdImage' : 'thumbnailImage';
        include 'z/system/configuration.php';
        include $includesPath . '/common.php';
        //
        // Step through archive2 databases until the image is found
        //
        $dbNumber = 0;
        while ($dbNumber !== -1) {
            $db = str_replace('archive', 'archive-' . $dbNumber, $dbArchive);
            if ($dbNumber === 0
                or file_exists(str_replace('sqlite:', '', $db))
            ) {
                if ($dbNumber === 0) {
                    $database = $dbArchive;
                } else {
                    $database = $db;
                }
                $dbNumber++;
            } else {
                $dbNumber = -1;
                $dbh = null;
            }
            //
            // Display the image
            //
            if (!empty($database)) {
                $dbh = new PDO($database);
                $stmt = $dbh->prepare('SELECT ' . $image . ' FROM articles WHERE idArticle=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$idArticle]);
                $row = $stmt->fetch();
                $dbh = null;
                if ($row) {
                    $dbNumber = -1;
                    header('Content-Type: image/jpeg');
                    echo $row[$image];
                }
            }
        }
    }
}
?>
