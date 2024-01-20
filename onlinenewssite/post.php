<?php
/**
 * The post portion of the program that registers and loggs in users
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2024 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2024 01 19
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
session_start();
session_regenerate_id(true);
require 'editor/z/system/configuration.php';
require $includesPath . '/editor/common.php';
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
$message = '';
$passOnePost = inlinePost('passOne');
$passPost = inlinePost('pass');
$passTwoPost = inlinePost('passTwo');
$verify = hash('sha1', mt_rand() . mt_rand() . mt_rand() . mt_rand());
$verifyPost = inlinePost('verify');
//
$headers = 'From: noreply@' . $_SERVER["HTTP_HOST"] . "\r\n";
$headers.= 'MIME-Version: 1.0' . "\r\n";
$headers.= 'Content-Type: text/plain; charset=utf-8; format=flowed' . "\r\n";
$headers.= 'Content-Transfer-Encoding: 7bit' . "\r\n";
//
$dbh = new PDO($dbSettings);
$stmt = $dbh->prepare('SELECT name FROM names WHERE idName=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([1]);
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
    $dbh = new PDO($dbLogSubscriber);
    $stmt = $dbh->prepare('INSERT INTO login (email, legibleTime, ipAddress, time) VALUES (?, ?, ?, ?)');
    $stmt->execute([muddle($emailPost), $legibleTime, $_SERVER['REMOTE_ADDR'], $now]);
    $stmt = $dbh->prepare('SELECT count(*) FROM login WHERE email=? AND time > ?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([muddle($emailPost), $lastHour]);
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
if ((isset($_POST['login'])
    or isset($_POST['register']))
    and isset($emailPost)
    and isset($passPost)
) {
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([muddle($emailPost)]);
    $row = $stmt->fetch();
    $dbh = null;
    $rowSubscribers = null;
    if ($row) {
        $row = array_map('strval', $row);
        $rowSubscribers = $row;
        $idUser = $row['idUser'];
        $database = 's';
    }
    if (is_null($rowSubscribers)) {
        //
        // Register a new subscriber
        //
        if (isset($_POST['register'])) {
            $hashPass = password_hash($passPost, PASSWORD_DEFAULT);
            $dbh = new PDO($dbSubscribers);
            $stmt = $dbh->prepare('INSERT INTO users (email, pass, ipAddress, verify, time) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([muddle($emailPost), $hashPass, $_SERVER['REMOTE_ADDR'], $verify, time() + 172800]);
            $idUser = $dbh->lastInsertId();
            $dbh = null;
            $body = 'To continue registration, visit the link below within forty-eight hours from when registration was begun and from the same computer. If activation has not been completed by then, then begin registration again.' . "\n\n";
            $body.= $uri . '?t=l&v=' . $verify . "\r\n";
            $subject = 'Confirm email address at ' . $name . "\r\n";
            mail($emailPost . "\r\n", $subject, $body, $headers);
            $message = 'Check your email for a message from us. Visit the link in the email to confirm the email address and continue registration.<br><br>The website does not use cookies except for logged-in users. By logging in, visitors consent to a cookie placed for the purpose of retaining the log in during website navigation.';
        } else {
            //
            // Set message for failed log in attempt when the email is not found
            //
            $message = 'Login credentials are incorrect.';
        }
    }
    //
    // Authentication variables
    //
    if (isset($rowSubscribers['pass'])) {
        $subscribersPass = $rowSubscribers['pass'];
    } else {
        $subscribersPass = '';
    }
    //
    if (isset($rowSubscribers['verified'])) {
        $subscribersVerified = $rowSubscribers['verified'];
    } else {
        $subscribersVerified = '';
    }
    //
    // Authenticate
    //
    if ((password_verify($passPost, $subscribersPass))
        and $subscribersVerified === '1'
    ) {
        //
        // Update the log database
        //
        $dbh = new PDO($dbLogSubscriber);
        $stmt = $dbh->prepare('UPDATE login SET time=? WHERE email=?');
        $stmt->execute([null, muddle($emailPost)]);
        $dbh = null;
        if ($rowSubscribers['payStatus'] >= time()) {
            $paid = 1;
        } else {
            $paid = 0;
        }
        //
        // Update the stored password when needed and possible
        //
        if ($database === 's') {
            if (password_needs_rehash($subscribersPass, PASSWORD_DEFAULT)) {
                $newHash = password_hash($passPost, PASSWORD_DEFAULT);
                $dbh = new PDO($dbSubscribers);
                $stmt = $dbh->prepare('UPDATE users SET pass=? WHERE idUser=?');
                $stmt->execute([$newHash, $idUser]);
                $dbh = null;
            }
        }
        //
        // Set the session variables
        //
        $_SESSION['auth'] = hash('sha256', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']) . hash('sha512', $emailPost . $idUser);
        $_SESSION['paid'] = $paid;
        $_SESSION['userID'] = hash('sha512', $emailPost . $idUser);
        $_SESSION['userId'] = $idUser;
        //
        // Check for a pay requirement for articles
        //
        if (isset($_SESSION['a'])
            and $freeOrPaid === 'paid'
            and $row['payStatus'] < time()
        ) {
            header('Location: ' . $uri . '?t=pay');
            exit;
        }
        //
        // Send to the selected article
        //
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
            // Set message for when a registration is begun again within the time limit for email confirmation
            //
            $message = 'Check your email for a message from us. Visit the link in the email to confirm the email address and continue registration.<br><br>The website does not use cookies except for logged-in users. By logging in, visitors consent to a cookie placed for the purpose of retaining the log in during website navigation.';
        } else {
            //
            // Set message for when the email is found but the password is incorrect in a log in
            //
            $message = 'Login credentials are incorrect.';
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
    $stmt->execute([muddle($emailPost)]);
    $row = $stmt->fetch();
    if ($row) {
        $stmt = $dbh->prepare('UPDATE users SET ipAddress=?, verify=?, time=? WHERE idUser=?');
        $stmt->execute([$_SERVER['REMOTE_ADDR'], $verify, time() + 900, $row['idUser']]);
    }
    $dbh = null;
    $body = 'To change the password, visit the link below within fifteen minutes from when the request was made and from the same computer. If the password has not been completed by then, begin a new password change request.' . "\n\n";
    $body.= $uri . '?t=p&v=' . $verify . "\r\n";
    $subject = 'Password change request at ' . $name . "\r\n";
    @mail($emailPost . "\r\n", $subject, $body, $headers);
    $message = 'Check your email for a message from us. Visit the link in the email to change the password. Then return here to log in.';
}
//
// Reset password
//
if (isset($_POST['resetPassword']) and isset($verifyPost)) {
    if (empty($passOnePost) or empty($passTwoPost)) {
        $_SESSION['message'] = 'Both password fields are required.';
        header('Location: ' . $uri . '?t=p&v=' . $verifyPost, true);
        exit;
    } elseif ($passOnePost !== $passTwoPost) {
        $_SESSION['message'] = 'The passwords did not match. Please try again.';
        header('Location: ' . $uri . '?t=p&v=' . $verifyPost, true);
        exit;
    } else {
        $newHash = password_hash($passOnePost, PASSWORD_DEFAULT);
        $dbh = new PDO($dbSubscribers);
        $stmt = $dbh->prepare('SELECT idUser FROM users WHERE verify=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$verifyPost]);
        $row = $stmt->fetch();
        if ($row) {
            $stmt = $dbh->prepare('UPDATE users SET pass=? WHERE idUser=?');
            $stmt->execute([$newHash, $row['idUser']]);
        }
        $dbh = null;
    }
}
//
// Set session message
//
if ($message !== null) {
    $_SESSION['message'] = $message;
}
header('Location: ' . $uri . '?t=l', true);
?>
