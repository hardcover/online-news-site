<?php
/**
 * Create the editor databases on the first run
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
if (!file_exists($includesPath . '/databases')) {
    mkdir($includesPath . '/databases', 0644);
}
require $includesPath . '/password_compat/password.php';
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