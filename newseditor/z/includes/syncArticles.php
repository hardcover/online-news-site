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
 * @version   GIT: 2015-05-31
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
if (!isset($archive)) {
    $archive = null;
}
//
// Loop through each remote location
//
$dbhRemote = new PDO($dbRemote);
$stmt = $dbhRemote->query('SELECT remote FROM remotes');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    extract($row);
    //
    // Download contributed articles
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
                $dbh = new PDO($dbEdit);
                $stmt = $dbh->prepare('INSERT INTO articles (idSection, byline, headline, standfirst, text, summary, photoCredit, photoCaption) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute(array($idSection, $byline, $headline, $standfirst, $text, $summary, $photoCredit, $photoCaption));
                $idArticle = $dbh->lastInsertId();
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
            }
            $request = null;
            $response = null;
            $request['task'] = 'downloadContributionDelete';
            $request['idArticle'] = $idArticleRemote;
            $response = soa($remote . 'z/', $request);
        }
    }
    //
    // Determine the published or archive databases
    //
    if (isset($archiveSync)) {
        $request['task'] = 'archiveSync';
        $database = $dbArchive;
    } else {
        $request['task'] = 'publishedSync';
        $database = $dbPublished;
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
    $stmt = $dbh->query('SELECT idArticle FROM articles');
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
$dbhRemote = null;
?>