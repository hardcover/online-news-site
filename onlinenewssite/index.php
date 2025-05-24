<?php
/**
 * The news home page
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Online News <useTheContactForm@onlinenewssite.com>
 * @copyright 2025 Online News
 * @license   https://onlinenewssite.com/license.html
 * @version   2025 05 12
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/onlinenewsllc/online-news-site
 */
@session_start();
//
// Check for existing configuration file and logo, create if not found
//
if (!file_exists('editor/z/system/configuration.php')) {
    copy('editor/z/system/configuration.inc', 'editor/z/system/configuration.php');
}
if (!file_exists('images/logo.png')) {
    copy('images/evaluation.logo.png', 'images/logo.png');
}
//
// Requires
//
require 'editor/z/system/configuration.php';
require $includesPath . '/editor/common.php';
require $includesPath . '/parsedown-master/Parsedown.php';
$uri = $uriScheme . '://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
//
// Create the databases and sitemaps path on the first run
//
require $includesPath . '/editor/createDatabases.php';
$sitemapsPath = getcwd() . "\n" . $uriScheme . '://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
file_put_contents($includesPath . '/cron/sitemapsPath', $sitemapsPath);
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
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('SELECT review FROM ads WHERE review >= ? AND payment != ?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([time() + (15 * 60), 1]);
    $row = $stmt->fetch();
    $dbh = null;
    print_r($row);

    if ($row) {
        $to = $emailClassified;
        $subject = "Classified ad requires review\r\n";
        $body = "\r\n";
        $headers = 'From: noreply@' . $_SERVER["HTTP_HOST"] . "\r\n";
        $headers.= 'MIME-version  1.0' . "\r\n";
        $headers.= 'Content-Type: text/plain; charset=utf-8; format=flowed' . "\r\n";
        $headers.= 'Content-Transfer-Encoding: 7bit' . "\r\n";
        $result = mail($to, $subject, $body, $headers);
        if ($result) {
            $dbh = new PDO($dbClassifieds);
            $stmt = $dbh->prepare('UPDATE ads SET payment=? WHERE review >= ?');
            $stmt->execute([1, time() + (15 * 60)]);
            $dbh = null;
        }
    }
}
//
// Delete expired registrations and password change requests
//
$dbh = new PDO($dbSubscribers);
$stmt = $dbh->prepare('DELETE FROM users WHERE time < ? AND verified IS NULL');
$stmt->execute([time()]);
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
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->prepare('SELECT verify, ipAddress, payStatus FROM users WHERE verify=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$vGet]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row and $row['ipAddress'] === $_SERVER['REMOTE_ADDR']) {
        $dbh = new PDO($dbSubscribers);
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
require $includesPath . '/editor/header1.inc';
echo '  <title>' . $headline . $paperName . "</title>\n";
echo '  <meta name="description" content="' . $paperDescription . '">' . "\n";
require $includesPath . '/subscriber/header2Two.inc';
if (file_exists('z/local.css')) {
    echo '  <link rel="stylesheet" type="text/css" href="z/local.css">' . "\n";
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
if (file_exists($includesPath . '/subscriber/custom/programs/home.php')) {
    include $includesPath . '/subscriber/custom/programs/home.php';
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
    include $includesPath . '/subscriber/displayIndex.inc';
} elseif (isset($aGet) and isset($mGet)) {
    include $includesPath . '/subscriber/archive.php';
} elseif (isset($tGet) and isset($mGet)) {
    include $includesPath . '/subscriber/articleContribution.php';
} elseif (isset($aGet)) {
    include $includesPath . '/subscriber/displayArticleSEO.inc';
} elseif ($mGet === 'archive-search') {
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->prepare('SELECT access FROM archiveAccess WHERE idAccess=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([1]);
    $row = $stmt->fetch();
    $dbh = null;
    if (!empty($row['access'])) {
        include $includesPath . '/subscriber/archive.php';
    }
} elseif ($mGet === 'calendar') {
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->prepare('SELECT access FROM calendarAccess WHERE idCalendarAccess=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([1]);
    $row = $stmt->fetch();
    $dbh = null;
    if (!empty($row['access'])) {
        include $includesPath . '/subscriber/calendar.php';
    }
} elseif ($mGet === 'classified-ads') {
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->prepare('SELECT access FROM classifiedAccess WHERE idClassifiedAccess=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([1]);
    $row = $stmt->fetch();
    $dbh = null;
    if (!empty($row['access'])) {
        include $includesPath . '/subscriber/classifieds.php';
    }
} elseif ($mGet === 'contact-us') {
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->prepare('SELECT access FROM contactAccess WHERE idContactAccess=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([1]);
    $row = $stmt->fetch();
    $dbh = null;
    if (!empty($row['access'])) {
        include $includesPath . '/subscriber/contact.php';
    }
} elseif ($mGet === 'article-contribution') {
    include $includesPath . '/subscriber/articleContribution.php';
} elseif ($mGet === 'my-account') {
    include $includesPath . '/subscriber/myAccount.php';
} elseif ($mGet === 'place-classified') {
    include $includesPath . '/subscriber/placeClassified.php';
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
            include $includesPath . '/subscriber/custom/programs/' . $program;
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
    include $includesPath . '/subscriber/passwordForgot.php';
} elseif (isset($tGet) and $tGet === 'p' and isset($vGet)) {
    //
    // Reset password
    //
    include $includesPath . '/subscriber/passwordReset.php';
} elseif (isset($tGet) and $tGet === 'pay') {
    //
    // Payment for subscription
    //
    if (file_exists($includesPath . '/subscriber/custom/programs/pay.php')) {
        include $includesPath . '/subscriber/custom/programs/pay.php';
    }
}
//
// Log in / Register
//
if ((isset($tGet) and $tGet === 'l') or !empty($_POST['logInRegister'])) {
    include $includesPath . '/subscriber/login.php';
}
//
// Optional footer
//
if (file_exists($includesPath . '/subscriber/custom/programs/footer.php')) {
    include $includesPath . '/subscriber/custom/programs/footer.php';
}
?>
  </main>
</body>
</html>
