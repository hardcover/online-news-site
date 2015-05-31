<?php
/**
 * Updates a published article that has just been edited
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
if (!isset($archive)) {
    $archive = null;
}
//
// Upload the updated article to the remote sites
//
$dbh = new PDO($database);
$stmt = $dbh->prepare('SELECT publicationDate, endDate, idSection, sortOrderArticle, byline, headline, standfirst, text, summary, photoCredit, photoCaption FROM articles WHERE idArticle=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array($idArticle));
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    extract($row);
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
    $response = soa($remote . 'z/', $request);
    //
    // Check for an image
    //
    $dbh = new PDO($database);
    $stmt = $dbh->prepare('SELECT thumbnailImageWidth FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idArticle));
    $row = $stmt->fetch();
    $dbh = null;
    if ($row['thumbnailImageWidth'] != null) {
        //
        // Upload the published thumbnail and other small items
        //
        $request = null;
        $dbh = new PDO($database);
        $stmt = $dbh->prepare('SELECT thumbnailImage, thumbnailImageWidth, thumbnailImageHeight, hdImageWidth, hdImageHeight FROM articles WHERE idArticle=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(array($idArticle));
        $row = $stmt->fetch();
        $dbh = null;
        if ($response['result'] == 'success') {
            $response = null;
            $request['task'] = 'updateInsert2';
            $request['archive'] = $archive;
            $request['thumbnailImage'] = $row['thumbnailImage'];
            $request['thumbnailImageWidth'] = $row['thumbnailImageWidth'];
            $request['thumbnailImageHeight'] = $row['thumbnailImageHeight'];
            $request['hdImageWidth'] = $row['hdImageWidth'];
            $request['hdImageHeight'] = $row['hdImageHeight'];
            $request['idArticle'] = $idArticle;
            $response = soa($remote . 'z/', $request);
        }
        //
        // Upload the published HD image
        //
        $request = null;
        $dbh = new PDO($database);
        $stmt = $dbh->prepare('SELECT hdImage FROM articles WHERE idArticle=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(array($idArticle));
        $row = $stmt->fetch();
        $dbh = null;
        if ($response['result'] == 'success') {
            $response = null;
            $request['task'] = 'updateInsert3';
            $request['archive'] = $archive;
            $request['hdImage'] = $row['hdImage'];
            $request['idArticle'] = $idArticle;
            $response = soa($remote . 'z/', $request);
        }
    }
}
?>