<?php
/**
 * Synchronizes the remote and local databases
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2016 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *.@license   http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2016-10-01
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
//
// Loop through each remote location
//
foreach ($remotes as $remote) {
    //
    // Download contributed articles to the edit database
    //
    $request = null;
    $response = null;
    $request['task'] = 'downloadContributionIDs';
    $response = soa($remote . 'z/', $request);
    if (isset($response['IDs'])) {
        $IDs = json_decode($response['IDs'], true);
        foreach ($IDs as $idArticleRemote) {
            $request = null;
            $response = null;
            $request['task'] = 'downloadContribution1';
            $request['idArticle'] = $idArticleRemote;
            $response = soa($remote . 'z/', $request);
            if ($response['result'] = 'success') {
                extract($response);
                $dbh = new PDO($dbArchive);
                $stmt = $dbh->prepare('INSERT INTO articles (headline) VALUES (?)');
                $stmt->execute(array(null));
                $idArticle = $dbh->lastInsertId();
                $stmt = $dbh->prepare('UPDATE articles SET idArticle=? WHERE rowid=?');
                $stmt->execute(array($idArticle, $idArticle));
                $dbh = null;
                $dbh = new PDO($dbEdit);
                $stmt = $dbh->prepare('INSERT INTO articles (idArticle, idSection, byline, headline, standfirst, text, summary, photoCredit, photoCaption) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute(array($idArticle, $idSection, $byline, $headline, $standfirst, $text, $summary, $photoCredit, $photoCaption));
                $dbh = null;
                if (isset($thumbnailImageWidth) and $thumbnailImageWidth != 'null') {
                    $request = null;
                    $response = null;
                    $request['task'] = 'downloadContribution2';
                    $request['idArticle'] = $idArticleRemote;
                    $response = soa($remote . 'z/', $request);
                    if (isset($response['thumbnailImageWidth'])) {
                        extract($response);
                        $dbh = new PDO($dbEdit);
                        $stmt = $dbh->prepare('UPDATE articles SET thumbnailImage=?, thumbnailImageWidth=?, thumbnailImageHeight=?, hdImageWidth=?, hdImageHeight=? WHERE idArticle=?');
                        $stmt->execute(array($thumbnailImage, $thumbnailImageWidth, $thumbnailImageHeight, $hdImageWidth, $hdImageHeight, $idArticle));
                        $dbh = null;
                        $request = null;
                        $response = null;
                        $request['task'] = 'downloadContribution3';
                        $request['idArticle'] = $idArticleRemote;
                        $response = soa($remote . 'z/', $request);
                        if (isset($response['hdImage'])) {
                            $dbh = new PDO($dbEdit);
                            $stmt = $dbh->prepare('UPDATE articles SET hdImage=? WHERE idArticle=?');
                            $stmt->execute(array($response['hdImage'], $idArticle));
                            $dbh = null;
                        }
                    }
                }
                $request = null;
                $response = null;
                $request['task'] = 'downloadContribution4a';
                $request['idArticle'] = $idArticleRemote;
                $response = soa($remote . 'z/', $request);
                if (isset($response['idPhotos'])) {
                    $idPhotos = json_decode($response['idPhotos'], true);
                    $request = null;
                    $request['task'] = 'downloadContribution4b';
                    foreach ($idPhotos as $idPhoto) {
                        $request['idPhoto'] = $idPhoto;
                        $response = null;
                        $response = soa($remote . 'z/', $request);
                        if (isset($response['hdImage'])) {
                            $dbh = new PDO($dbEdit2);
                            $stmt = $dbh->prepare('INSERT INTO imageSecondary (idArticle, image, photoCredit, photoCaption, time) VALUES (?, ?, ?, ?, ?)');
                            $stmt->execute(array($idArticle, $response['hdImage'], $response['photoCredit'], $response['photoCaption'], time()));
                            $dbh = null;
                        }
                    }
                }
            }
            if ($response['result'] = 'success') {
                $request = null;
                $response = null;
                $request['task'] = 'downloadContributionDelete';
                $request['idArticle'] = $idArticleRemote;
                $response = soa($remote . 'z/', $request);
            }
        }
    }
    //
    // Determine the published or archive databases
    //
    $request = null;
    $response = null;
    if (empty($archiveSync)) {
        $archive = null;
        $database = $dbPublished;
        $database2 = $dbPublished2;
        $request['task'] = 'publishedSync';
    } else {
        $archive = 'archive';
        $database = $dbArchive;
        $database2 = $dbArchive2;
        $request['task'] = 'archiveSync';
    }
    //
    // Determine the missing and extra articles
    //
    $response = soa($remote . 'z/', $request);
    $remoteArticles = json_decode($response['remoteArticles'], true);
    if ($remoteArticles == 'null' or $remoteArticles == null) {
        $remoteArticles = array();
    }
    $articles = array();
    $dbh = new PDO($database);
    if (is_null($archive)) {
        $stmt = $dbh->query('SELECT idArticle FROM articles');
    } else {
        $stmt = $dbh->query('SELECT idArticle FROM articles WHERE publicationDate IS NOT NULL');
    }
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        $articles[] = $row['idArticle'];
    }
    $dbh = null;
    $missingArticles = array_diff($articles, $remoteArticles);
    $extraArticles = array_diff($remoteArticles, $articles);
    //
    // Upload missing articles to the remote sites
    //
    if (count($missingArticles) > 0) {
        foreach ($missingArticles as $idArticle) {
            include $includesPath . '/addUpdateArticle.php';
        }
    }
    //
    // When extra remote articles were found above, check again and delete the extra articles
    //
    if (count($extraArticles) > 0) {
        $request = null;
        $response = null;
        if (isset($archiveSync)) {
            $request['task'] = 'archiveSync';
        } else {
            $request['task'] = 'publishedSync';
        }
        $response = soa($remote . 'z/', $request);
        $remoteArticles = json_decode($response['remoteArticles'], true);
        if ($remoteArticles == 'null' or $remoteArticles == null) {
            $remoteArticles = array();
        }
        $dbh = new PDO($database);
        $stmt = $dbh->query('SELECT idArticle FROM articles');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            $articles[] = $row['idArticle'];
        }
        $dbh = null;
        $extraArticles = array_diff($remoteArticles, $articles);
        //
        // Delete extra remote articles
        //
        if (isset($archiveSync)) {
            $request = null;
            $response = null;
            $request['task'] = 'archiveDelete';
        } else {
            $request['task'] = 'publishedDelete';
        }
        foreach ($extraArticles as $idArticle) {
            $request['idArticle'] = $idArticle;
            $response = soa($remote . 'z/', $request);
        }
    }
}
//
// Reset database variables
//
if ($use == 'edit') {
    $database = $dbEdit;
    $database2 = $dbEdit2;
    $imagePath = 'imagee.php';
    $imagePath2 = 'imagee2.php';
} elseif ($use == 'published') {
    $database = $dbPublished;
    $database2 = $dbPublished2;
    $imagePath = 'imagep.php';
    $imagePath2 = 'imagep2.php';
}
?>