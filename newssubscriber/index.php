<?php
/**
 * The news home page
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
@session_start();
//
// Check for existing custom files, create if not found
//
if (!file_exists('z/system/configuration.php')) {
    copy('z/system/configuration.inc', 'z/system/configuration.php');
}
if (!file_exists('z/base.css')) {
    copy('z/evaluation.base.css', 'z/base.css');
}
if (!file_exists('images/logo.png')) {
    copy('images/evaluation.logo.png', 'images/logo.png');
}
require 'z/system/configuration.php';
require $includesPath . '/password_compat/password.php';
require $includesPath . '/parsedown-master/Parsedown.php';
$uri = $uriScheme . '://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
//
// Create the databases on the first run
//
require $includesPath . '/common.php';
require $includesPath . '/createSubscriber1.php';
require $includesPath . '/createSubscriber2.php';
require $includesPath . '/createCrypt.php';
//
// Variables
//
$adSort = array();
$anchorPath = null;
$database = $dbPublished;
$datePost = $today;
$editorView = null;
$imagePath = 'images.php';
$indexPath = '';
$use = 'news';
if (isset($_GET['m'])) {
    $mGet = secure($_GET['m']);
} else {
    $mGet = null;
}
if (isset($_GET['t'])) {
    $tGet = secure($_GET['t']);
} else {
    $tGet = null;
}
if (isset($_GET['v'])) {
    $vGet = secure($_GET['v']);
} else {
    $vGet = null;
}
//
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT access FROM archiveAccess');
$row = $stmt->fetch();
if ($row) {
    extract($row);
    if ($access == 1) {
        $links = '<a class="n" href="' . $uri . 'archive.php">Archives</a> | ';
    }
} else {
    $links = null;
}
//
$stmt = $dbh->prepare('SELECT name, description FROM names WHERE idName=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array(1));
$row = $stmt->fetch();
$dbh = null;
//
if ($row) {
    extract($row);
} else {
    $name = null;
    $description = null;
}
$loginButtons= '    <form method="post" action="post.php">
      <span class="al"><input name="email" type="email" class="al" placeholder="E-mail" /> <input name="pass" type="password" class="al" placeholder="Password" /> <input name="login" type="submit" class="left" value="Log in" /><input name="register" type="submit" class="right" value="Register" />&nbsp;</span>
    </form>' . "\n\n";
$logoutButtons= '    <form method="post" action="logout.php">
      <span class="al"><input class="button" type="submit" name="login" value="Log out" />&nbsp;</span>
    </form>' . "\n\n";
//
// Delete expired registrations and password change requests
//
$dbh = new PDO($dbSubscribersNew);
$stmt = $dbh->prepare('DELETE FROM users WHERE time < ? AND verified != ?');
$stmt->execute(array(time(), 1));
$stmt = $dbh->prepare('UPDATE users SET verify=? WHERE time < ? AND verified = ?');
$stmt->execute(array(null, time(), 1));
$dbh = null;
$dbh = new PDO($dbSubscribers);
$stmt = $dbh->prepare('UPDATE users SET verify=? WHERE time < ? AND verified = ?');
$stmt->execute(array(null, time(), 1));
$dbh = null;
//
// Activate a registration
//
if (isset($tGet) and $tGet == 'l' and isset($vGet)) {
    $dbh = new PDO($dbSubscribersNew);
    $stmt = $dbh->prepare('SELECT verify, ipAddress FROM users WHERE verify=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($vGet));
    $row = $stmt->fetch();
    if ($row and $row['ipAddress'] == $_SERVER['REMOTE_ADDR']) {
        $stmt = $dbh->prepare('UPDATE users SET verified=? WHERE verify=?');
        $stmt->execute(array(1, $vGet));
        $_SESSION['message'] = 'The e-mail address is confirmed. Log in to submit classified ads, subscribe to the news, etc. Visit <a class="n" href="' . $uri . '?m=my-account">My account</a> after logging in to set your account preferences.';
    }
    $dbh = null;
}
//
// HTML
//
require $includesPath . '/header1.inc';
echo '  <title>' . $name . "</title>\n";
echo '  <meta name="description" content="' . $description . '" />' . "\n";
require $includesPath . '/header2Two.inc';
echo '<body>
  <div class="h">' . "\n";
if (!isset($_SESSION['auth'])) {
    echo $loginButtons;
} else {
    echo $logoutButtons;
}
echo '    <div class="logo">
      <a href="./"><img src="images/logo.png" class="logo" alt="'. $name . '" /></a>
    </div>
  </div>' . "\n\n";
//
// Right column, menu
//
echo '  <div class="r">' . "\n";
echo '    <h5>' . $description . "</h5>\n\n";
echo '    <h5>';
if (isset($_SESSION['auth'])) {
    //
    // My account
    //
    echo '<a class="n" href="' . $uri . '?m=my-account">My account</a><br />' . "\n";
    //
    // Article contribution
    //
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->prepare('SELECT contributor FROM users WHERE idUser=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($_SESSION['userId']));
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        if ($row['contributor'] == 1) {
            echo '    <a class="n" href="' . $uri . '?m=article-contribution">Article contribution</a><br />' . "\n";
        }
    }
}
//
// Archives
//
if (isset($access) and $access == 1) {
    if ($freeOrPaid == 'free' or isset($_SESSION['auth'])) {
        echo '    <a class="n" href="' . $uri . 'archive.php">Archives</a><br />' . "\n";
    }
}
//
// Classifieds
//
//echo '    <a class="n" href="' . $uri . '?m=classified-ads">Classifieds</a><br />' . "\n";
//
// Custom menu
//
$dbh = new PDO($dbMenu);
$stmt = $dbh->query('SELECT menuName, menuPath, menuAuthorization FROM menu ORDER BY menuSortOrder');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    extract($row);
    echo '    <a class="n" href="' . $uri . '?m=' . $menuPath . '">' . $menuName . '</a><br />' . "\n";
}
$dbh = null;
echo "    </h5>\n";
//
// Right column, ads
//
$dbh = new PDO($dbAdvertising);
$stmt = $dbh->prepare('SELECT maxAds FROM maxAd WHERE idMaxAds=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array(1));
$row = $stmt->fetch();
if ($row) {
    $maxAds = $row['maxAds'];
}
$stmt = $dbh->prepare('SELECT idAd, sortOrderAd FROM advertisements WHERE (? >= startDateAd AND ? <= endDateAd) ORDER BY sortOrderAd');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array($today, $today));
foreach ($stmt as $row) {
    extract($row);
    if (empty($sortOrderAd)) {
        $adSort[mt_rand()] = $idAd;
    } else {
        $adSort[$sortOrderAd] = $idAd;
    }
}
$dbh = null;
ksort($adSort);
if (isset($maxAds)) {
    $i = null;
    foreach ($adSort as $key => $value) {
        $i++;
        if ($i <= $maxAds) {
            $adSortTemp[$key] = $value;
        }
    }
    $adSort = $adSortTemp;
    $adSortTemp = null;
}
$dbh = new PDO($dbAdvertising);
foreach ($adSort as $idAd) {
    $stmt = $dbh->prepare('SELECT link, linkAlt FROM advertisements WHERE idAd=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idAd));
    $row = $stmt->fetch();
    if ($row) {
        extract($row);
        if ($link != null and $link != '') {
            $linkHtml1 = '<a href="' . html($link) . '" target="_blank" rel="nofollow">';
            $linkHtml2 = '</a>';
        } else {
            $linkHtml1 = $linkHtml2 = null;
        }
        echo '    ' . $linkHtml1 . '<img class="w b" src="imaged.php?i=' . muddle($idAd) . '" alt="' . $linkAlt . '" />' . $linkHtml2 . '<br />' . "\n";
    }
}
$dbh = null;
echo '  </div>' . "\n\n";
//
// Left column
//
echo '  <div class="l">' . "\n";
if (empty($_GET)) {
    include $includesPath . '/displayIndex.inc';
} elseif ($mGet == 'article-contribution') {
    include $includesPath . '/articleContribution.php';
} elseif ($mGet == 'classified-ads') {
    include $includesPath . '/classifieds.php';
} elseif ($mGet == 'my-account') {
    include $includesPath . '/myAccount.php';
} elseif ($mGet == 'place-classified') {
    include $includesPath . '/placeClassified.php';
} elseif (isset($mGet)) {
    $dbh = new PDO($dbMenu);
    $stmt = $dbh->prepare('SELECT menuName, menuContent FROM menu WHERE menuPath=?');
    $stmt->execute(array($mGet));
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        extract($row);
        $content = Parsedown::instance()->parse($menuContent);
        $content = str_replace("\n", "\n    ", $content);
        echo '    <h1>' . $menuName . "</h1>\n";
        echo '    ' . $content . "\n";
    }
} elseif (isset($tGet) and $tGet == 'c') {
    //
    // Forgot password
    //
    include $includesPath . '/passwordForgot.php';
} elseif (isset($tGet) and $tGet == 'p' and isset($vGet)) {
    //
    // Reset password
    //
    include $includesPath . '/passwordReset.php';
} else {
    //
    // Log in / Register
    //
    include $includesPath . '/login.php';
}
?>
  </div>
</body>
</html>
