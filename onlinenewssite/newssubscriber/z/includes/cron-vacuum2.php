<?php
/**
 * Cron daily to vacuum the subscriber databases
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2018 05 06
 * @link      https://hardcoverwebdesign.com/
 * @link      https://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
//
// Variables
//
date_default_timezone_set('America/Los_Angeles');
$startTime = time();
$today = date("Y-m-d");
$prior = null;
$databases = [
    'databases/archive.sqlite'
];
//
// Vacuum the database
//
foreach ($databases as $database) {
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
    $stmt = $dbh->query('PRAGMA page_size = 4096');
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
}
//
// Write run stats to the cron-vacuum.log, limit the size of cron-vacuum.log
//
if (file_exists('cron-vacuum.log')) {
    $i = null;
    $priorLog = file('cron-vacuum.log');
    foreach ($priorLog as $value) {
        if ($i < 1000) {
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
