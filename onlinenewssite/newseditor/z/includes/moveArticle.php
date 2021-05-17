<?php
/**
 * Moves an article from the specified from database to the specified to database
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
if ($dbFrom === $dbEdit) {
    //
    // Move from edit to published
    //
    $archive = null;
    $dbFrom2 = $dbEdit2;
    $dbTo = $dbPublished;
    $dbTo2 = $dbPublished2;
} elseif ($dbFrom === $dbPublished) {
    //
    // Move from published to archive
    //
    $archive = 'archive';
    $dbFrom2 = $dbPublished2;
    $dbTo = $dbArchive;
    $dbTo2 = $dbArchive2;
} elseif (strpos($dbFrom, 'archive') !== false) {
    //
    // Move from archive to edit
    //
    $archive = null;
    if (strpos($dbFrom, 'archive-') !== false) {
        $dbFrom2 = str_replace('archive-', 'archive2-', $dbFrom);
    } else {
        $dbFrom2 = $dbArchive2;
    }
    $dbTo = $dbEdit;
    $dbTo2 = $dbEdit2;
} else {
    exit;
}
//
// Move the non-image information
//
$dbh = new PDO($dbFrom);
$stmt = $dbh->prepare('SELECT publicationDate, publicationTime, endDate, survey, genre, keywords, idSection, sortOrderArticle, byline, headline, standfirst, text, summary, evolve, expand, extend, photoName, photoCredit, photoCaption FROM articles WHERE idArticle=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$idArticle]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    extract($row);
    if ($dbFrom === $dbPublished) {
        //
        // Retain a match between rowid and idArticle in the local archive database
        //
        $dbh = new PDO($dbTo);
        $stmt = $dbh->prepare('SELECT rowid FROM articles WHERE idArticle=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idArticle]);
        $row = $stmt->fetch();
        $dbh = null;
        if (empty($row)) {
            $dbh = new PDO($dbTo);
            $stmt = $dbh->prepare('INSERT INTO articles (rowid, idArticle) VALUES (?, ?)');
            $stmt->execute([$idArticle, $idArticle]);
            $dbh = null;
        } else {
            $dbh = new PDO($dbTo);
            $stmt = $dbh->prepare('UPDATE articles SET idArticle=? WHERE rowid=?');
            $stmt->execute([$idArticle, $idArticle]);
            $dbh = null;
        }
        //
        // When moving surveys to the archive, copy the votes to the local survey database
        //
        if ($survey === strval(1)) {
            $request = null;
            $response = null;
            $request['task'] = 'surveyVotesDownload';
            $request['idArticle'] = $idArticle;
            foreach ($remotes as $remote) {
                $response = soa($remote . 'z/', $request);
            }
            if ($response['result'] === 'success') {
                $remoteVotes = json_decode($response['remoteVotes'], true);
                $dbh = new PDO($dbSurvey);
                $dbh->beginTransaction();
                foreach ($remoteVotes as $remoteVote) {
                    $remoteVote = json_decode($remoteVote, true);
                    $stmt = $dbh->prepare('INSERT INTO tally (idTally, idArticle, idAnswer, ipAddress) VALUES (?, ?, ?, ?)');
                    $stmt->execute($remoteVote);
                }
                $dbh->commit();
                $dbh = null;
            }
        }
    } else {
        //
        // Whatevs for other article databases
        //
        $dbh = new PDO($dbTo);
        $stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE idArticle=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idArticle]);
        $row = $stmt->fetch();
        $dbh = null;
        if (empty($row)) {
            $dbh = new PDO($dbTo);
            $stmt = $dbh->prepare('INSERT INTO articles (idArticle) VALUES (?)');
            $stmt->execute([$idArticle]);
            $dbh = null;
        }
    }
    $dbh = new PDO($dbTo);
    $stmt = $dbh->prepare('UPDATE articles SET publicationDate=?, publicationTime=?, endDate=?, survey=?, genre=?, keywords=?, idSection=?, byline=?, headline=?, standfirst=?, text=?, summary=?, evolve=?, expand=?, extend=?, photoName=?, photoCredit=?, photoCaption=? WHERE idArticle=?');
    $stmt->execute([$publicationDate, $publicationTime, $endDate, $survey, $genre, $keywords, $idSection, $byline, $headline, $standfirst, $text, $summary, $evolve, $expand, $extend, $photoName, $photoCredit, $photoCaption, $idArticle]);
    $dbh = null;
    if ($dbFrom !== $dbArchive) {
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
        $request['keywords'] = $keywords;
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
        if ($survey === '1') {
            include $includesPath . '/syncSurveyAnswers.php';
        }
    }
    //
    // Check for an image
    //
    $dbh = new PDO($dbFrom);
    $stmt = $dbh->prepare('SELECT thumbnailImageWidth FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idArticle]);
    $row = $stmt->fetch();
    $dbh = null;
    if (!empty($row['thumbnailImageWidth'])) {
        //
        // Move the thumbnail and other small items
        //
        $request = null;
        $dbh = new PDO($dbFrom);
        $stmt = $dbh->prepare('SELECT originalImageWidth, originalImageHeight, thumbnailImage, thumbnailImageWidth, thumbnailImageHeight, hdImageWidth, hdImageHeight FROM articles WHERE idArticle=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idArticle]);
        $row = $stmt->fetch();
        $dbh = null;
        $dbh = new PDO($dbTo);
        $stmt = $dbh->prepare('UPDATE articles SET originalImageWidth=?, originalImageHeight=?, thumbnailImage=?, thumbnailImageWidth=?, thumbnailImageHeight=?, hdImageWidth=?, hdImageHeight=? WHERE idArticle=?');
        $stmt->execute([$row['originalImageWidth'], $row['originalImageHeight'], $row['thumbnailImage'], $row['thumbnailImageWidth'], $row['thumbnailImageHeight'], $row['hdImageWidth'], $row['hdImageHeight'], $idArticle]);
        $dbh = null;
        if ($dbFrom !== $dbArchive) {
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
        $stmt->execute([$idArticle]);
        $row = $stmt->fetch();
        $dbh = null;
        $dbh = new PDO($dbTo);
        $stmt = $dbh->prepare('UPDATE articles SET hdImage=? WHERE idArticle=?');
        $stmt->execute([$row['hdImage'], $idArticle]);
        $dbh = null;
        if ($dbFrom !== $dbArchive) {
            if ($response['result'] === 'success') {
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
        $stmt = $dbhF->prepare('SELECT idPhoto, image, photoName, photoCredit, photoCaption, time FROM imageSecondary WHERE idArticle=? ORDER BY time');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idArticle]);
        foreach ($stmt as $row) {
            $dbh = new PDO($dbTo2);
            $stmt = $dbh->prepare('INSERT INTO imageSecondary (idPhoto, idArticle, image, photoName, photoCredit, photoCaption, time) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$row['idPhoto'], $idArticle, $row['image'], $row['photoName'], $row['photoCredit'], $row['photoCaption'], $row['time']]);
            $dbh = null;
            if ($dbFrom !== $dbArchive) {
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
        }
        $dbhF = null;
    }
    //
    // Verify the move on the main system before deleting the From article
    //
    $dbh = new PDO($dbFrom);
    $stmt = $dbh->prepare('SELECT publicationDate, publicationTime, endDate, survey, genre, keywords, idSection, byline, headline, text FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_NUM);
    $stmt->execute([$idArticle]);
    $row = $stmt->fetch();
    $dbh = null;
    $from = $row;
    $dbh = new PDO($dbTo);
    $stmt = $dbh->prepare('SELECT publicationDate, publicationTime, endDate, survey, genre, keywords, idSection, byline, headline, text FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_NUM);
    $stmt->execute([$idArticle]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($from === $row) {
        $dbh = new PDO($dbFrom2);
        $stmt = $dbh->prepare('SELECT count(*) FROM imageSecondary WHERE idArticle=?');
        $stmt->setFetchMode(PDO::FETCH_NUM);
        $stmt->execute([$idArticle]);
        $row = $stmt->fetch();
        $dbh = null;
        $from = $row;
        $dbh = new PDO($dbTo2);
        $stmt = $dbh->prepare('SELECT count(*) FROM imageSecondary WHERE idArticle=?');
        $stmt->setFetchMode(PDO::FETCH_NUM);
        $stmt->execute([$idArticle]);
        $row = $stmt->fetch();
        $dbh = null;
        if ($from === $row) {
            //
            // Delete the From article on the main system
            //
            $dbh = new PDO($dbFrom);
            $stmt = $dbh->prepare('DELETE FROM articles WHERE idArticle=?');
            $stmt->execute([$idArticle]);
            $dbh = null;
            $dbh = new PDO($dbFrom2);
            $stmt = $dbh->prepare('DELETE FROM imageSecondary WHERE idArticle=?');
            $stmt->execute([$idArticle]);
            $dbh = null;
            //
            // Delete the From article on the remote sites
            //
            $request = null;
            $response = null;
            if ($dbFrom === $dbPublished) {
                $request['task'] = 'publishedDelete';
                $request['archive'] = $archive;
                $request['idArticle'] = $idArticle;
                foreach ($remotes as $remote) {
                    $response = soa($remote . 'z/', $request);
                }
            }
            if (strpos($dbFrom, 'archive') !== false) {
                $request['task'] = 'archiveDelete';
                $request['archive'] = $archive;
                $request['idArticle'] = $idArticle;
                foreach ($remotes as $remote) {
                    $response = soa($remote . 'z/', $request);
                }
            }
        }
    }
}
?>