<?php
/**
 * Updates the remote menu databases
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2018 09 28
 * @link      https://hardcoverwebdesign.com/
 * @link      https://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
//
// Variables
//
$remotes = [];
$dbh = new PDO($dbRemote);
$stmt = $dbh->query('SELECT remote FROM remotes');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    $remotes[] = $row['remote'];
}
$dbh = null;
//
// Loop through each remote location
//
foreach ($remotes as $remote) {
    //
    // Determine the missing and extra menu items
    //
    $request = null;
    $response = null;
    $request['task'] = 'menuSync';
    $response = soa($remote . 'z/', $request);
    $remoteMenu = json_decode($response['remoteMenu'], true);
    if ($remoteMenu == 'null' or $remoteMenu == null) {
        $remoteMenu = [];
    }
    $menu = [];
    $dbh = new PDO($dbMenu);
    $stmt = $dbh->query('SELECT idMenu FROM menu');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        $menu[] = $row['idMenu'];
    }
    $dbh = null;
    $missingMenuItems = array_diff($menu, $remoteMenu);
    $extraMenuItems = array_diff($remoteMenu, $menu);
    //
    // Upload missing menu items to the remote sites
    //
    if (count($missingMenuItems) > 0) {
        foreach ($missingMenuItems as $idMenu) {
            $dbh = new PDO($dbMenu);
            $stmt = $dbh->prepare('SELECT menuName, menuSortOrder, menuPath, menuContent, menuAuthorization FROM menu WHERE idMenu=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$idMenu]);
            $row = $stmt->fetch();
            $dbh = null;
            extract($row);
            $request = null;
            $response = null;
            $request['task'] = 'menuInsert';
            $request['idMenu'] = $idMenu;
            $request['menuName'] = $menuName;
            $request['menuSortOrder'] = $menuSortOrder;
            $request['menuPath'] = $menuPath;
            $request['menuContent'] = $menuContent;
            $request['menuAuthorization'] = $menuAuthorization;
            $response = soa($remote . 'z/', $request);
        }
    }
    //
    // When extra remote menu items were found above, check again and delete the extra items
    //
    if (count($extraMenuItems) > 0) {
        $request = null;
        $response = null;
        $request['task'] = 'menuSync';
        $response = soa($remote . 'z/', $request);
        $remoteMenu = json_decode($response['remoteMenu'], true);
        if ($remoteMenu == 'null' or $remoteMenu == null) {
            $remoteMenu = [];
        }
        $dbh = new PDO($dbMenu);
        $stmt = $dbh->query('SELECT idMenu FROM menu');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            $menu[] = $row['idMenu'];
        }
        $dbh = null;
        $extraMenuItems = array_diff($remoteMenu, $menu);
        //
        // Delete extra remote menu items
        //
        $request = null;
        $response = null;
        $request['task'] = 'menuDelete';
        foreach ($extraMenuItems as $idMenu) {
            $request['idMenu'] = $idMenu;
            $response = soa($remote . 'z/', $request);
        }
    }
}
?>