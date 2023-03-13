<?php
/**
 * Synchronizes the remote and local databases
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2021 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2023 03 13
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
//
// Variables
//
$remotes = [];
$dbh = new PDO($dbRemote);
$stmt = $dbh->query('SELECT remote FROM remotes');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    $remotes[] = $row['remote'];
}
$dbh = null;
//
// Loop through each remote location
//
foreach ($remotes as $remote) {
    //
    // Delete remote requests for early removal
    //
    $remoteClassifieds = [];
    $request = [];
    $response = [];
    $request['task'] = 'classifiedsEarlyRemoval';
    $response = soa($remote . 'z/', $request);
    if ($response['result'] === 'success') {
        $remoteClassifieds = json_decode($response['remoteClassifieds'], true);
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->prepare('DELETE FROM ads WHERE idAd=?');
        foreach ($remoteClassifieds as $idAd) {
            $stmt->execute([$idAd]);
        }
        $dbh = null;
    }
    //
    // Determine the missing and extra classifieds
    //
    $remoteClassifieds = [];
    $request = [];
    $response = [];
    $request['task'] = 'classifiedsSync';
    $response = soa($remote . 'z/', $request);
    if ($response['result'] === 'success') {
        $remoteClassifieds = json_decode($response['remoteClassifieds'], true);
    }
    if ($remoteClassifieds === 'null' or $remoteClassifieds === null) {
        $remoteClassifieds = [];
    }
    $classifieds = [];
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->query('SELECT idAd FROM ads WHERE review IS NOT NULL');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        $classifieds[] = $row['idAd'];
    }
    $dbh = null;
    $missingClassifieds = array_diff($classifieds, $remoteClassifieds);
    $extraClassifieds = array_diff($remoteClassifieds, $classifieds);
    //
    // Upload missing classifieds
    //
    foreach ($missingClassifieds as $idAd) {
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->prepare('SELECT email, title, description, categoryId, review, startDate, duration, invoice, photos FROM ads WHERE idAd=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idAd]);
        $row = $stmt->fetch();
        $dbh = null;
        if ($row) {
            extract($row);
            $photos = json_decode($photos, true);
            $photos = array_map('strval', $photos);
            $startTime = strtotime($startDate);
            $review = date("Y-m-d", $startTime + ($duration * 7 * 86400));
            $request = [];
            $response = [];
            $request['task'] = 'classifiedsUpdateInsert1';
            $request['idAd'] = $idAd;
            $request['email'] = $email;
            $request['title'] = $title;
            $request['description'] = $description;
            $request['categoryId'] = $categoryId;
            $request['review'] = $review;
            $request['startDate'] = $startDate;
            $request['duration'] = $duration;
            $request['invoice'] = $invoice;
            $request['photos'] = $row['photos'];
            $response = soa($remote . 'z/', $request);
            if ($response['result'] = 'success') {
                $i = 0;
                foreach ($photos as $photo) {
                    $i++;
                    if ($photo === '1') {
                        $request = [];
                        $response = [];
                        $request['task'] = 'classifiedsUpdateInsert2';
                        $request['idAd'] = $idAd;
                        $request['photoNumber'] = $i;
                        $dbh = new PDO($dbClassifieds);
                        $stmt = $dbh->prepare('SELECT photo' . $i . ' FROM ads WHERE idAd=?');
                        $stmt->setFetchMode(PDO::FETCH_NUM);
                        $stmt->execute([$idAd]);
                        $row = $stmt->fetch();
                        $dbh = null;
                        $request['photo'] = $row['0'];
                        $response = soa($remote . 'z/', $request);
                    }
                }
            }
        }
    }
    //
    // Delete extra classifieds
    //
    foreach ($extraClassifieds as $idAd) {
        $request = [];
        $response = [];
        $request['task'] = 'classifiedsDelete';
        $request['idAd'] = $idAd;
        $response = soa($remote . 'z/', $request);
    }
}
?>
