<?php
/**
 * Cron daily after vacuum to back up the databases, keeps 30 days of backups
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2025 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 * @version:  2025 02 03
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
$database = '../databases/archive.sqlite';
//
// Copy the numbered archive databases to the back up directory
//
$dbNumber = 1;
while ($dbNumber !== -1) {
    $filePath = str_replace('archive', 'archive-' . $dbNumber, $database);
    $filePath2 = str_replace('archive', 'archive2-' . $dbNumber, $database);
    if (file_exists($filePath)) {
        $filename = strrchr($filePath, '/');
        copy($filePath, '../databases/backup/' . $today . '/' . $filename);
        $filename = str_replace('archive', 'archive2', $filename);
        copy($filePath2, '../databases/backup/' . $today . '/' . $filename);
        $dbNumber++;
    } else {
        $dbNumber = -1;
    }
}
//
// Create the back up databases
//
if (file_exists('backup.log')) {
    $prior = file_get_contents('backup.log');
}
$startSize = number_format(@filesize($database) / 1024);
//
// Parse the database file name
//
$filename = strrchr($database, '/');
//
// Copy the archive direct to backup
//
$dbh = new PDO('sqlite:../databases/archive.sqlite');
$dbhBACKUP = new PDO('sqlite:../databases/backup/' . $today . '/archive.sqlite');
$dbhBACKUP->beginTransaction();
$stmt = $dbhBACKUP->query('CREATE VIRTUAL TABLE IF NOT EXISTS "articles" USING fts4 ("idArticle", "publicationDate", "publicationTime", "endDate", "survey", "genre", "keywords", "idSection", "sortOrderArticle", "sortPriority", "byline", "headline", "standfirst", "text", "summary", "evolve", "expand", "extend", "photoName", "photoCredit", "photoCaption", "alt", "originalImageWidth", "originalImageHeight", "thumbnailImage", "thumbnailImageWidth", "thumbnailImageHeight", "hdImage", "hdImageWidth", "hdImageHeight")');
$stmt = $dbh->query('SELECT * FROM articles');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    extract($row);
    $stmt = $dbhBACKUP->prepare('INSERT INTO articles (rowid, idArticle, publicationDate, publicationTime, endDate, survey, genre, keywords, idSection, sortOrderArticle, sortPriority, byline, headline, standfirst, text, summary, evolve, expand, extend, photoName, photoCredit, photoCaption, alt originalImageWidth, originalImageHeight, thumbnailImage, thumbnailImageWidth, thumbnailImageHeight, hdImage, hdImageWidth, hdImageHeight) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$idArticle, $idArticle, $publicationDate, $publicationTime, $endDate, $survey, $genre, $keywords, $idSection, $sortOrderArticle, $sortPriority, $byline, $headline, $standfirst, $text, $summary, $evolve, $expand, $extend, $photoName, $photoCredit, $photoCaption, $alt, $originalImageWidth, $originalImageHeight, $thumbnailImage, $thumbnailImageWidth, $thumbnailImageHeight, $hdImage, $hdImageWidth, $hdImageHeight]);
}
$dbh = null;
$dbhBACKUP->commit();
//
// Check integrity and size of the back up
//
$stmt = $dbhBACKUP->query('PRAGMA integrity_check');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$dbhBACKUP = null;
$integrity_check = isset($row['integrity_check']) ? $row['integrity_check'] : 0;
if ($integrity_check !== 'ok') {
    if (file_exists('error_log')) {
        $priorLog = file_get_contents('error_log');
    } else {
        $priorLog = null;
    }
    $errorMessage = 'back up ' . $database . "\n";
    $errorMessage.= $integrity_check . "\n\n";
    file_put_contents('error_log', $errorMessage . $priorLog);
}
$endSize = number_format(filesize('../databases/backup/' . $today . '/' . $filename) / 1024);
$body = ltrim($filename, '/') . "\n";
$body.= 'Integrity: ' . $integrity_check . "\n";
$body.= $startSize . ' KB original, ' . $endSize . ' KB back up' . "\n\n";
file_put_contents('backup.log', $body . $prior);
//
// Release the database handles
//
$dbh = null;
$dbh = new PDO('sqlite::memory:');
$stmt = $dbh->query('CREATE TABLE "a" ("b")');
$dbh = null;
$dbhMemory = null;
$dbhMemory = new PDO('sqlite::memory:');
$stmt = $dbhMemory->query('CREATE TABLE "a" ("b")');
$dbhMemory = null;
//
// Write run stats to the backup.log, limit the size of the log
//
$prior = null;
if (file_exists('backup.log')) {
    $i = 0;
    $priorLog = file('backup.log');
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
file_put_contents('backup.log', $body . $prior);
//
// Add the run stats to the cron email
//
echo $hours . ':' . $minutes . ':' . $seconds . ' run time at ' . date("H:i:s") . "\n";
echo number_format(memory_get_peak_usage() / 1024 / 1024, 1) . ' MB RAM used' . "\n\n";
echo ini_get('max_execution_time') . ' max_execution_time' . "\n";
echo ini_get('memory_limit') . ' memory_limit';
?>
