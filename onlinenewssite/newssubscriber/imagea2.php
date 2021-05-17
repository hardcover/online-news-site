<?php
/**
 * Pulls the selected image from the published database
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2021 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2021 5 17
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
if (isset($_GET['i'])) {
    $idPhoto = base64_decode(str_rot13(substr($_GET['i'], 0, -1)));
    $imageSize = substr($_GET['i'], -1);
    if (is_numeric($idPhoto) and (strval($imageSize) === strval('h') or strval($imageSize) === strval('t'))) {
        include 'z/system/configuration.php';
        include $includesPath . '/common.php';
        //
        // Step through archive2 databases until the image is found
        //
        $dbNumber = 0;
        while ($dbNumber !== -1) {
            $db = str_replace('archive2', 'archive2-' . $dbNumber, $dbArchive2);
            if ($dbNumber === 0
                or file_exists(str_replace('sqlite:', '', $db))
            ) {
                if ($dbNumber === 0) {
                    $database = $dbArchive2;
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
                $stmt = $dbh->prepare('SELECT image FROM imageSecondary WHERE idPhoto=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$idPhoto]);
                $row = $stmt->fetch();
                $dbh = null;
                if ($row) {
                    $dbNumber = -1;
                    header('Content-Type: image/jpeg');
                    echo $row['image'];
                }
            }
        }
    }
}
?>
