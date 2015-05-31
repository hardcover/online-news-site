<?php
/**
 * Synchronizes the remote and local databases
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
//
// Loop through each remote location
//
$dbhRemote = new PDO($dbRemote);
$stmt = $dbhRemote->query('SELECT remote FROM remotes');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    extract($row);
    //
    // Integrate new subscribers from remote sites
    //
    $request = null;
    $response = null;
    $request['task'] = 'subscribersNewDownload';
    $response = soa($remote . 'z/', $request);
    if ($response['result'] == 'success') {
        //
        // Merge new subscribers to the main subscriber database
        //
        if ($response['dbRows'] != 'null') {
            $dbRows = json_decode($response['dbRows'], true);
            $dbh = new PDO($dbSubscribers);
            $dbh->beginTransaction();
            foreach ($dbRows as $value) {
                $stmt = $dbh->prepare('SELECT email FROM users WHERE email=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute(array($value['email']));
                $row = $stmt->fetch();
                if ($row === false) {
                    $stmt = $dbh->prepare('INSERT INTO users (email, pass, payStatus) VALUES (?, ?, ?)');
                    $stmt->execute(array($value['email'], $value['pass'], $value['payStatus']));
                }
            }
            $dbh->commit();
            $dbh = null;
        }
        //
        // Clean up any duplicate new subscribers on the remote sites
        //
        if ($response['result'] == 'success') {
            $request = null;
            $response = null;
            $request['task'] = 'subscribersNewCleanUp';
            $response = soa($remote . 'z/', $request);
        }
    }
    //
    // Update changed remote subscriber records to the main database
    //
    $request = null;
    $response = null;
    $request['task'] = 'subscribersSyncSoaFlagged';
    $response = soa($remote . 'z/', $request);
    if (isset($response) and $response['result'] == 'success' and isset($response['remoteSubscribers'])) {
        $remoteSubscribers = json_decode($response['remoteSubscribers'], true);
        if ($remoteSubscribers == 'null' or $remoteSubscribers == null) {
            $remoteSubscribers = array();
        }
        foreach ($remoteSubscribers as $idUser) {
            $request = null;
            $response = null;
            $request['task'] = 'subscribersDownload';
            $request['idUser'] = $idUser;
            $response = soa($remote . 'z/', $request);
            if (isset($response) and $response['result'] = 'success' and isset($response['email'])) {
                extract($response);
                $dbh = new PDO($dbSubscribers);
                $stmt = $dbh->prepare('UPDATE users SET email=?, ipAddress=?, verified=?, pass=?, payStatus=?, note=?, contributor=?, classifiedOnly=?, deliver=?, deliveryAddress=?, dCityRegionPostal=?, billingAddress=?, bCityRegionPostal=?, evolve=?, expand=?, extend=? WHERE idUser=?');
                $stmt->execute(array($email, $ipAddress, $verified, $pass, $payStatus, $note, $contributor, $classifiedOnly, $deliver, $deliveryAddress, $dCityRegionPostal, $billingAddress, $bCityRegionPostal, $evolve, $expand, $extend, $idUser));
                $dbh = null;
            }
            $request = null;
            $response = null;
            $request['task'] = 'subscribersSoaUnflag';
            $request['idUser'] = $idUser;
            $response = soa($remote . 'z/', $request);
        }
    }
    //
    // Update changed subscriber records from the main database to the remotes
    //
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->prepare('SELECT idUser, email, ipAddress, verified, pass, payStatus, note, contributor, classifiedOnly, deliver, deliveryAddress, dCityRegionPostal, billingAddress, bCityRegionPostal, evolve, expand, extend FROM users WHERE soa = ?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array(1));
    foreach ($stmt as $row) {
        extract($row);
        $request = null;
        $response = null;
        $request['task'] = 'subscribersUpdate';
        $request['idUser'] = $idUser;
        $request['email'] = $email;
        $request['ipAddress'] = $ipAddress;
        $request['verified'] = $verified;
        $request['pass'] = $pass;
        $request['payStatus'] = $payStatus;
        $request['note'] = $note;
        $request['contributor'] = $contributor;
        $request['classifiedOnly'] = $classifiedOnly;
        $request['deliver'] = $deliver;
        $request['deliveryAddress'] = $deliveryAddress;
        $request['dCityRegionPostal'] = $dCityRegionPostal;
        $request['billingAddress'] = $billingAddress;
        $request['bCityRegionPostal'] = $bCityRegionPostal;
        $request['evolve'] = $evolve;
        $request['expand'] = $expand;
        $request['extend'] = $extend;
        $response = soa($remote . 'z/', $request);
        if ($response['result'] == 'success') {
            $stmt = $dbh->prepare('UPDATE users SET soa=? WHERE idUser=?');
            $stmt->execute(array(null, $idUser));
        }
    }
    $dbh = null;
    //
    // Determine the missing and extra subscribers
    //
    $request = null;
    $response = null;
    $request['task'] = 'subscribersSync';
    $response = soa($remote . 'z/', $request);
    $remoteSubscribers = json_decode($response['remoteSubscribers'], true);
    if ($remoteSubscribers == 'null' or $remoteSubscribers == null) {
        $remoteSubscribers = array();
    }
    $subscribers = array();
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->query('SELECT idUser FROM users');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        $subscribers[] = $row['idUser'];
    }
    $dbh = null;
    $missingSubscribers = array_diff($subscribers, $remoteSubscribers);
    $extraSubscribers = array_diff($remoteSubscribers, $subscribers);
    //
    // Upload missing subscribers to the remote sites
    //
    if (count($missingSubscribers) > 0) {
        foreach ($missingSubscribers as $idUser) {
            $request = null;
            $response = null;
            $request['task'] = 'subscribersUpload';
            $dbh = new PDO($dbSubscribers);
            $stmt = $dbh->prepare('SELECT idUser, email, pass, payStatus FROM users WHERE idUser=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute(array($idUser));
            foreach ($stmt as $row) {
                $request['idUser'] = $row['idUser'];
                $request['email'] = $row['email'];
                $request['pass'] = $row['pass'];
                $request['payStatus'] = $row['payStatus'];
                $response = soa($remote . 'z/', $request);
            }
            $dbh = null;
        }
    }
    //
    // When extra remote subscribers were found above, check again and delete the extra subscribers
    //
    if (count($extraSubscribers) > 0) {
        $request = null;
        $response = null;
        $request['task'] = 'subscribersSync';
        $response = soa($remote . 'z/', $request);
        $remoteSubscribers = json_decode($response['remoteSubscribers'], true);
        if ($remoteSubscribers == 'null' or $remoteSubscribers == null) {
            $remoteSubscribers = array();
        }
        $dbh = new PDO($dbSubscribers);
        $stmt = $dbh->query('SELECT idUser FROM users');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            $subscribers[] = $row['idUser'];
        }
        $dbh = null;
        $extraSubscribers = array_diff($remoteSubscribers, $subscribers);
        //
        // Delete extra remote subscribers
        //
        foreach ($extraSubscribers as $idUser) {
            $request = null;
            $response = null;
            $request['task'] = 'subscriberDelete';
            $request['idUser'] = $idUser;
            $response = soa($remote . 'z/', $request);
        }
    }
}
$dbhRemote = null;
?>
