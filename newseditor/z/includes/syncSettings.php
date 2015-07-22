<?php
/**
 * Updates the remote settings databases
 *
 * PHP version 5
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2013-2015 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *.@license   http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version   GIT: 2015-07-21
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
$request = null;
$request['task'] = 'settingsUpdate';
//
// Update archive access
//
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT idAccess, access FROM archiveAccess');
$stmt->setFetchMode(PDO::FETCH_NUM);
$row = $stmt->fetch();
if ($row) {
    $request['archiveAccess'] = json_encode($row);
}
//
// Update newspaper name
//
$stmt = $dbh->query('SELECT idName, name, description FROM names');
$stmt->setFetchMode(PDO::FETCH_NUM);
$row = $stmt->fetch();
if ($row) {
    $request['name'] = json_encode($row);
}
//
// Update newpaper sections
//
$sortOrder = null;
$stmt = $dbh->query('SELECT idSection, section, sortOrderSection FROM sections ORDER BY idSection');
$stmt->setFetchMode(PDO::FETCH_NUM);
foreach ($stmt as $row) {
    $sortOrder[] = $row;
}
$dbh = null;
$sortOrder = json_encode($sortOrder);
$request['sortOrder'] = $sortOrder;
//
// Loop through each remote location
//
foreach ($remotes as $remote) {
    $response = soa($remote . 'z/', $request);
}
?>