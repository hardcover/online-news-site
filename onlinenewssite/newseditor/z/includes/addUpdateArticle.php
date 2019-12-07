<?php
/**
 * Adds or updates a remote published or archived article from the same database on the main system
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2019 12 7
 * @link      https://hardcoverwebdesign.com/
 * @link      https://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
//
// Copy the non-image information
//
$dbh = new PDO($database);
$stmt = $dbh->prepare('SELECT publicationDate, publicationTime, endDate, survey, genre, keywords, idSection, sortOrderArticle, byline, headline, standfirst, text, summary, evolve, expand, extend, photoName, photoCredit, photoCaption FROM articles WHERE idArticle=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$idArticle]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    extract($row);
    $request = null;
    $response = null;
    $request['task'] = 'updateInsert1';
    $request['archive'] = $archive;
    $request['idArticle'] = $idArticle;
    $request['publicationDate'] = $publicationDate;
    $request['publicationTime'] = $publicationTime;
    $request['endDate'] = $endDate;
    $request['survey'] = $survey;
    $request['genre'] = $genre;
    $request['keywords'] = $survey;
    $request['idSection'] = $idSection;
    $request['sortOrderArticle'] = $sortOrderArticle;
    $request['byline'] = $byline;
    $request['headline'] = $headline;
    $request['standfirst'] = $standfirst;
    $request['text'] = $text;
    $request['summary'] = $summary;
    $request['evolve'] = $evolve;
    $request['expand'] = $expand;
    $request['extend'] = $extend;
    $request['photoName'] = $photoName;
    $request['photoCredit'] = $photoCredit;
    $request['photoCaption'] = $photoCaption;
    foreach ($remotes as $remote) {
        $response = soa($remote . 'z/', $request);
    }
    //
    // Check for an image
    //
    $dbh = new PDO($database);
    $stmt = $dbh->prepare('SELECT thumbnailImageWidth FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idArticle]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row['thumbnailImageWidth'] != '') {
        //
        // Copy the thumbnail and other small items
        //
        $request = null;
        $dbh = new PDO($database);
        $stmt = $dbh->prepare('SELECT thumbnailImage, thumbnailImageWidth, thumbnailImageHeight, hdImageWidth, hdImageHeight FROM articles WHERE idArticle=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idArticle]);
        $row = $stmt->fetch();
        $dbh = null;
        if ($response['result'] == 'success') {
            $request = null;
            $response = null;
            $request['task'] = 'updateInsert2';
            $request['archive'] = $archive;
            $request['idArticle'] = $idArticle;
            $request['thumbnailImage'] = $row['thumbnailImage'];
            $request['thumbnailImageWidth'] = $row['thumbnailImageWidth'];
            $request['thumbnailImageHeight'] = $row['thumbnailImageHeight'];
            $request['hdImageWidth'] = $row['hdImageWidth'];
            $request['hdImageHeight'] = $row['hdImageHeight'];
            foreach ($remotes as $remote) {
                $response = soa($remote . 'z/', $request);
            }
        }
        //
        // Upload the published HD image
        //
        $request = null;
        $dbh = new PDO($database);
        $stmt = $dbh->prepare('SELECT hdImage FROM articles WHERE idArticle=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idArticle]);
        $row = $stmt->fetch();
        $dbh = null;
        if ($response['result'] == 'success') {
            $request = null;
            $response = null;
            $request['task'] = 'updateInsert3';
            $request['archive'] = $archive;
            $request['idArticle'] = $idArticle;
            $request['hdImage'] = $row['hdImage'];
            foreach ($remotes as $remote) {
                $response = soa($remote . 'z/', $request);
            }
        }
    }
    //
    // Move the secondary images
    //
    $request = null;
    $response = null;
    if (is_null($archive)) {
        $request['task'] = 'publishedSync2';
    } else {
        $request['task'] = 'archiveSync2';
    }
    $request['idArticle'] = $idArticle;
    $dbh = new PDO($database2);
    $stmt = $dbh->prepare('SELECT count(*) FROM imageSecondary WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idArticle]);
    $row = $stmt->fetch();
    $dbh = null;
    $imagesMain = $row['count(*)'];
    foreach ($remotes as $remote) {
        $response = soa($remote . 'z/', $request);
        if ($imagesMain != $response['remotePhotos']) {
            $dbh = new PDO($database2);
            $stmt = $dbh->prepare('SELECT idPhoto, image, photoName, photoCredit, photoCaption, time FROM imageSecondary WHERE idArticle=? ORDER BY time');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$idArticle]);
            foreach ($stmt as $row) {
                $request = null;
                $response = null;
                $request['task'] = 'updateInsert4';
                $request['archive'] = $archive;
                $request['idPhoto'] = $row['idPhoto'];
                $request['idArticle'] = $idArticle;
                $request['image'] = $row['image'];
                $request['photoName'] = $row['photoName'];
                $request['photoCredit'] = $row['photoCredit'];
                $request['photoCaption'] = $row['photoCaption'];
                foreach ($remotes as $remote) {
                    $response = soa($remote . 'z/', $request);
                }
            }
            $dbh = null;
        }
    }
}
?>
