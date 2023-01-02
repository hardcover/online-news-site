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
 * @version:  2023 01 02
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
    // Determine the missing and extra ads
    //
    $request = [];
    $response = [];
    $request['task'] = 'adSync';
    $response = soa($remote . 'z/', $request);
    $remoteAds = json_decode($response['remoteAds'], true);
    if ($remoteAds === 'null' or $remoteAds === null) {
        $remoteAds = [];
    }
    $ads = [];
    $dbh = new PDO($dbAdvertising);
    $stmt = $dbh->query('SELECT idAd FROM advertisements');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        $ads[] = $row['idAd'];
    }
    $dbh = null;
    $missingAds = array_diff($ads, $remoteAds);
    $extraAds = array_diff($remoteAds, $ads);
    //
    // Upload missing ads to the remote sites
    //
    if (count($missingAds) > 0) {
        foreach ($missingAds as $idAd) {
            $dbh = new PDO($dbAdvertising);
            $stmt = $dbh->prepare('SELECT startDateAd, endDateAd, sortOrderAd, link, linkAlt, image FROM advertisements WHERE idAd=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$idAd]);
            $row = $stmt->fetch();
            $dbh = null;
            extract($row);
            $request = [];
            $response = [];
            $request['task'] = 'adInsert';
            $request['idAd'] = $idAd;
            $request['startDateAd'] = $startDateAd;
            $request['endDateAd'] = $endDateAd;
            $request['sortOrderAd'] = $sortOrderAd;
            $request['link'] = $link;
            $request['linkAlt'] = $linkAlt;
            $request['image'] = $image;
            $response = soa($remote . 'z/', $request);
        }
    }
    //
    // When extra remote ads were found above, check again and delete the extra ads
    //
    if (count($extraAds) > 0) {
        $request = [];
        $response = [];
        $request['task'] = 'adSync';
        $response = soa($remote . 'z/', $request);
        $remoteAds = json_decode($response['remoteAds'], true);
        if ($remoteAds === 'null' or $remoteAds === null) {
            $remoteAds = [];
        }
        $dbh = new PDO($dbAdvertising);
        $stmt = $dbh->query('SELECT idAd FROM advertisements');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            $ads[] = $row['idAd'];
        }
        $dbh = null;
        $extraAds = array_diff($remoteAds, $ads);
        //
        // Delete extra remote articles
        //
        $request = [];
        $response = [];
        $request['task'] = 'adDelete';
        foreach ($extraAds as $idAd) {
            $request['idAd'] = $idAd;
            $response = soa($remote . 'z/', $request);
        }
    }
    //
    // Upload the current sort order and maximum number of ads
    //
    $request = [];
    $response = [];
    $sortOrder = null;
    $request['task'] = 'adOrder';
    $dbh = new PDO($dbAdvertising);
    $stmt = $dbh->prepare('SELECT maxAds FROM maxAd WHERE idMaxAds=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([1]);
    $row = $stmt->fetch();
    if ($row) {
        $request['maxAds'] = $row['maxAds'];
    }
    $stmt = $dbh->query('SELECT sortOrderAd, idAd FROM advertisements');
    $stmt->setFetchMode(PDO::FETCH_NUM);
    foreach ($stmt as $row) {
        $sortOrder[] = $row;
    }
    $dbh = null;
    $sortOrder = json_encode($sortOrder);
    $request['sortOrder'] = $sortOrder;
    $response = soa($remote . 'z/', $request);
}
?>
