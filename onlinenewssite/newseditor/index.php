<?php
/**
 * Log in, when successful redirects to the appropriate page
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2021 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2021 4 20
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
@session_start();
session_regenerate_id(true);
//
// Check for existing configuration file, create one if not found
//
if (!file_exists('z/system/configuration.php')) {
    copy('z/system/configuration.inc', 'z/system/configuration.php');
}
//
// Reset authorization
//
require 'z/system/configuration.php';
//
// Optional $_GET authorization
//
if (empty($_POST['login']) or $_POST['login'] !== 'Log in') {
    if (file_exists($includesPath . '/custom/programs/getAuthorization.php')) {
        include $includesPath . '/custom/programs/getAuthorization.php';
        if (isset($_GET['k'])) {
            $kGet = filter_var($_GET['k'], FILTER_SANITIZE_STRING);
        } else {
            $kGet = null;
        }
        if ($kGet !== $getAuthorization) {
            header_remove();
            http_response_code(404);
            exit;
        }
    }
}
//
// Log out if logged in
//
$uri = $uriScheme . '://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
if (isset($_SESSION['auth'])) {
    include 'logout.php';
    exit;
}
//
// Requires
//
require $includesPath . '/common.php';
//
// Variables
//
$message = null;
$passPost = inlinePost('pass');
$userPost = inlinePost('user');
//
// Create the databases on the first run
//
require $includesPath . '/createCrypt.php';
require $includesPath . '/createEditor.php';
require $includesPath . '/createSubscriber1.php';
//
// Allow five failed log ins per user per hour
//
if (isset($userPost, $passPost)) {
    date_default_timezone_set('America/Los_Angeles');
    $now = time();
    $lastHour = $now - (60 * 60);
    $legibleTime = date("l, F j, Y, H:i:s", $now);
    $dbh = new PDO($dbLog);
    $stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "login" ("idUser" INTEGER PRIMARY KEY, "user", "legibleTime", ipAddress, "time" INTEGER)');
    $stmt = $dbh->prepare('INSERT INTO login (user, legibleTime, ipAddress, time) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userPost, $legibleTime, $_SERVER['REMOTE_ADDR'], $now]);
    $stmt = $dbh->prepare('SELECT count(*) FROM login WHERE user=? AND time > ?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$userPost, $lastHour]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row['count(*)'] > 5) {
        include 'logout.php';
    }
}
//
// Authenticate
//
if (isset($_POST['login'], $userPost, $passPost)) {
    $dbh = new PDO($dbEditors);
    $stmt = $dbh->prepare('SELECT idUser, user, pass, fullName, userType FROM users WHERE user=? LIMIT 1');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$userPost]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        if (password_verify($passPost, $row['pass'])) {
            if (password_needs_rehash($row['pass'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($passPost, PASSWORD_DEFAULT);
                $dbh = new PDO($dbEditors);
                $stmt = $dbh->prepare('UPDATE users SET pass=? WHERE idUser=?');
                $stmt->execute([$newHash, $row['idUser']]);
                $dbh = null;
            }
            $dbh = new PDO($dbLog);
            $stmt = $dbh->prepare('UPDATE login SET time=? WHERE user=?');
            $stmt->execute([null, $userPost]);
            $dbh = null;
            $_SESSION['auth'] = hash('sha256', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']) . hash('sha512', $row['user'] . $row['idUser']);
            $_SESSION['userID'] = hash('sha512', $row['user'] . $row['idUser']);
            $_SESSION['userId'] = $row['idUser'];
            $_SESSION['username'] = $row['user'];
            if (strval($row['user']) === strval('admin')) {
                header('Location: ' . $uri . 'usersEditors.php');
                exit;
            } elseif ($row['userType'] === '1') {
                header('Location: ' . $uri . 'edit.php');
                exit;
            } elseif ($row['userType'] === '2') {
                header('Location: ' . $uri . 'subscribers.php');
                exit;
            } elseif ($row['userType'] === '3') {
                header('Location: ' . $uri . 'advertisingPublished.php');
                exit;
            } elseif ($row['userType'] === '4') {
                header('Location: ' . $uri . 'classifieds.php');
                exit;
            } elseif ($row['userType'] === '5') {
                header('Location: ' . $uri . 'menu.php');
                exit;
            }
        } else {
            $message = 'Login credentials are incorrect.';
        }
    }
}
//
// Check for online news site software update
//
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT name FROM names');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$dbh = null;
if ($row === false) {
    $row['name'] = null;
}
$request['name'] = $row['name'];
$request['remotes'] = null;
$dbh = new PDO($dbRemote);
$stmt = $dbh->query('SELECT remote FROM remotes');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    $request['remotes'].= $row['remote'] . ' ';
}
$dbh = null;
$request['phpversion'] = phpversion();
$request['version'] = '2021 4 20';
$request = http_build_query(array_map('base64_encode', $request));
stream_context_set_default(['http' => ['user_agent' => 'PHP', 'method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => $request]]);
$fp = @fopen('https://onlinenewssite.com/v/', 'rb', false);
if ($fp !== false) {
    $response = @stream_get_contents($fp);
}
//
// HTML
//
require $includesPath . '/header1.inc';
?>
  <title>Online News Site</title>
  <meta name="generator" content="Online News Site, free open source news publishing software, https://onlinenewssite.com/" />
<?php require $includesPath . '/header2.inc'; ?>
<body>
  <p><br />
  <a href="https://onlinenewssite.com/"><img src="images/logo.png" class="logo" alt="Online news site free open source software" /></a></p>

  <h1>News editor log in</h1>
<?php echoIfMessage($message); ?>

  <form action="<?php echo $uri; ?>" method="post">
    <p><label for="user">User</label><br />
    <input id="user" name="user" type="text" class="h" maxlength="254" autofocus required /></p>

    <p><label for="pass">Password</label><br />
    <input id="pass" name="pass" type="password" class="h" maxlength="254" required /></p>

    <p><input type="submit" name="login" class="button" value="Log in" /></p>
  </form>

<?php
//
// Alert for classified ads requiring review
//
require $includesPath . '/syncClassifiedsNew.php';
$dbh = new PDO($dbClassifieds);
$stmt = $dbh->query('SELECT count(idAd) FROM ads WHERE review IS NULL');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$dbRowCount = $row['count(idAd)'];
$dbh = null;
if ($dbRowCount !== '0') {
    echo '  <p>' . number_format($dbRowCount) . " classified ad(s) pending review.</p>\n\n";
}
?>
  <p>Version 2021 4 20. By logging in, visitors consent to a cookie placed for the purpose of retaining the log in during website navigation.</p>
</body>
</html>
