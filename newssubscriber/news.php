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
 * @version   GIT: 2015-07-21
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
session_start();
require 'z/system/configuration.php';
if ($freeOrPaid != 'free') {
    include $includesPath . '/authorization.php';
} else {
    $uri = $uriScheme . '://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
}
require $includesPath . '/common.php';
require $includesPath . '/parsedown-master/Parsedown.php';
//
// Variables
//
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
$stmt->execute(array(1));
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
// HTML
//
require $includesPath . '/header1.inc';
echo '  <title>' . $title . "</title>\n";
require $includesPath . '/header2.inc';
echo "<body>\n";
require $includesPath . '/displayIndex.inc';
?>
</body>
</html>
