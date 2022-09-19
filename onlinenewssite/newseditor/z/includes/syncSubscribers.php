<?php
/**
 * Synchronizes the remote and local databases
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
    $request = [];
    $response = [];
    $request['task'] = 'subscribersNewDownload';
    $response = soa($remote . 'z/', $request);
    if ($response['result'] === 'success') {
        //
        // Merge new subscribers to the main subscriber database
        //
        if (!empty($response['dbRows'])) {
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
    $request = [];
    $response = [];
    $request['task'] = 'subscribersSyncSoaFlagged';
    $response = soa($remote . 'z/', $request);
    if (isset($response) and $response['result'] === 'success' and isset($response['remoteSubscribers'])) {
        $remoteSubscribers = json_decode($response['remoteSubscribers'], true);
        if ($remoteSubscribers === 'null' or $remoteSubscribers === null) {
            $remoteSubscribers = [];
        }
        foreach ($remoteSubscribers as $idUser) {
            $request = [];
            $response = [];
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
            $request = [];
            $response = [];
            $request['task'] = 'subscribersSoaUnflag';
            $request['idUser'] = $idUser;
            $response = soa($remote . 'z/', $request);
        }
    }
    //
    // Update changed subscriber records from the main database to the remotes
    //
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->prepare('SELECT idUser, email, payerEmail, payerFirstName, payerLastName, ipAddress, verify, verified, time, pass, payStatus, paid, paymentDate, note, contributor, classifiedOnly, deliver, deliver2, deliveryAddress, dCityRegionPostal, billingAddress, bCityRegionPostal, evolve, expand, extend FROM users WHERE soa = ?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([1]);
    foreach ($stmt as $row) {
        extract($row);
        $request = [];
        $response = [];
        $request['task'] = 'subscribersUpdate';
        $request['idUser'] = $idUser;
        $request['email'] = $email;
        $request['payerEmail'] = $payerEmail;
        $request['payerFirstName'] = $payerFirstName;
        $request['payerLastName'] = $payerLastName;
        $request['ipAddress'] = $ipAddress;
        $request['verify'] = $verify;
        $request['verified'] = $verified;
        $request['time'] = $time;
        $request['pass'] = $pass;
        $request['payStatus'] = $payStatus;
        $request['paid'] = $paid;
        $request['paymentDate'] = $paymentDate;
        $request['note'] = $note;
        $request['contributor'] = $contributor;
        $request['classifiedOnly'] = $classifiedOnly;
        $request['deliver'] = $deliver;
        $request['deliver2'] = $deliver2;
        $request['deliveryAddress'] = $deliveryAddress;
        $request['dCityRegionPostal'] = $dCityRegionPostal;
        $request['billingAddress'] = $billingAddress;
        $request['bCityRegionPostal'] = $bCityRegionPostal;
        $request['evolve'] = $evolve;
        $request['expand'] = $expand;
        $request['extend'] = $extend;
        $response = soa($remote . 'z/', $request);
        if ($response['result'] === 'success') {
            $stmt = $dbh->prepare('UPDATE users SET soa=? WHERE idUser=?');
            $stmt->execute([null, $idUser]);
        }
    }
    $dbh = null;
    //
    // Determine the missing and extra subscribers
    //
    $request = [];
    $response = [];
    $request['task'] = 'subscribersSync';
    $response = soa($remote . 'z/', $request);
    $remoteSubscribers = json_decode($response['remoteSubscribers'], true);
    if ($remoteSubscribers === 'null' or $remoteSubscribers === null) {
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
            $request = [];
            $response = [];
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
        $request = [];
        $response = [];
        $request['task'] = 'subscribersSync';
        $response = soa($remote . 'z/', $request);
        $remoteSubscribers = json_decode($response['remoteSubscribers'], true);
        if ($remoteSubscribers === 'null' or $remoteSubscribers === null) {
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
            $request = [];
            $response = [];
            $request['task'] = 'subscriberDelete';
            $request['idUser'] = $idUser;
            $response = soa($remote . 'z/', $request);
        }
    }
}
?>
