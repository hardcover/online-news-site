<?php
/**
 * Cron daily before back up to vacuum the databases
 * Identical in newseditor and newssubscriber
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2021 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2022 09 19
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
//
// Variables
//
date_default_timezone_set('America/Los_Angeles');
$startTime = time();
$today = date("Y-m-d");
$prior = null;
$database = 'databases/archive.sqlite';
$database2 = 'databases/archive2.sqlite';
//
// Break out new archive and archive2 databases as needed
//
$dbh = new PDO('sqlite:' . $database);
$stmt = $dbh->query('SELECT count(rowid) FROM articles');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$dbh = null;
//
// Check for more than 100 rows in the archive database
//
$highestArchiveDatabase = 1;
if ($row['count(rowid)'] > 100) {
    //
    // Count the existing numbered archive databases
    //
    $dbNumber = 1;
    while ($dbNumber !== -1) {
        $db = str_replace('archive', 'archive-' . $dbNumber, 'sqlite:' . $database);
        if (file_exists(str_replace('sqlite:', '', $db))) {
            $dbNumber++;
            $highestArchiveDatabase = $dbNumber;
        } else {
            $dbNumber = -1;
        }
    }
    //
    // Break out the new numbered archive databases
    //
    $dbNumber = $highestArchiveDatabase;
    $dbh = new PDO('sqlite:' . $database);
    $stmt = $dbh->query('SELECT idArticle FROM articles ORDER BY rowid');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $i = 0;
    foreach ($stmt as $row) {
        extract($row);
        $i++;
        if ($i < 101) {
            $articles[] = $idArticle;
        } else {
            $dbhNew = new PDO(str_replace('archive', 'archive-' . $dbNumber, 'sqlite:' . $database));
            $stmt = $dbhNew->query('CREATE VIRTUAL TABLE IF NOT EXISTS "articles" USING fts4 ("idArticle", "publicationDate", "publicationTime", "endDate", "survey", "genre", "keywords", "idSection", "sortOrderArticle", "sortPriority", "byline", "headline", "standfirst", "text", "summary", "evolve", "expand", "extend", "photoName", "photoCredit", "photoCaption", "originalImageWidth", "originalImageHeight", "thumbnailImage", "thumbnailImageWidth", "thumbnailImageHeight", "hdImage", "hdImageWidth", "hdImageHeight")');
            foreach ($articles as $article) {
                $stmt = $dbh->prepare('SELECT * FROM articles WHERE idArticle=?');
                $stmt->setFetchMode(PDO::FETCH_NUM);
                $stmt->execute([$article]);
                $row = $stmt->fetch();
                $stmt = $dbhNew->prepare('INSERT INTO articles VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute($row);
            }
            $stmt = $dbhNew->query('VACUUM');
            $dbhNew = null;
            foreach ($articles as $article) {
                $stmt = $dbh->prepare('DELETE FROM articles WHERE idArticle=?');
                $stmt->execute([$article]);
            }
            $dbhNew = new PDO(str_replace('archive2', 'archive2-' . $dbNumber, 'sqlite:' . $database2));
            $stmt = $dbhNew->query('CREATE TABLE IF NOT EXISTS "imageSecondary" ("idPhoto" INTEGER UNIQUE, "idArticle" INTEGER, "image", "photoName", "photoCredit", "photoCaption", "time" INTEGER)');
            $stmt = $dbhNew->query('CREATE INDEX IF NOT EXISTS "main"."imageSecondaryIndex" ON "imageSecondary" ("idPhoto" ASC);');
            $dbhArchive2 = new PDO('sqlite:' . $database2);
            foreach ($articles as $article) {
                $stmt = $dbhArchive2->prepare('SELECT * FROM imageSecondary WHERE idArticle=?');
                $stmt->setFetchMode(PDO::FETCH_NUM);
                $stmt->execute([$article]);
                foreach ($stmt as $row) {
                    $stmt = $dbhNew->prepare('INSERT INTO imageSecondary VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute($row);
                }
            }
            $dbhNew = null;
            foreach ($articles as $article) {
                $stmt = $dbhArchive2->prepare('DELETE FROM imageSecondary WHERE idArticle=?');
                $stmt->execute([$article]);
            }
            $dbhArchive2 = null;
            $i = 0;
            $dbNumber++;
            $articles = [];
        }
    }
    $dbh = null;
}
//
// Vacuum the database
//
if (file_exists('cron-vacuum.log')) {
    $prior = file_get_contents('cron-vacuum.log');
}
$startSize = number_format(@filesize($database) / 1024);
$dbh = new PDO('sqlite:' . $database);
$stmt = $dbh->query('PRAGMA integrity_check');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$integrity_check = isset($row['integrity_check']) ? $row['integrity_check'] : 0;
$dbh = null;
if ($integrity_check !== 'ok') {
    if (file_exists('error_log')) {
        $priorLog = file_get_contents('error_log');
    } else {
        $priorLog = null;
    }
    $errorMessage = 'newssubscriber ' . $database . "\n";
    $errorMessage.= $integrity_check . "\n\n";
    file_put_contents('error_log', $errorMessage . $priorLog);
}
$dbh = new PDO('sqlite::memory:');
$stmt = $dbh->query('CREATE TABLE "a" ("b")');
$dbh = null;
$dbh = new PDO('sqlite:' . $database);
$stmt = $dbh->query('PRAGMA page_count');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$page_count = isset($row['page_count']) ? $row['page_count'] : 0;
$startPageCount = $page_count;
$dbh = null;
$dbh = new PDO('sqlite::memory:');
$stmt = $dbh->query('CREATE TABLE "a" ("b")');
$dbh = null;
$dbh = new PDO('sqlite:' . $database);
$stmt = $dbh->query('VACUUM');
$dbh = null;
$dbh = new PDO('sqlite::memory:');
$stmt = $dbh->query('CREATE TABLE "a" ("b")');
$dbh = null;
$dbh = new PDO('sqlite:' . $database);
$stmt = $dbh->query('PRAGMA page_count');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$page_count = isset($row['page_count']) ? $row['page_count'] : 0;
$dbh = null;
$dbh = new PDO('sqlite::memory:');
$stmt = $dbh->query('CREATE TABLE "a" ("b")');
$dbh = null;
$endSize = number_format(filesize($database) / 1024);
$body = $database . "\n";
$body.= 'Integrity: ' . $integrity_check . "\n";
$body.= number_format($startPageCount) . ' pages before, ' . number_format($page_count) . ' pages after' . "\n";
$body.= $startSize . ' KB before, ' . $endSize . ' KB after' . "\n\n";
file_put_contents('cron-vacuum.log', $body . $prior);
//
// Write run stats to the cron-vacuum.log, limit the size of cron-vacuum.log
//
$prior = null;
if (file_exists('cron-vacuum.log')) {
    $i = 0;
    $priorLog = file('cron-vacuum.log');
    foreach ($priorLog as $value) {
        if ($i < 500) {
            $prior.= $value;
            $i++;
        }
    }
}
$endTime = time();
$dif = $endTime - $startTime;
$hours = intval($dif / 60 / 60);
$totalMinutes = intval($dif / 60);
$minutes = sprintf('%02d', $totalMinutes - ($hours * 60));
$seconds = sprintf('%02d', round($dif - ($totalMinutes * 60)));
$body = "\n" . $today . ', ' . number_format(memory_get_peak_usage() / 1024 / 1024, 1) . ' MB RAM used' . ', memory_limit: ' . ini_get('memory_limit') . "\n";
$body.= $hours . ':' . $minutes . ':' . $seconds . ' run time at ' . date("H:i:s") . ', max_execution_time: ' . ini_get('max_execution_time') . "\n\n";
file_put_contents('cron-vacuum.log', $body . $prior);
//
// Add the run stats to the cron email
//
echo $hours . ':' . $minutes . ':' . $seconds . ' run time at ' . date("H:i:s") . "\n";
echo number_format(memory_get_peak_usage() / 1024 / 1024, 1) . ' MB RAM used' . "\n\n";
echo ini_get('max_execution_time') . ' max_execution_time' . "\n";
echo ini_get('memory_limit') . ' memory_limit';
?>
