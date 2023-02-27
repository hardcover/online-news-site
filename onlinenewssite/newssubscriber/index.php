<?php
/**
 * The news home page
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2021 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2023 02 27
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
@session_start();
//
// Check for existing configuration file and logo, create if not found
//
if (!file_exists('z/system/configuration.php')) {
    copy('z/system/configuration.inc', 'z/system/configuration.php');
}
if (!file_exists('images/logo.png')) {
    copy('images/evaluation.logo.png', 'images/logo.png');
}
//
// Requires
//
require 'z/system/configuration.php';
require $includesPath . '/common.php';
require $includesPath . '/parsedown-master/Parsedown.php';
$uri = $uriScheme . '://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
//
// Create the databases on the first run
//
require $includesPath . '/createSubscriber1.php';
require $includesPath . '/createSubscriber2.php';
require $includesPath . '/createCrypt.php';
//
// Variables
//
$adSort = [];
$anchorPath = null;
$database = $dbPublished;
$datePost = $today;
$editorView = null;
$imagePath = 'images.php';
$indexPath = '';
$payNowPost = inlinePost('payNow');
$use = 'news';
//
// Article variable
//
if (!empty($_GET['a'])) {
    $aGet = secure($_GET['a']);
    $aGet = str_replace(strstr($aGet, '+'), '', $aGet);
    $aGet = str_replace(strstr($aGet, ' '), '', $aGet);
} else {
    $aGet = null;
}
//
// Menu variable
//
if (isset($_GET['m'])) {
    $mGet = secure($_GET['m']);
} else {
    $mGet = null;
}
//
// Task variable
//
if (isset($_GET['t'])) {
    $tGet = secure($_GET['t']);
} else {
    $tGet = null;
}
//
// Registration verification variable
//
if (isset($_GET['v'])) {
    $vGet = secure($_GET['v']);
} else {
    $vGet = null;
}
//
// $payNow
//
if (!empty($payNowPost)) {
    header('Location: ' . $uri . '?t=pay');
    exit;
}
//
// Get newspaper name and description
//
$dbh = new PDO($dbSettings);
$stmt = $dbh->prepare('SELECT name, description FROM names WHERE idName=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([1]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $paperName = $row['name'];
    $paperDescription = $row['description'];
} else {
    $paperName = '';
    $paperDescription = '';
}
//
$loginButtons= '      <form method="post" action="' . $uri . '?t=l">
        <span class="fr"><input name="logInRegister" type="submit" class="button" value="Log in / Register"></span>
      </form>' . "\n\n";
$logoutButtons= '      <form method="post" action="logout.php">
        <span class="fr"><input type="submit" class="button" name="login" value="Log out">&nbsp;</span>
      </form>' . "\n\n";
//
// Optional classifieds email alert, check for classified ads ready for review
//
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT emailClassified FROM alertClassified');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $dbh = new PDO($dbClassifiedsNew);
    $stmt = $dbh->prepare('SELECT review FROM ads WHERE review >= ? AND payment != ?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([time() + (15 * 60), 1]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        $to = $emailClassified;
        $subject = "Classified ad requires review\r\n";
        $body = "\r\n";
        $headers = 'From: noreply@' . $_SERVER["HTTP_HOST"] . "\r\n";
        $headers.= 'MIME-Version: 1.0' . "\r\n";
        $headers.= 'Content-Type: text/plain; charset=utf-8; format=flowed' . "\r\n";
        $headers.= 'Content-Transfer-Encoding: 7bit' . "\r\n";
        $result = mail($to, $subject, $body, $headers);
        if ($result) {
            $dbh = new PDO($dbClassifiedsNew);
            $stmt = $dbh->prepare('UPDATE ads SET payment=? WHERE review >= ?');
            $stmt->execute([1, time() + (15 * 60)]);
            $dbh = null;
        }
    }
}
//
// Delete expired registrations and password change requests
//
$dbh = new PDO($dbSubscribersNew);
$stmt = $dbh->prepare('DELETE FROM users WHERE time < ? AND verified IS NULL');
$stmt->execute([time()]);
$stmt = $dbh->prepare('UPDATE users SET verify=? WHERE time < ? AND verified = ?');
$stmt->execute([null, time(), 1]);
$dbh = null;
$dbh = new PDO($dbSubscribers);
$stmt = $dbh->prepare('UPDATE users SET verify=? WHERE time < ? AND verified = ?');
$stmt->execute([null, time(), 1]);
$dbh = null;
//
// Activate a registration
//
if (isset($tGet)
    and $tGet === 'l'
    and isset($vGet)
) {
    $dbh = new PDO($dbSubscribersNew);
    $stmt = $dbh->prepare('SELECT verify, ipAddress, payStatus FROM users WHERE verify=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$vGet]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row and $row['ipAddress'] === $_SERVER['REMOTE_ADDR']) {
        $dbh = new PDO($dbSubscribersNew);
        $stmt = $dbh->prepare('UPDATE users SET verify=?, verified=? WHERE verify=?');
        $stmt->execute([null, 1, $vGet]);
        $_SESSION['message'] = 'The email address is confirmed. After logging in, visit My account on the menu to set your account preferences.';
        $dbh = null;
    }
}
//
// Get article headline for SEO purposes
//
$headline = '';
if (isset($_GET['a'])) {
    $dbh = new PDO($dbPublished);
    $stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$aGet]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        $dbh = new PDO($dbPublished);
        $stmt = $dbh->prepare('SELECT headline FROM articles WHERE idArticle=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$aGet]);
        $row = $stmt->fetch();
        $dbh = null;
        $row = array_map('strval', $row);
        extract($row);
        $headline = $headline . ' - ';
    }
}
//
// HTML
//
require $includesPath . '/header1.inc';
echo '  <title>' . $headline . $paperName . "</title>\n";
echo '  <meta name="description" content="' . $paperDescription . '">' . "\n";
require $includesPath . '/header2Two.inc';
if (file_exists('z/local.css')) {
    echo '<link rel="stylesheet" type="text/css" href="z/local.css">' . "\n";
}
echo '</head>

<body>
  <header>
    <nav id="admin">' . "\n";
if (!isset($_SESSION['auth'])) {
    echo $loginButtons;
} else {
    echo $logoutButtons;
}
echo '      <a href="javascript:void(0);" onclick="cellMenu()"><svg id="cellMenu" class="button" width="41" height="41" viewbox="0 0 24 24" fill="none">
      <path d="M4 17H20M4 12H20M4 7H20" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
      </path></svg></a>

      <div class="logo">
        <a href="./"><img src="images/logo.png" class="logo" alt="'. $paperName . '"></a>
      </div>
    </nav>
  </header>' . "\n\n";
//
// Aside, menu
//
echo '  <aside id="aside">' . "\n";
echo '    <h5>' . $paperDescription . "</h5>\n\n";
echo '    <nav>' . "\n      ";
if (file_exists($includesPath . '/custom/programs/home.php')) {
    include $includesPath . '/custom/programs/home.php';
}
if (isset($_SESSION['auth'])) {
    //
    // My account
    //
    echo '<a class="n" href="' . $uri . '?m=my-account">My account</a><br>' . "\n";
    //
    // Article contribution
    //
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->prepare('SELECT contributor FROM users WHERE idUser=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$_SESSION['userId']]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        $row = array_map('strval', $row);
        if ($row['contributor'] === '1') {
            echo '      <a class="n" href="' . $uri . '?m=article-contribution">Article contribution</a><br>' . "\n";
        }
    }
}
//
// Aside, custom menu
//
$dbh = new PDO($dbMenu);
$stmt = $dbh->query('SELECT menuName, menuPath, menuAuthorization FROM menu ORDER BY menuSortOrder');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    extract($row);
    echo '      <a class="n" href="' . $uri . '?m=' . $menuPath . '">' . $menuName . '</a><br>' . "\n";
}
$dbh = null;
echo "      <br>\n";
echo "    </nav>\n\n";
//
// Aside, ads
//
$dbh = new PDO($dbAdvertising);
$stmt = $dbh->prepare('SELECT maxAds FROM maxAd WHERE idMaxAds=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([1]);
$row = $stmt->fetch();
if ($row) {
    $maxAds = $row['maxAds'];
}
$stmt = $dbh->prepare('SELECT idAd, sortOrderAd FROM advertisements WHERE (? >= startDateAd AND ? <= endDateAd) ORDER BY sortOrderAd');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$today, $today]);
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
    $i = 0;
    foreach ($adSort as $key => $value) {
        $i++;
        if ($i <= $maxAds) {
            $adSortTemp[$key] = $value;
        }
    }
    if (!empty($adSortTemp)) {
        $adSort = $adSortTemp;
        $adSortTemp = null;
    }
}
$dbh = new PDO($dbAdvertising);
foreach ($adSort as $idAd) {
    $stmt = $dbh->prepare('SELECT link, linkAlt FROM advertisements WHERE idAd=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idAd]);
    $row = $stmt->fetch();
    if ($row) {
        extract($row);
        if (!empty($link)) {
            $linkHtml1 = '<a href="' . $link . '" target="_blank" rel="nofollow">';
            $linkHtml2 = '</a>';
        } else {
            $linkHtml1 = $linkHtml2 = null;
        }
        echo '    ' . $linkHtml1 . '<img class="wide border" src="imaged.php?i=' . muddle($idAd) . '" alt="' . $linkAlt . '">' . $linkHtml2 . '<br>' . "\n";
    }
}
$dbh = null;
echo '  </aside>' . "\n";
//
// Main
//
echo "\n" . '  <main>' . "\n";
if (empty($_GET)) {
    include $includesPath . '/displayIndex.inc';
} elseif (isset($aGet) and isset($mGet)) {
    include $includesPath . '/archive.php';
} elseif (isset($tGet) and isset($mGet)) {
    include $includesPath . '/articleContribution.php';
} elseif (isset($aGet)) {
    include $includesPath . '/displayArticleSEO.inc';
} elseif ($mGet === 'archive-search') {
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->prepare('SELECT access FROM archiveAccess WHERE idAccess=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([1]);
    $row = $stmt->fetch();
    $dbh = null;
    if (!empty($row['access'])) {
        include $includesPath . '/archive.php';
    }
} elseif ($mGet === 'calendar') {
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->prepare('SELECT access FROM calendarAccess WHERE idCalendarAccess=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([1]);
    $row = $stmt->fetch();
    $dbh = null;
    if (!empty($row['access'])) {
        include $includesPath . '/calendar.php';
    }
} elseif ($mGet === 'classified-ads') {
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->prepare('SELECT access FROM classifiedAccess WHERE idClassifiedAccess=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([1]);
    $row = $stmt->fetch();
    $dbh = null;
    if (!empty($row['access'])) {
        include $includesPath . '/classifieds.php';
    }
} elseif ($mGet === 'contact-us') {
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->prepare('SELECT access FROM contactAccess WHERE idContactAccess=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([1]);
    $row = $stmt->fetch();
    $dbh = null;
    if (!empty($row['access'])) {
        include $includesPath . '/contact.php';
    }
} elseif ($mGet === 'article-contribution') {
    include $includesPath . '/articleContribution.php';
} elseif ($mGet === 'my-account') {
    include $includesPath . '/myAccount.php';
} elseif ($mGet === 'place-classified') {
    include $includesPath . '/placeClassified.php';
} elseif (isset($mGet)) {
    $dbh = new PDO($dbMenu);
    $stmt = $dbh->prepare('SELECT menuName, menuContent FROM menu WHERE menuPath=?');
    $stmt->execute([$mGet]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        extract($row);
        $test = substr($menuContent, 0, 8);
        if ($test === 'require ') {
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
            $content = str_replace("\n", "\n      ", $content);
            echo '    <div class="main">' . "\n";
            echo '      <h1>' . $menuName . "</h1>\n";
            echo '      ' . $content . "\n";
            echo '    </div>' . "\n";
        }
    }
} elseif (isset($tGet) and $tGet === 'c') {
    //
    // Forgot password
    //
    include $includesPath . '/passwordForgot.php';
} elseif (isset($tGet) and $tGet === 'p' and isset($vGet)) {
    //
    // Reset password
    //
    include $includesPath . '/passwordReset.php';
} elseif (isset($tGet) and $tGet === 'pay') {
    //
    // Payment for subscription
    //
    if (file_exists($includesPath . '/custom/programs/pay.php')) {
        include $includesPath . '/custom/programs/pay.php';
    }
}
//
// Log in / Register
//
if (isset($tGet) and $tGet === 'l') {
    include $includesPath . '/login.php';
}
if (isset($_POST['logInRegister']) and $_POST['logInRegister'] === 'Log in / Register') {
    include $includesPath . '/login.php';
}
//
// Optional footer
//
if (file_exists($includesPath . '/custom/programs/footer.php')) {
    include $includesPath . '/custom/programs/footer.php';
}
?>
  </main>
</body>
</html>
