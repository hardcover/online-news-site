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
 * @version:  2023 03 13
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
session_start();
require 'z/system/configuration.php';
if ($freeOrPaid !== 'free') {
    include $includesPath . '/authorization.php';
} else {
    $uri = $uriScheme . '://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
}
require $includesPath . '/common.php';
require $includesPath . '/parsedown-master/Parsedown.php';
//
// Variables
//
$aGet = secure($_GET['a']);
$anchorPath = null;
$database = $dbPublished;
$database2 = $dbPublished2;
$datePost = $today;
$editorView = null;
$imagePath = 'images.php';
$imagePath2 = 'imagep2.php';
$indexPath = '';
$use = 'news';
//
if (isset($_SESSION['userId'])) {
    $logOutHtml = ' | <a class="n" href="' . $uri . 'logout.php">Log out</a>';
} else {
    $logOutHtml = null;
}
//
$dbh = new PDO($dbSettings);
$stmt = $dbh->prepare('SELECT name FROM names WHERE idName=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([1]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $title = $row['name'];
    $name = '  <h2>' . html($row['name']) . "</h2>\n\n";
} else {
    $title = 'Subscriber';
    $name = null;
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
echo '  <title>' . $headline . $title . "</title>\n";
require $includesPath . '/header2.inc';
if (file_exists('z/local.css')) {
    echo '  <link rel="stylesheet" type="text/css" href="z/local.css">' . "\n";
}
echo '</head>

<body>' . "\n";
require $includesPath . '/displayIndex.inc';
?>
</body>
</html>
