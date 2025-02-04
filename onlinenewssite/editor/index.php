<?php
/**
 * Log in, when successful redirects to the appropriate page
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2025 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 * @version:  2025 02 03
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
$includesPath = '../' . $includesPath;
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
require $includesPath . '/editor/common.php';
require $includesPath . '/editor/createDatabases.php';
//
// Variables
//
$message = '';
$passPost = inlinePost('pass');
$userPost = inlinePost('user');
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT getAuthorization FROM getSecurity');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    extract($row);
    $uriPost = $uri . '?' . $getAuthorization;
} else {
    $getAuthorization = '';
    $uriPost = $uri;
}
//
// Optional $_GET authorization
//
if (!empty($getAuthorization)
    and !isset($_GET[$getAuthorization])
) {
    if (isset($_SERVER['HTTP_COOKIE'])) {
        setcookie(session_name(), '', time() - 90000);
        unset($_SERVER['HTTP_COOKIE']);
    }
    http_response_code(404);
    exit;
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
        $row = array_map('strval', $row);
        if (password_verify($passPost, $row['pass'])) {
            if (password_needs_rehash($row['pass'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($passPost, PASSWORD_DEFAULT);
                $dbh = new PDO($dbEditors);
                $stmt = $dbh->prepare('UPDATE users SET pass=? WHERE idUser=?');
                $stmt->execute([$newHash, $row['idUser']]);
                $dbh = null;
            }
            $dbh = new PDO($dbLogEditor);
            $stmt = $dbh->prepare('UPDATE login SET time=? WHERE user=?');
            $stmt->execute([null, $userPost]);
            $dbh = null;
            $_SESSION['auth'] = hash('sha256', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']) . hash('sha512', $row['user'] . $row['idUser']);
            $_SESSION['getAuthorization'] = $getAuthorization;
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
            //
            // Allow five failed log ins per user per hour
            //
            date_default_timezone_set('America/Los_Angeles');
            $now = time();
            $lastHour = $now - (60 * 60);
            $legibleTime = date("l, F j, Y, H:i:s", $now);
            $dbh = new PDO($dbLogEditor);
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
    $row = [];
    $row['name'] = '';
}
$request['name'] = $row['name'];
$request['uri'] = $uriScheme . '://' . $_SERVER["HTTP_HOST"] . '/';
$request['phpversion'] = phpversion();
$request['version'] = '2025 02 03';
$request = http_build_query($request);
stream_context_set_default(['http' => ['user_agent' => 'PHP', 'method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => $request]]);
$fp = @fopen('https://onlinenewssite.com/v/', 'rb', false);
if ($fp !== false) {
    $response = @stream_get_contents($fp);
}
//
// HTML
//
require $includesPath . '/editor/header1.inc';
?>
  <title>Online News Site</title>
  <link rel="icon" type="image/png" href="images/32.png">
  <link rel="stylesheet" type="text/css" href="z/base.css">
  <link rel="stylesheet" type="text/css" href="z/admin.css">
  <link rel="manifest" href="manifest.json">
  <link rel="apple-touch-icon" href="images/192.png">
</head>

<body>
  <div class="column">
    <p class="a"><br>
    <a href="https://onlinenewssite.com/"><img src="images/logo.png" class="wide" alt="Online News Site Software"></a></p>

    <h1 class="a">Editor log in</h1>
<?php echoIfMessage($message); ?>

    <form action="<?php echo $uriPost; ?>" method="post">
      <p class="a"><label for="user">User</label><br>
      <input id="user" name="user" class="h" maxlength="254" autofocus required></p>

      <p class="a"><label for="pass">Password</label><br>
      <input id="pass" name="pass" type="password" class="h" maxlength="254" required></p>

      <p class="a"><input type="submit" name="login" class="button" value="Log in"></p>
    </form>

<?php
//
// Alert for classified ads requiring review
//
$dbh = new PDO($dbClassifieds);
$stmt = $dbh->prepare('SELECT count(idAd) FROM ads WHERE review < ?');
$stmt->execute([time()]);
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$dbRowCount = $row['count(idAd)'];
$dbh = null;
if ($dbRowCount !== '0') {
    echo '    <p class="a">' . number_format($dbRowCount) . " classified ad(s) pending review.</p>\n\n";
}
?>
    <p class="a">Version 2025 02 03. By logging in, visitors consent to a cookie placed for the purpose of retaining the log in during website navigation.</p>
  </div>
</body>
</html>
