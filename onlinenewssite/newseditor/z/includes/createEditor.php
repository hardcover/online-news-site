<?php
/**
 * Create the editor databases on the first run
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *            http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2018 01 08
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
if (!file_exists($includesPath . '/databases')) {
    mkdir($includesPath . '/databases', 0755);
}
//
$dbh = new PDO($dbEditors);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "users" ("idUser" INTEGER PRIMARY KEY, "user", "pass", "fullName", "email", "userType" INTEGER)');
$stmt = $dbh->query('SELECT count(*) FROM users');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
if ($row['count(*)'] < 1) {
    $adminPass = password_hash('admin', PASSWORD_DEFAULT);
    $stmt = $dbh->prepare('INSERT INTO users (user, pass, fullName) VALUES (?, ?, ?)');
    $stmt->execute(array('admin', $adminPass, 'Administrator'));
}
$dbh = null;
//
$dbh = new PDO($dbRemote);
$stmt = $dbh->query('CREATE TABLE "remotes" ("idRemote" INTEGER PRIMARY KEY, "remote")');
$dbh = null;
?>