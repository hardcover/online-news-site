<?php
/**
 * Remote site menu maintenance
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
session_start();
require 'z/system/configuration.php';
require $includesPath . '/authorization.php';
require $includesPath . '/common.php';
//
// User-group authorization
//
$dbh = new PDO($dbEditors);
$stmt = $dbh->prepare('SELECT userType FROM users WHERE idUser=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array($_SESSION['userId']));
$row = $stmt->fetch();
$dbh = null;
if (empty($row['userType']) or $row['userType'] != 5) {
    include 'logout.php';
    exit;
}
//
// Variables
//
$archiveEdit = null;
$archivePost = inlinePost('archive');
$calendarEdit = null;
$calendarPost = inlinePost('calendar');
$classifiedsEdit = null;
$classifiedsPost = inlinePost('classifieds');
$edit = inlinePost('edit');
$message = null;
//
// Variables for predefined menu items
//
$dbh = new PDO($dbSettings);
$stmt = $dbh->prepare('SELECT access FROM archiveAccess WHERE idAccess=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array(1));
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $archiveEdit = $row['access'];
} else {
    $archiveEdit = null;
}
$dbh = new PDO($dbSettings);
$stmt = $dbh->prepare('SELECT access FROM calendarAccess WHERE idCalendarAccess=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array(1));
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $calendarEdit = $row['access'];
} else {
    $calendarEdit = null;
}
$dbh = new PDO($dbMenu);
$stmt = $dbh->prepare('SELECT idMenu FROM menu WHERE menuName=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array('Classified ads'));
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $classifiedsEdit = 1;
}
//
$remotes = array();
$dbh = new PDO($dbRemote);
$stmt = $dbh->query('SELECT remote FROM remotes');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    $remotes[] = $row['remote'];
}
$dbh = null;
//
// Button: Update
//
if (isset($_POST['updatePredefined'])) {
    //
    // Enable archive access
    //
    if ($archivePost == strval('on')) {
        $access = 1;
        $archiveEdit = 1;
    } else {
        $access = null;
        $archiveEdit = null;
    }
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->query('DELETE FROM archiveAccess');
    $stmt = $dbh->prepare('INSERT INTO archiveAccess (access) VALUES (?)');
    $stmt->execute(array($access));
    $dbh = null;
    include $includesPath . '/syncSettings.php';
    //
    // Enable archive access
    //
    $dbh = new PDO($dbMenu);
    $stmt = $dbh->prepare('DELETE FROM menu WHERE menuName = ?');
    $stmt->execute(array('Archive search'));
    $dbh = null;
    if ($archivePost == strval('on')) {
        $dbh = new PDO($dbMenu);
        $stmt = $dbh->prepare('INSERT INTO menu (menuName, menuSortOrder, menuPath) VALUES (?, ?, ?)');
        $stmt->execute(array('Archive search', 1, 'archive'));
        $dbh = null;
        $archiveEdit = 1;
    } else {
        $archiveEdit = null;
    }
    //
    // Enable calendar access
    //
    $dbh = new PDO($dbMenu);
    $stmt = $dbh->prepare('DELETE FROM menu WHERE menuName = ?');
    $stmt->execute(array('Calendar'));
    $dbh = null;
    if ($calendarPost == strval('on')) {
        $dbh = new PDO($dbMenu);
        $stmt = $dbh->prepare('INSERT INTO menu (menuName, menuSortOrder, menuPath) VALUES (?, ?, ?)');
        $stmt->execute(array('Calendar', 1, 'calendar'));
        $dbh = null;
        $calendarEdit = 1;
    } else {
        $calendarEdit = null;
    }
    //
    // Enable classifieds
    //
    $dbh = new PDO($dbMenu);
    $stmt = $dbh->prepare('DELETE FROM menu WHERE menuName = ?');
    $stmt->execute(array('Classified ads'));
    $dbh = null;
    if ($classifiedsPost == strval('on')) {
        $dbh = new PDO($dbMenu);
        $stmt = $dbh->prepare('INSERT INTO menu (menuName, menuSortOrder, menuPath) VALUES (?, ?, ?)');
        $stmt->execute(array('Classified ads', 2, 'classified-ads'));
        $dbh = null;
        $classifiedsEdit = 1;
    } else {
        $classifiedsEdit = null;
    }
    //
    // Update remote sites
    //
    include $includesPath . '/syncMenu.php';
}
//
// HTML
//
require $includesPath . '/header1.inc';
echo "  <title>Predefined menu items maintenance</title>\n";
echo '  <script type="text/javascript" src="z/wait.js"></script>' . "\n";
require $includesPath . '/header2.inc';
require $includesPath . '/body.inc';
?>

  <h4 class="m"><a class="m" href="menu.php">&nbsp;Menu&nbsp;</a><a class="m" href="menuCalendar.php">&nbsp;Calendar&nbsp;</a><a class="s" href="menuPredefine.php">&nbsp;Predefined&nbsp;</a></h4>
<?php echoIfMessage($message); ?>

  <h1 id="waiting">Please wait.</h1>

  <h1>Predefined menu items maintenance</h1>

  <form class="wait" action="<?php echo $uri; ?>menuPredefine.php" method="post">
    <p><label>
      <input type="checkbox" name="archive"<?php echoIfYes($archiveEdit); ?> /> Enable archive search
    </label></p>

    <p><label>
      <input type="checkbox" name="calendar"<?php echoIfYes($calendarEdit); ?> /> Enable calendar
    </label></p>

    <p><label>
      <input type="checkbox" name="classifieds"<?php echoIfYes($classifiedsEdit); ?> /> Enable classified ads
    </label></p>

    <p><input type="submit" value="Update" name="updatePredefined" class="button" /></p>
  </form>
</body>
</html>