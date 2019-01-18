<?php
/**
 * Synchronizes the remote and local databases
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2019 01 18
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
                $stmt->execute([$value['email']]);
                $row = $stmt->fetch();
                if ($row === false) {
                    $stmt = $dbh->prepare('INSERT INTO users (email, payerEmail, payerFirstName, payerLastName, ipAddress, verify, verified, time, pass, payStatus, paid, paymentDate, note, contributor, classifiedOnly, deliver, deliver2, deliveryAddress, dCityRegionPostal, billingAddress, bCityRegionPostal, soa, evolve, expand, extend) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$value['email'], $value['payerEmail'], $value['payerFirstName'], $value['payerLastName'], $value['ipAddress'], $value['verify'], $value['verified'], $value['time'], $value['pass'], $value['payStatus'], $value['paid'], $value['paymentDate'], $value['note'], $value['contributor'], $value['classifiedOnly'], $value['deliver'], $value['deliver2'], $value['deliveryAddress'], $value['dCityRegionPostal'], $value['billingAddress'], $value['bCityRegionPostal'], $value['soa'], $value['evolve'], $value['expand'], $value['extend']]);
                }
            }
            $dbh->commit();
            $dbh = null;
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
            $remoteSubscribers = [];
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
                $stmt = $dbh->prepare('UPDATE users SET email=?, payerEmail=?, payerFirstName=?, payerLastName=?, ipAddress=?, verify=?, verified=?, time=?, pass=?, payStatus=?, paid=?, paymentDate=?, note=?, contributor=?, classifiedOnly=?, deliver=?, deliver2=?, deliveryAddress=?, dCityRegionPostal=?, billingAddress=?, bCityRegionPostal=?, soa=?, evolve=?, expand=?, extend=? WHERE idUser=?');
                $stmt->execute([$email, $payerEmail, $payerFirstName, $payerLastName, $ipAddress, $verify, $verified, $time, $pass, $payStatus, $paid, $paymentDate, $note, $contributor, $classifiedOnly, $deliver, $deliver2, $deliveryAddress, $dCityRegionPostal, $billingAddress, $bCityRegionPostal, $soa, $evolve, $expand, $extend, $idUser]);
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
    $stmt->execute([1]);
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
            $stmt->execute([null, $idUser]);
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
        $remoteSubscribers = [];
    }
    $subscribers = [];
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
            $stmt = $dbh->prepare('SELECT idUser, email, payerEmail, payerFirstName, payerLastName, ipAddress, verify, verified, time, pass, payStatus, paid, paymentDate, note, contributor, classifiedOnly, deliver, deliver2, deliveryAddress, dCityRegionPostal, billingAddress, bCityRegionPostal, soa, evolve, expand, extend FROM users WHERE idUser=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$idUser]);
            foreach ($stmt as $row) {
                $request['idUser'] = $row['idUser'];
                $request['email'] = $row['email'];
                $request['payerEmail'] = $row['payerEmail'];
                $request['payerFirstName'] = $row['payerFirstName'];
                $request['payerLastName'] = $row['payerLastName'];
                $request['ipAddress'] = $row['ipAddress'];
                $request['verify'] = $row['verify'];
                $request['verified'] = $row['verified'];
                $request['time'] = $row['time'];
                $request['pass'] = $row['pass'];
                $request['payStatus'] = $row['payStatus'];
                $request['paid'] = $row['paid'];
                $request['paymentDate'] = $row['paymentDate'];
                $request['note'] = $row['note'];
                $request['contributor'] = $row['contributor'];
                $request['classifiedOnly'] = $row['classifiedOnly'];
                $request['deliver'] = $row['deliver'];
                $request['deliver2'] = $row['deliver2'];
                $request['deliveryAddress'] = $row['deliveryAddress'];
                $request['dCityRegionPostal'] = $row['dCityRegionPostal'];
                $request['billingAddress'] = $row['billingAddress'];
                $request['bCityRegionPostal'] = $row['bCityRegionPostal'];
                $request['soa'] = $row['soa'];
                $request['evolve'] = $row['evolve'];
                $request['expand'] = $row['expand'];
                $request['extend'] = $row['expand'];
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
            $remoteSubscribers = [];
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
?>
