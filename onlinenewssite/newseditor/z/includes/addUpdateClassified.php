<?php
/**
 * Adds or updates a published classified ad at the remote sites
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
// Add or update the remote sites
//
foreach ($remotes as $remote) {
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('SELECT email, review, photos FROM ads WHERE idAd=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idAdPost]);
    $row = $stmt->fetch();
    $dbh = null;
    $photos = json_decode($row['photos'], true);
    $request = null;
    $response = null;
    $request['task'] = 'classifiedsUpdateInsert1';
    $request['email'] = $row['email'];
    $request['idAd'] = $idAdPost;
    $request['title'] = $titlePost;
    $request['description'] = $descriptionPost;
    $request['categoryId'] = $categoryIdPost;
    $request['review'] = $row['review'];
    $request['startDate'] = $startDatePost;
    $request['duration'] = '1';
    $request['photos'] = $row['photos'];
    $response = soa($remote . 'z/', $request);
    if ($response['result'] === 'success') {
        //
        // Add, update or set to null the photos
        //
        $i = null;
        foreach ($photos as $photo) {
            $i++;
            if ($photo === 1) {
                $request = null;
                $response = null;
                $request['task'] = 'classifiedsUpdateInsert2';
                $request['idAd'] = $idAdPost;
                $request['photoNumber'] = $i;
                $dbh = new PDO($dbClassifieds);
                $stmt = $dbh->prepare('SELECT photo' . $i . ' FROM ads WHERE idAd=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$idAdPost]);
                $row = $stmt->fetch();
                $dbh = null;
                $request['photo'] = $row['photo' . $i];
                $response = soa($remote . 'z/', $request);
            }
        }
    }
}
?>