<?php
/**
 * Moves an article from the specified from database to the specified to database
 *
 * PHP version 5
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2013-2015 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *.@license   http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version   GIT: 2015-07-21
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
//
// Variables
//
if ($dbFrom == $dbEdit) {
    $dbTo = $dbPublished;
    $dbFrom2 = $dbEdit2;
    $dbTo2 = $dbPublished2;
    $archive = null;
} elseif ($dbFrom == $dbPublished) {
    $dbTo = $dbArchive;
    $dbFrom2 = $dbPublished2;
    $dbTo2 = $dbArchive2;
    $archive = 'archive';
} elseif ($dbFrom == $dbArchive) {
    $dbTo = $dbEdit;
    $dbFrom2 = $dbArchive2;
    $dbTo2 = $dbEdit2;
    $archive = null;
} else {
    exit;
}
//
// Move the non-image information
//
$dbh = new PDO($dbFrom);
$stmt = $dbh->prepare('SELECT publicationDate, endDate, idSection, sortOrderArticle, byline, headline, standfirst, text, summary, photoCredit, photoCaption FROM articles WHERE idArticle=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array($idArticle));
$row = $stmt->fetch();
$dbh = null;
extract($row);
$dbh = new PDO($dbTo);
$stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE idArticle=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array($idArticle));
$row = $stmt->fetch();
if (empty($row)) {
    $stmt = $dbh->prepare('INSERT INTO articles (idArticle) VALUES (?)');
    $stmt->execute(array($idArticle));
}
$stmt = $dbh->prepare('UPDATE articles SET publicationDate=?, endDate=?, idSection=?, byline=?, headline=?, standfirst=?, text=?, summary=?, photoCredit=?, photoCaption=? WHERE idArticle=?');
$stmt->execute(array($publicationDate, $endDate, $idSection, $byline, $headline, $standfirst, $text, $summary, $photoCredit, $photoCaption, $idArticle));
$dbh = null;
if ($dbFrom != $dbArchive) {
    $request = null;
    $response = null;
    $request['task'] = 'updateInsert1';
    $request['archive'] = $archive;
    $request['idArticle'] = $idArticle;
    $request['publicationDate'] = $publicationDate;
    $request['endDate'] = $endDate;
    $request['idSection'] = $idSection;
    $request['sortOrderArticle'] = $sortOrderArticle;
    $request['byline'] = $byline;
    $request['headline'] = $headline;
    $request['standfirst'] = $standfirst;
    $request['text'] = $text;
    $request['summary'] = $summary;
    $request['photoCredit'] = $photoCredit;
    $request['photoCaption'] = $photoCaption;
    foreach ($remotes as $remote) {
        $response = soa($remote . 'z/', $request);
    }
}
//
// Check for an image
//
$dbh = new PDO($dbFrom);
$stmt = $dbh->prepare('SELECT thumbnailImageWidth FROM articles WHERE idArticle=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array($idArticle));
$row = $stmt->fetch();
$dbh = null;
if ($row['thumbnailImageWidth'] != null) {
    //
    // Move the thumbnail and other small items
    //
    $request = null;
    $dbh = new PDO($dbFrom);
    $stmt = $dbh->prepare('SELECT originalImageWidth, originalImageHeight, thumbnailImage, thumbnailImageWidth, thumbnailImageHeight, hdImageWidth, hdImageHeight FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idArticle));
    $row = $stmt->fetch();
    $dbh = null;
    $dbh = new PDO($dbTo);
    $stmt = $dbh->prepare('UPDATE articles SET originalImageWidth=?, originalImageHeight=?, thumbnailImage=?, thumbnailImageWidth=?, thumbnailImageHeight=?, hdImageWidth=?, hdImageHeight=? WHERE idArticle=?');
    $stmt->execute(array($row['originalImageWidth'], $row['originalImageHeight'], $row['thumbnailImage'], $row['thumbnailImageWidth'], $row['thumbnailImageHeight'], $row['hdImageWidth'], $row['hdImageHeight'], $idArticle));
    $dbh = null;
    if ($dbFrom != $dbArchive) {
        $request = null;
        $response = null;
        $request['task'] = 'updateInsert2';
        $request['archive'] = $archive;
        $request['thumbnailImage'] = $row['thumbnailImage'];
        $request['thumbnailImageWidth'] = $row['thumbnailImageWidth'];
        $request['thumbnailImageHeight'] = $row['thumbnailImageHeight'];
        $request['hdImageWidth'] = $row['hdImageWidth'];
        $request['hdImageHeight'] = $row['hdImageHeight'];
        $request['idArticle'] = $idArticle;
        foreach ($remotes as $remote) {
            $response = soa($remote . 'z/', $request);
        }
    }
    //
    // Move the HD image
    //
    $request = null;
    $dbh = new PDO($dbFrom);
    $stmt = $dbh->prepare('SELECT hdImage FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idArticle));
    $row = $stmt->fetch();
    $dbh = null;
    $dbh = new PDO($dbTo);
    $stmt = $dbh->prepare('UPDATE articles SET hdImage=? WHERE idArticle=?');
    $stmt->execute(array($row['hdImage'], $idArticle));
    $dbh = null;
    if ($dbFrom != $dbArchive) {
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
    $dbhF = new PDO($dbFrom2);
    $stmt = $dbhF->prepare('SELECT image, photoCredit, photoCaption, time FROM imageSecondary WHERE idArticle=? ORDER BY time');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idArticle));
    foreach ($stmt as $row) {
        $dbh = new PDO($dbTo2);
        $stmt = $dbh->prepare('INSERT INTO imageSecondary (idArticle, image, photoCredit, photoCaption, time) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute(array($idArticle, $row['image'], $row['photoCredit'], $row['photoCaption'], $row['time']));
        $dbh = null;
        if ($dbFrom != $dbArchive) {
            $request = null;
            $response = null;
            $request['task'] = 'updateInsert4';
            $request['archive'] = $archive;
            $request['idArticle'] = $idArticle;
            $request['image'] = $row['image'];
            $request['photoCredit'] = $row['photoCredit'];
            $request['photoCaption'] = $row['photoCaption'];
            foreach ($remotes as $remote) {
                $response = soa($remote . 'z/', $request);
            }
        }
    }
    $dbhF = null;
}
//
// Verify move on the main system before deleting the From article
//
$dbh = new PDO($dbFrom);
$stmt = $dbh->prepare('SELECT publicationDate, endDate, idSection, byline, headline, text FROM articles WHERE idArticle=?');
$stmt->setFetchMode(PDO::FETCH_NUM);
$stmt->execute(array($idArticle));
$row = $stmt->fetch();
$dbh = null;
$from = $row;
$dbh = new PDO($dbTo);
$stmt = $dbh->prepare('SELECT publicationDate, endDate, idSection, byline, headline, text FROM articles WHERE idArticle=?');
$stmt->setFetchMode(PDO::FETCH_NUM);
$stmt->execute(array($idArticle));
$row = $stmt->fetch();
$dbh = null;
if ($from == $row) {
    $dbh = new PDO($dbFrom2);
    $stmt = $dbh->prepare('SELECT count(*) FROM imageSecondary WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_NUM);
    $stmt->execute(array($idArticle));
    $row = $stmt->fetch();
    $dbh = null;
    $from = $row;
    $dbh = new PDO($dbTo2);
    $stmt = $dbh->prepare('SELECT count(*) FROM imageSecondary WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_NUM);
    $stmt->execute(array($idArticle));
    $row = $stmt->fetch();
    $dbh = null;
    if ($from == $row) {
        //
        // Delete the From article
        //
        $dbh = new PDO($dbFrom);
        $stmt = $dbh->prepare('DELETE FROM articles WHERE idArticle=?');
        $stmt->execute(array($idArticle));
        $dbh = null;
        $dbh = new PDO($dbFrom2);
        $stmt = $dbh->prepare('DELETE FROM imageSecondary WHERE idArticle=?');
        $stmt->execute(array($idArticle));
        $dbh = null;
    }
}
?>