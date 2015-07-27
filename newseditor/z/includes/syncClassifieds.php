<?php
/**
 * Synchronizes the remote and local databases
 *
 * PHP version 5
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2013-2015 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *.@license   http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version   GIT: 2015-07-26
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
//
// Variables
//
$remotes = array();
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
    $remoteClassifieds = array();
    $request = null;
    $response = null;
    $request['task'] = 'classifiedsEarlyRemoval';
    $response = soa($remote . 'z/', $request);
    if ($response['result'] == 'success') {
        $remoteClassifieds = json_decode($response['remoteClassifieds'], true);
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->prepare('DELETE FROM ads WHERE idAd=?');
        foreach ($remoteClassifieds as $idAd) {
            $stmt->execute(array($idAd));
        }
        $dbh = null;
    }
    //
    // Determine the missing and extra classifieds
    //
    $remoteClassifieds = array();
    $request = null;
    $response = null;
    $request['task'] = 'classifiedsSync';
    $response = soa($remote . 'z/', $request);
    if ($response['result'] == 'success') {
        $remoteClassifieds = json_decode($response['remoteClassifieds'], true);
    }
    if ($remoteClassifieds == 'null' or $remoteClassifieds == null) {
        $remoteClassifieds = array();
    }
    $classifieds = array();
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
        $stmt = $dbh->prepare('SELECT email, title, description, categoryId, review, startDate, duration, photos FROM ads WHERE idAd=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(array($idAd));
        $row = $stmt->fetch();
        $dbh = null;
        if ($row) {
            extract($row);
            $photos = json_decode($photos, true);
            $startTime = strtotime($startDate);
            $review = date("Y-m-d", $startTime + ($duration * 7 * 86400));
            $request = null;
            $response = null;
            $request['task'] = 'classifiedsUpdateInsert1';
            $request['idAd'] = $idAd;
            $request['email'] = $email;
            $request['title'] = $title;
            $request['description'] = $description;
            $request['categoryId'] = $categoryId;
            $request['review'] = $review;
            $request['startDate'] = $startDate;
            $request['duration'] = $duration;
            $request['photos'] = $row['photos'];
            $response = soa($remote . 'z/', $request);
            if ($response['result'] = 'success') {
                $i = null;
                foreach ($photos as $photo) {
                    $i++;
                    if ($photo == 1) {
                        $request = null;
                        $response = null;
                        $request['task'] = 'classifiedsUpdateInsert2';
                        $request['idAd'] = $idAd;
                        $request['photoNumber'] = $i;
                        $dbh = new PDO($dbClassifieds);
                        $stmt = $dbh->prepare('SELECT photo' . $i . ' FROM ads WHERE idAd=?');
                        $stmt->setFetchMode(PDO::FETCH_NUM);
                        $stmt->execute(array($idAd));
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
        $request = null;
        $response = null;
        $request['task'] = 'classifiedsDelete';
        $request['idAd'] = $idAd;
        $response = soa($remote . 'z/', $request);
    }
}
?>
