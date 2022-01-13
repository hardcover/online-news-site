<?php
/**
 * Downloads the latest remote classifieds
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2021 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2022 01 12
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
    // Get the IDs of the new ads
    //
    $classifieds = [];
    $request = [];
    $response = [];
    $request['task'] = 'classifiedsSyncNew';
    $response = soa($remote . 'z/', $request);
    if ($response['result'] === 'success') {
        $classifieds = json_decode($response['remoteClassifieds'], true);
        if ($classifieds === 'null' or $classifieds === null) {
            $classifieds = [];
        }
    }
    //
    // Download new classifieds from remote sites
    //
    foreach ($classifieds as $classified) {
        $request = [];
        $response = [];
        $request['task'] = 'classifiedsNewDownload';
        $request['idAd'] = $classified;
        $response = soa($remote . 'z/', $request);
        if ($response['result'] === 'success' and isset($response['email'])) {
            extract($response);
            $dbh = new PDO($dbClassifieds);
            $stmt = $dbh->prepare('INSERT INTO ads (email, title, description, categoryId, photos) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$email, $title, $description, $categoryId, $photos]);
            $idAdMain = $dbh->lastInsertId();
            $dbh = null;
            //
            // Download the images, one at a time
            //
            $photos = json_decode($photos, true);
            $photos = array_map('strval', $photos);
            $request = [];
            $response = [];
            $request['task'] = 'classifiedsNewDownloadPhoto';
            $request['idAd'] = $classified;
            $i = 0;
            foreach ($photos as $photo) {
                $i++;
                if ($photo === '1') {
                    $request['photo'] = $i;
                    $response = soa($remote . 'z/', $request);
                    if ($response['result'] === 'success' and isset($response['photo'])) {
                        $dbh = new PDO($dbClassifieds);
                        $stmt = $dbh->prepare('UPDATE ads SET photo' . $i . '=? WHERE idAd=?');
                        $stmt->execute([$response['photo'], $idAdMain]);
                        $dbh = null;
                    }
                }
            }
            //
            // Delete the ad from the classifiedsNew database
            //
            $request = [];
            $response = [];
            $request['task'] = 'classifiedsNewCleanUp';
            $request['idAd'] = $classified;
            $response = soa($remote . 'z/', $request);
        }
        //
        // Determine the missing and extra classifieds
        //
        $request = [];
        $response = [];
        $request['task'] = 'classifiedsSync';
        $response = soa($remote . 'z/', $request);
        $remoteClassifieds = json_decode($response['remoteClassifieds'], true);
        if ($remoteClassifieds === 'null' or $remoteClassifieds === null) {
            $remoteClassifieds = [];
        }
        $classifieds = [];
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->query('SELECT idAd FROM ads');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            $classifieds[] = $row['idAd'];
        }
        $dbh = null;
        $missingClassifieds = array_diff($classifieds, $remoteClassifieds);
        $extraClassifieds = array_diff($remoteClassifieds, $classifieds);
        //
        // When extra remote classifieds were found above, check again and delete the extra classifieds
        //
        if (count($extraClassifieds) > 0) {
            $request = [];
            $response = [];
            $request['task'] = 'classifiedsSync';
            $response = soa($remote . 'z/', $request);
            $remoteClassifieds = json_decode($response['remoteClassifieds'], true);
            if ($remoteClassifieds === 'null' or $remoteClassifieds === null) {
                $remoteClassifieds = [];
            }
            $dbh = new PDO($dbClassifieds);
            $stmt = $dbh->query('SELECT idAd FROM ads');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            foreach ($stmt as $row) {
                $classifieds[] = $row['idAd'];
            }
            $dbh = null;
            $extraClassifieds = array_diff($remoteClassifieds, $classifieds);
            //
            // Delete extra remote classifieds
            //
            foreach ($extraClassifieds as $idAd) {
                $request = [];
                $response = [];
                $request['task'] = 'classifiedsDelete';
                $request['idAd'] = $idAd;
                $response = soa($remote . 'z/', $request);
            }
        }
    }
}
?>
