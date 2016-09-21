<?php
/**
 * The news home page
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2016 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *.@license   http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2016-09-19
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
if (!file_exists('images/logo.png')) {
    copy('images/evaluation.logo.png', 'images/logo.png');
}
require 'z/system/configuration.php';
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
//
// Articles
//
if (isset($_GET['a'])) {
    $aGet = secure($_GET['a']);
    $aGet = str_replace(strstr($aGet, '+'), '', $aGet);
} else {
    $aGet = null;
}
//
// Menu items
///
if (isset($_GET['m'])) {
    $mGet = secure($_GET['m']);
} else {
    $mGet = null;
}
//
// Tasks like login
//
if (isset($_GET['t'])) {
    $tGet = secure($_GET['t']);
} else {
    $tGet = null;
}
//
// Verification of registration
//
if (isset($_GET['v'])) {
    $vGet = secure($_GET['v']);
} else {
    $vGet = null;
}
//
// Get newspaper name and description
//
$dbh = new PDO($dbSettings);
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
//
$loginButtons= '    <form method="post" action="post.php">
      <span class="al"><input name="email" type="email" class="al" placeholder="E-mail" /> <input name="pass" type="password" class="al" placeholder="Password" /> <input name="login" type="submit" class="button" value="Log in" /> <input name="register" type="submit" class="button" value="Register" />&nbsp;</span>
    </form>' . "\n\n";
$logoutButtons= '    <form method="post" action="logout.php">
      <span class="al"><input type="submit" class="button" name="login" value="Log out" />&nbsp;</span>
    </form>' . "\n\n";
//
// Delete expired registrations and password change requests
//
$dbh = new PDO($dbSubscribersNew);
$stmt = $dbh->prepare('DELETE FROM users WHERE time < ? AND verified IS NULL');
$stmt->execute(array(time()));
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
    $stmt = $dbh->prepare('SELECT verify, ipAddress, payStatus FROM users WHERE verify=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($vGet));
    $row = $stmt->fetch();
    $dbh = null;
    if ($row and $row['ipAddress'] == $_SERVER['REMOTE_ADDR']) {
        $dbh = new PDO($dbSubscribersNew);
        $stmt = $dbh->prepare('UPDATE users SET verify=?, verified=? WHERE verify=?');
        $stmt->execute(array(null, 1, $vGet));
        $_SESSION['message'] = 'The e-mail address is confirmed. Log in to submit classified ads, subscribe to the news, etc. Visit <a class="n" href="' . $uri . '?m=my-account">My account</a> after logging in to set your account preferences.';
        $dbh = null;
    }
}
//
// HTML
//
require $includesPath . '/header1.inc';
echo '  <title>' . $name . "</title>\n";
echo '  <meta name="description" content="' . $description . '" />' . "\n";
echo '  <meta name="application-name" content="Online News Site http://online-news-site.com/" />' . "\n";
require $includesPath . '/header2Two.inc';
if (file_exists('z/local.css')) {
    echo '  <link rel="stylesheet" type="text/css" href="z/local.css" />' . "\n";
}
echo '</head>

<body>
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
if (file_exists($includesPath . '/custom/programs/home.php')) {
    include $includesPath . '/custom/programs/home.php';
}
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
} elseif (isset($aGet) and isset($mGet)) {
    include $includesPath . '/archive.php';
} elseif (isset($aGet)) {
    include $includesPath . '/displayArticleSEO.inc';
} elseif ($mGet == 'archive') {
    include $includesPath . '/archive.php';
} elseif ($mGet == 'article-contribution') {
    include $includesPath . '/articleContribution.php';
} elseif ($mGet == 'classified-ads') {
    include $includesPath . '/classifieds.php';
} elseif ($mGet == 'calendar') {
    include $includesPath . '/calendar.php';
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
        $test = substr($menuContent, 0, 8);
        if ($test == 'require ') {
            //
            // Custom programs
            //
            $program = str_replace($test, '', $menuContent);
            include $includesPath . '/custom/programs/' . $program;
        } else {
            //
            // Standard menu content
            //
            $content = Parsedown::instance()->parse($menuContent);
            $content = str_replace("\n", "\n    ", $content);
            echo '    <h1>' . $menuName . "</h1>\n";
            echo '    ' . $content . "\n";
        }
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
} elseif (isset($tGet) and $tGet == 'pay') {
    //
    // Payment for subscription
    //
    if (file_exists($includesPath . '/custom/programs/pay.php')) {
        include $includesPath . '/custom/programs/pay.php';
    }
} else {
    //
    // Log in / Register
    //
    include $includesPath . '/login.php';
}
echo "  </div>\n";
if (file_exists($includesPath . '/custom/programs/footer.php')) {
    include $includesPath . '/custom/programs/footer.php';
}
?>
</body>
</html>