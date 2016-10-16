<?php
/**
 * Deletes answers for deleted survey questions and updates the remote sites
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2016 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *.@license   http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2016-10-16
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
//
// Variables
//
$remotes = array();
$dbh = new PDO($dbRemote);
$stmt = $dbh->query('SELECT remote FROM remotes');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    $remotes[] = $row['remote'];
}
$dbh = null;
//
// Delete answers for deleted survey questions
//
$surveysInEditPublishedArchive = array();
$surveysInSurvey = array();
$dbh = new PDO($dbEdit);
$stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE survey=? ORDER BY idArticle');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array(1));
foreach ($stmt as $row) {
    $surveysInEditPublishedArchive[] = $row['idArticle'];
}
$dbh = null;
$dbh = new PDO($dbPublished);
$stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE survey=? ORDER BY idArticle');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array(1));
foreach ($stmt as $row) {
    $surveysInEditPublishedArchive[] = $row['idArticle'];
}
$dbh = null;
$dbh = new PDO($dbArchive);
$stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE survey=? ORDER BY idArticle');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array(1));
foreach ($stmt as $row) {
    $surveysInEditPublishedArchive[] = $row['idArticle'];
}
$dbh = null;
$dbh = new PDO($dbSurvey);
$stmt = $dbh->query('SELECT DISTINCT idArticle FROM answers ORDER BY idArticle');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    $surveysInSurvey[] = $row['idArticle'];
}
$dbh = null;
$extras = array_diff($surveysInSurvey, $surveysInEditPublishedArchive);
if (!empty($extras)) {
    $dbh = new PDO($dbSurvey);
    $stmt = $dbh->prepare('DELETE FROM answers WHERE idArticle=?');
    foreach ($extras as $extra) {
        $stmt->execute(array($extra));
    }
    $dbh = null;
}
//
// Update answers on the remote sites
//
$answers = array();
$dbh = new PDO($dbSurvey);
$stmt = $dbh->query('SELECT * FROM answers ORDER BY idAnswer');
$stmt->setFetchMode(PDO::FETCH_NUM);
foreach ($stmt as $row) {
    $answers[] = json_encode($row);
}
$dbh = null;
$request = null;
$response = null;
$request['task'] = 'surveySync';
$request['archive'] = null;
$request['answers'] = json_encode($answers);
foreach ($remotes as $remote) {
    $response = soa($remote . 'z/', $request);
}
?>
