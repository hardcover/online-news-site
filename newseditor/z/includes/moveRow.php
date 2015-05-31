<?php
/**
 * Moves a row from the specified from article database to the specified to article database
 *
 * PHP version 5
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2013-2015 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *.@license   http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version   GIT: 2015-05-31
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
//
// Move the non-image information
//
$dbh = new PDO($dbFrom);
$stmt = $dbh->prepare('SELECT publicationDate, endDate, idSection, sortOrderArticle, byline, headline, standfirst, text, summary, photoCredit, photoCaption FROM articles WHERE idArticle=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array($idArticleMove));
$row = $stmt->fetch();
$dbh = null;
extract($row);
$dbh = new PDO($dbTo);
$stmt = $dbh->prepare('INSERT INTO articles (publicationDate, endDate, idSection, byline, headline, standfirst, text, summary, photoCredit, photoCaption) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute(array($publicationDate, $endDate, $idSection, $byline, $headline, $standfirst, $text, $summary, $photoCredit, $photoCaption));
$idArticlePublished = $dbh->lastInsertId();
if ($dbTo == $dbArchive) {
    $stmt = $dbh->prepare('UPDATE articles SET idArticle=? WHERE rowid=?');
    $stmt->execute(array($idArticlePublished, $idArticlePublished));
}
$dbh = null;
if (isset($archive, $publish)) {
    $request['task'] = 'updateInsert1';
    $request['archive'] = $archive;
    $request['idArticle'] = $idArticlePublished;
    $request['publish'] = $publish;
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
    $dbhRemote = new PDO($dbRemote);
    $stmt = $dbhRemote->query('SELECT remote FROM remotes');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        $response = soa($row['remote'] . 'z/', $request);
    }
    $dbhRemote = null;
}
//
// Move images, if any, to the to database
//
$dbh = new PDO($dbFrom);
$stmt = $dbh->prepare('SELECT thumbnailImageWidth FROM articles WHERE idArticle=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array($idArticleMove));
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
    $stmt->execute(array($idArticleMove));
    $row = $stmt->fetch();
    $dbh = null;
    $dbh = new PDO($dbTo);
    $stmt = $dbh->prepare('UPDATE articles SET originalImageWidth=?, originalImageHeight=?, thumbnailImage=?, thumbnailImageWidth=?, thumbnailImageHeight=?, hdImageWidth=?, hdImageHeight=? WHERE idArticle=?');
    $stmt->execute(array($row['originalImageWidth'], $row['originalImageHeight'], $row['thumbnailImage'], $row['thumbnailImageWidth'], $row['thumbnailImageHeight'], $row['hdImageWidth'], $row['hdImageHeight'], $idArticlePublished));
    $dbh = null;
    if (isset($archive, $publish) and $dbRemote != '') {
        if ($response['result'] == 'success') {
            $response = null;
            $request['task'] = 'updateInsert2';
            $request['archive'] = $archive;
            $request['thumbnailImage'] = $row['thumbnailImage'];
            $request['thumbnailImageWidth'] = $row['thumbnailImageWidth'];
            $request['thumbnailImageHeight'] = $row['thumbnailImageHeight'];
            $request['hdImageWidth'] = $row['hdImageWidth'];
            $request['hdImageHeight'] = $row['hdImageHeight'];
            $request['idArticle'] = $idArticlePublished;
            $dbhRemote = new PDO($dbRemote);
            $stmt = $dbhRemote->query('SELECT remote FROM remotes');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            foreach ($stmt as $row) {
                $response = soa($row['remote'] . 'z/', $request);
            }
            $dbhRemote = null;
        }
    }
    //
    // Move the HD image
    //
    $request = null;
    $dbh = new PDO($dbFrom);
    $stmt = $dbh->prepare('SELECT hdImage FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idArticleMove));
    $row = $stmt->fetch();
    $dbh = null;
    $dbh = new PDO($dbTo);
    $stmt = $dbh->prepare('UPDATE articles SET hdImage=? WHERE idArticle=?');
    $stmt->execute(array($row['hdImage'], $idArticlePublished));
    $dbh = null;
    if (isset($archive, $published) and $dbRemote != '') {
        if ($response['result'] == 'success') {
            $response = null;
            $request['task'] = 'updateInsert3';
            $request['archive'] = $archive;
            $request['hdImage'] = $row['hdImage'];
            $request['idArticle'] = $idArticlePublished;
            $dbhRemote = new PDO($dbRemote);
            $stmt = $dbhRemote->query('SELECT remote FROM remotes');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            foreach ($stmt as $row) {
                $response = soa($row['remote'] . 'z/', $request);
            }
            $dbhRemote = null;
        }
    }
    //
    // Move the original image
    //
    $request = null;
    $dbh = new PDO($dbFrom);
    $stmt = $dbh->prepare('SELECT originalImage FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idArticleMove));
    $row = $stmt->fetch();
    $dbh = null;
    $dbh = new PDO($dbTo);
    $stmt = $dbh->prepare('UPDATE articles SET originalImage=? WHERE idArticle=?');
    $stmt->execute(array($row['originalImage'], $idArticlePublished));
    $dbh = null;
    $row = null;
}
$dbh = new PDO($dbFrom);
$stmt = $dbh->prepare('DELETE FROM articles WHERE idArticle=?');
$stmt->execute(array($idArticleMove));
$dbh = null;
?>