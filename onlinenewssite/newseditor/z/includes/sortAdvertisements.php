<?php
/**
 * Sets the sort order for published advertisements
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2018 09 28
 * @link      https://hardcoverwebdesign.com/
 * @link      https://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
//
// Sort the ads within their publication date, ignore others
//
$count = null;
$dbh = new PDO($dbAdvertising);
$stmt = $dbh->prepare('SELECT idAd, sortOrderAd FROM advertisements WHERE (? >= startDateAd AND ? <= endDateAd) ORDER BY sortOrderAd, sortPriority');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$today, $today]);
foreach ($stmt as $row) {
    if ($row) {
        extract($row);
        if (isset($sortOrderAd)) {
            $count++;
            $stmt = $dbh->prepare('UPDATE advertisements SET sortOrderAd=? WHERE idAd=?');
            $stmt->execute([$count, $idAd]);
        }
    }
}
//
// Restore presort settings
//
$stmt = $dbh->prepare('SELECT idAd FROM advertisements WHERE (startDateAd >= ? AND ? <= endDateAd)');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$today, $today]);
foreach ($stmt as $row) {
    if ($row) {
        extract($row);
        $stmt = $dbh->prepare('UPDATE advertisements SET sortPriority=?');
        $stmt->execute([2]);
    }
}
$dbh = null;
?>