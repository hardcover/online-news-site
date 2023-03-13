<?php
/**
 * Create the editor databases on the first run
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2021 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2023 03 13
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
if (!file_exists($includesPath . '/databases')) {
    mkdir($includesPath . '/databases', 0755);
}
//
$dbh = new PDO($dbArticleId);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "articles" ("idArticle", "headline")');
$dbh = null;
//
$dbh = new PDO($dbEditors);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "users" ("idUser" INTEGER PRIMARY KEY, "user", "pass", "fullName", "email", "userType" INTEGER)');
$stmt = $dbh->query('SELECT count(*) FROM users');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
if ($row['count(*)'] < 1) {
    $adminPass = password_hash('admin', PASSWORD_DEFAULT);
    $stmt = $dbh->prepare('INSERT INTO users (user, pass, fullName) VALUES (?, ?, ?)');
    $stmt->execute(['admin', $adminPass, 'Administrator']);
}
$dbh = null;
//
$dbh = new PDO($dbPhotoId);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "photos" ("idPhoto", "idArticle")');
$dbh = null;
//
$dbh = new PDO($dbRemote);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "remotes" ("idRemote" INTEGER PRIMARY KEY, "remote")');
$dbh = null;
?>