<?php
/**
 * Sets the sort order for published articles
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
$dbh = new PDO($dbPublished);
$stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE idArticle=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array($idArticle));
$row = $stmt->fetch();
if ($row) {
    $stmt = $dbh->prepare('SELECT idSection, sortOrderArticle FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idArticle));
    $row = $stmt->fetch();
    if ($row) {
        extract($row);
        //
        // Establish the change in sort order, if any
        //
        if (isset($_POST['up'])) {
            $sortOrderArticleNew = $sortOrderArticle > 1 ? $sortOrderArticle - 1 : $sortOrderArticle;
        } elseif (isset($_POST['down'])) {
            $sortOrderArticleNew = $sortOrderArticle + 2;
        } else {
            $sortOrderArticleNew = $sortOrderArticle;
        }
        $stmt = $dbh->prepare('UPDATE articles SET sortOrderArticle=?, sortPriority=? WHERE idArticle=?');
        $stmt->execute(array($sortOrderArticleNew, '1', $idArticle));
    }
    $stmt = $dbh->prepare('SELECT idArticle, sortOrderArticle FROM articles WHERE idSection=? ORDER BY sortOrderArticle, sortPriority');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idSection));
    $count = null;
    foreach ($stmt as $row) {
        extract($row);
        $count++;
        $stmt = $dbh->prepare('UPDATE articles SET sortOrderArticle=? WHERE idArticle=?');
        $stmt->execute(array($count, $idArticle));
    }
    //
    // Restore presort settings
    //
    $stmt = $dbh->prepare('UPDATE articles SET sortPriority=? WHERE idSection=?');
    $stmt->execute(array(2, $idSection));
    //
    // Update the remote databases
    //
    $sortOrder = null;
    $stmt = $dbh->prepare('SELECT publicationDate, endDate, sortOrderArticle, idArticle FROM articles WHERE idSection=?');
    $stmt->setFetchMode(PDO::FETCH_NUM);
    $stmt->execute(array($idSection));
    foreach ($stmt as $row) {
        $sortOrder[] = $row;
    }
    $dbh = null;
    $sortOrder = json_encode($sortOrder);
    $request = null;
    $request['task'] = 'publishedOrder';
    $request['sortOrder'] = $sortOrder;
    $dbhRemote = new PDO($dbRemote);
    $stmt = $dbhRemote->query('SELECT remote FROM remotes');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        $response = soa($row['remote'] . 'z/', $request);
    }
    $dbhRemote = null;
}
$dbh = null;
?>