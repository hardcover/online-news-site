<?php
/**
 * The post portion of the program that registers and loggs in users
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
session_start();
session_regenerate_id(true);
require 'z/system/configuration.php';
require $includesPath . '/common.php';
require $includesPath . '/password_compat/password.php';
$uri = $uriScheme . '://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
//
// Exit if no post array
//
if (empty($_POST)) {
    header('Location: ' . $uri, true);
    exit;
}
//
// Variables
//
$emailPost = inlinePost('email');
$message = null;
$passOnePost = inlinePost('passOne');
$passPost = inlinePost('pass');
$passTwoPost = inlinePost('passTwo');
$verify = hash('sha1', mt_rand() . mt_rand() . mt_rand() . mt_rand());
$verifyPost = inlinePost('verify');
//
$headers = 'From: noreply@' . $_SERVER["HTTP_HOST"] . "\n";
$headers.= 'MIME-Version: 1.0' . "\n";
$headers.= 'Content-Type: text/plain; charset=utf-8; format=flowed' . "\n";
$headers.= 'Content-Transfer-Encoding: 7bit' . "\r\n";
//
$dbh = new PDO($dbSettings);
$stmt = $dbh->prepare('SELECT name FROM names WHERE idName=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array(1));
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    extract($row);
} else {
    $name = null;
}
//
// Allow five failed log ins per hour
//
if (isset($emailPost) and isset($passPost) and isset($_POST['login'])) {
    $now = time();
    $lastHour = $now - (60 * 60);
    $legibleTime = date("l, F j, Y, H:i:s", $now);
    $dbh = new PDO($dbLog);
    $stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "login" ("idUser" INTEGER PRIMARY KEY, "email", "legibleTime", ipAddress, "time" INTEGER)');
    $stmt = $dbh->prepare('INSERT INTO login (email, legibleTime, ipAddress, time) VALUES (?, ?, ?, ?)');
    $stmt->execute(array(muddle($emailPost), $legibleTime, $_SERVER['REMOTE_ADDR'], $now));
    $stmt = $dbh->prepare('SELECT count(*) FROM login WHERE email=? AND time > ?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array(muddle($emailPost), $lastHour));
    $row = $stmt->fetch();
    $dbh = null;
    if ($row['count(*)'] > 5) {
        include 'logout.php';
        exit;
    }
}
//
// Register or authenticate
//
if ((isset($_POST['login']) or isset($_POST['register'])) and isset($emailPost) and isset($passPost)) {
    //
    // Determine if the user is in one subscriber database or the other
    //
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->prepare('SELECT idUser, pass FROM users WHERE email=? LIMIT 1');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array(muddle($emailPost)));
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        $idUser = $row['idUser'];
    }
    $dbh = new PDO($dbSubscribersNew);
    $stmt = $dbh->prepare('SELECT idUser, pass, verified FROM users WHERE email=? LIMIT 1');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array(muddle($emailPost)));
    $rowNew = $stmt->fetch();
    $dbh = null;
    if ($rowNew) {
        $idUser = $rowNew['idUser'];
    }
    if ($row === false and $rowNew === false) {
        if (isset($_POST['register'])) {
            //
            // Register a new user
            //
            $dbh = new PDO($dbSubscribersNew);
            $stmt = $dbh->prepare('INSERT INTO users (email, pass, ipAddress, verify, time) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute(array(muddle($emailPost), password_hash($passPost, PASSWORD_DEFAULT), $_SERVER['REMOTE_ADDR'], $verify, time() + 900));
            $idUser = $dbh->lastInsertId();
            $dbh = null;
            $body = 'To continue registration, visit the link below within fifteen minutes from when registration was begun and from the same computer. If activation has not been completed by then, begin registration again.' . "\n\n";
            $body.= $uri . '?t=l&v=' . $verify . "\r\n";
            $subject = 'Confirm e-mail address at ' . $name . "\r\n";
            @mail($emailPost . "\r\n", $subject, $body, $headers);
            $message = 'Check your e-mail for a message from us. Visit the link in the e-mail to confirm the e-mail address and continue registration.';
        } else {
            //
            // Set message for failed log in attempt when the e-mail is not found
            //
            $message = 'Login credentials are incorrect.';
        }
    } else {
        if (password_verify($passPost, $row['pass']) or (password_verify($passPost, $rowNew['pass']) and $rowNew['verified'] == 1)) {
            //
            // Authentication
            //
            $dbh = new PDO($dbLog);
            $stmt = $dbh->prepare('UPDATE login SET time=? WHERE email=?');
            $stmt->execute(array(null, muddle($emailPost)));
            $dbh = null;
            $_SESSION['auth'] = hash('sha256', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']) . hash('sha512', $emailPost . $idUser);
            $_SESSION['userID'] = hash('sha512', $emailPost . $idUser);
            $_SESSION['userId'] = $idUser;
            if (isset($_SESSION['a'])) {
                header('Location: ' . $uri . 'news.php?a=' . $_SESSION['a'], true);
                exit;
            } else {
                header('Location: ' . $uri, true);
                exit;
            }
        } else {
            if (isset($_POST['register'])) {
                //
                // Set message for when a registration is begun again within the fifteen minute time for e-mail confirmation
                //
                $message = 'Check your e-mail for a message from us. Visit the link in the e-mail to confirm the e-mail address and continue registration.';
            } else {
                //
                // Set message for when the e-mail is found but the password is incorrect in a log in
                //
                $message = 'Login credentials are incorrect.';
            }
        }
    }
}
//
// Forgot password
//
if (isset($_POST['email']) and isset($_POST['forgot']) and isset($_POST['forgotPassword'])) {
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->prepare('SELECT idUser FROM users WHERE email=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array(muddle($emailPost)));
    $row = $stmt->fetch();
    if ($row) {
        $stmt = $dbh->prepare('UPDATE users SET ipAddress=?, verify=?, time=? WHERE idUser=?');
        $stmt->execute(array($_SERVER['REMOTE_ADDR'], $verify, time() + 900, $row['idUser']));
    }
    $dbh = null;
    $dbh = new PDO($dbSubscribersNew);
    $stmt = $dbh->prepare('SELECT idUser FROM users WHERE email=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array(muddle($emailPost)));
    $row = $stmt->fetch();
    if ($row) {
        $stmt = $dbh->prepare('UPDATE users SET ipAddress=?, verify=?, time=? WHERE idUser=?');
        $stmt->execute(array($_SERVER['REMOTE_ADDR'], $verify, time() + 900, $row['idUser']));
    }
    $dbh = null;
    $body = 'To change the password, visit the link below within fifteen minutes from when the request was made and from the same computer. If the password has not been completed by then, begin a new password change request.' . "\n\n";
    $body.= $uri . '?t=p&v=' . $verify . "\r\n";
    $subject = 'Password change request at ' . $name . "\r\n";
    @mail($emailPost . "\r\n", $subject, $body, $headers);
    $message = 'Check your e-mail for a message from us. Visit the link in the e-mail to change the password. Then return here to log in.';
}
//
// Reset password
//
if (isset($_POST['resetPassword']) and isset($verifyPost)) {
    if (is_null($passOnePost) or is_null($passTwoPost)) {
        $_SESSION['message'] = 'Both password fields are required.';
        header('Location: ' . $uri . '?t=p&v=' . $verifyPost, true);
        exit;
    } elseif ($passOnePost != $passTwoPost) {
        $_SESSION['message'] = 'The passwords did not match. Please try again.';
        header('Location: ' . $uri . '?t=p&v=' . $verifyPost, true);
        exit;
    } else {
        $newHash = password_hash($passOnePost, PASSWORD_DEFAULT);
        $dbh = new PDO($dbSubscribersNew);
        $stmt = $dbh->prepare('SELECT idUser FROM users WHERE verify=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(array($verifyPost));
        $row = $stmt->fetch();
        if ($row) {
            $stmt = $dbh->prepare('UPDATE users SET pass=? WHERE idUser=?');
            $stmt->execute(array($newHash, $row['idUser']));
        }
        $dbh = null;
        $dbh = new PDO($dbSubscribers);
        $stmt = $dbh->prepare('SELECT idUser FROM users WHERE verify=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(array($verifyPost));
        $row = $stmt->fetch();
        if ($row) {
            $stmt = $dbh->prepare('UPDATE users SET pass=?, soa=? WHERE idUser=?');
            $stmt->execute(array($newHash, 1, $row['idUser']));
        }
        $dbh = null;
    }
}
//
// Set session message
//
if ($message != null) {
    $_SESSION['message'] = $message;
}
header('Location: ' . $uri . '?t=l', true);
?>
