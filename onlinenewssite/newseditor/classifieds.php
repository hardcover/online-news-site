<?php
/**
 * Pending classified ad maintenance
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *            http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2018 01 08
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
if (empty($row['userType']) or $row['userType'] != 4) {
    include 'logout.php';
    exit;
}
//
// Variables
//
$categoryIdEdit = null;
$categoryIdPost = inlinePost('categoryId');
$descriptionEdit = null;
$descriptionPost = securePost('description');
$durationEdit = null;
$durationPost = inlinePost('duration');
$idAdPost = inlinePost('idAd');
$message = null;
$startDateEdit = null;
$startDatePost = inlinePost('startDate');
$titleEdit = null;
$titlePost = inlinePost('title');
$photosOrdered = array(1, 2, 3, 4, 5, 6, 7);
$photosReverse = array_reverse($photosOrdered);
$photoAvailable = null;
//
// Button: Publish
//
if (isset($_POST['publish'])) {
    if ($idAdPost != null) {
        $startTime = strtotime($startDatePost);
        $review = date("Y-m-d", $startTime + ($durationPost * 7 * 86400));
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->prepare('UPDATE ads SET title=?, description=?, categoryId=?, review=?, startDate=?, duration=? WHERE idAd=?');
        $stmt->execute(array($titlePost, $descriptionPost, $categoryIdPost, $review, $startDatePost, 1, $idAdPost));
        $dbh = null;
        include $includesPath . '/addUpdateClassified.php';
    } else {
        $message = 'No ad was selected.';
    }
}
//
// Button: Delete
//
if (isset($_POST['delete'])) {
    if ($idAdPost != null) {
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->prepare('SELECT idAd FROM ads WHERE idAd=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(array($idAdPost));
        $row = $stmt->fetch();
        $dbh = null;
        if ($row) {
            $dbh = new PDO($dbClassifieds);
            $stmt = $dbh->prepare('DELETE FROM ads WHERE idAd=?');
            $stmt->execute(array($idAdPost));
            $dbh = null;
        } else {
            $message = 'The selected ad was not found.';
        }
    } else {
        $message = 'No ad was selected.';
    }
}
//
// Sync with remote sites
//
require $includesPath . '/syncClassifieds.php';
require $includesPath . '/syncClassifiedsNew.php';
//
// HTML
//
require $includesPath . '/header1.inc';
?>
  <title>Pending classifieds maintenance</title>
  <link rel="icon" type="image/png" href="images/favicon.png" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" type="text/css" href="z/jquery-ui.theme.css" />
  <link rel="stylesheet" type="text/css" href="z/jquery-ui.structure.css" />
  <link rel="stylesheet" type="text/css" href="z/base.css" />
  <link rel="stylesheet" type="text/css" media="(max-width: 768px)" href="z/small.css" />
  <link rel="stylesheet" type="text/css" media="(min-width: 768px)" href="z/large.css" />
  <script src="z/jquery.min.js"></script>
  <script src="z/jquery-ui.min.js"></script>
  <script src="z/datepicker.js"></script>
</head>
<?php require $includesPath . '/body.inc'; ?>

  <h4 class="m"><a class="s" href="classifieds.php">&nbsp;Pending review&nbsp;</a><a class="m" href="classifiedCreate.php">&nbsp;Create&nbsp;</a><a class="m" href="classifiedEdit.php">&nbsp;Edit&nbsp;</a></h4>
<?php echoIfMessage($message); ?>

  <h1 id="waiting">Please wait.</h1>

  <h1>Pending classified ads maintenance</h1>

<?php
$rowcount = null;
$dbh = new PDO($dbClassifieds);
$stmt = $dbh->query('SELECT count(*) FROM ads WHERE review IS NULL');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$dbRowCount = $row['count(*)'];
echo '  <p>' . number_format($dbRowCount) . " ad(s) pending.</p>\n  <hr />\n\n";
$i = null;
$stmt = $dbh->query('SELECT idAd, email, title, description, categoryId, startDate, duration, photos FROM ads WHERE review IS NULL ORDER BY email');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    $i++;
    if ($i == 1) {
        extract($row);
        $rowcount++;
        $categoryIdEdit = $categoryId;
        $durationEdit = 1;
        $email = plain($email);
        $photos = json_decode($photos, true);
        $startDateEdit = $startDate;
        $titleEdit = $title;
        if (is_null($startDate)) {
            $startDateEdit = $today;
        } else {
            $startDateEdit = $startDate;
        }
        echo '  <form class="wait" action="' . $uri . 'classifieds.php" method="post">' . "\n";
        echo "    <p>Submitted by<br />\n";
        echo '    ' . $email . "</p>\n\n";
        echo '    <p><label for="title">Title</label><br />' . "\n";
        echo '    <input type="text" class="w" id="title" name="title" ';
        echoIfValue($titleEdit);
        echo " /></p>\n\n";
        echo '    <p><label for="description">Description</label><br />' . "\n";
        echo '    <textarea class="w" id="description" name="description" rows="9">';
        echo echoIfText($description);
        echo '</textarea></p>' . "\n\n";
        echo '    <p><label for="categoryId">Category (select a subcategory)</label><br />' . "\n";
        echo '    <select size="1" id="categoryId" name="categoryId" required>' . "\n";
        $stmt = $dbh->query('SELECT idSection, section FROM sections ORDER BY sortOrderSection');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            extract($row);
            echo '      <option value="">' . html($section) . "</option>\n";
            $stmt = $dbh->prepare('SELECT idSubsection, subsection FROM subsections WHERE parentId=? ORDER BY sortPrioritySubSection');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute(array($idSection));
            foreach ($stmt as $row) {
                extract($row);
                if ($idSubsection == $categoryIdEdit) {
                    $selected = ' selected';
                } else {
                    $selected = null;
                }
                echo '      <option value="' . $idSubsection . '"' . $selected . '>&nbsp;&nbsp;&nbsp;' . html($subsection) . "</option>\n";
            }
        }
        echo "    </select></p>\n\n";
        echo '    <p><label for="startDate">Start date</label><br />' . "\n";
        echo '    <input type="text" class="datepicker" id="startDate" name="startDate" ';
        echo echoIfValue($startDateEdit);
        echo " /></p>\n";
        echo '    <p><label for="duration">Duration (weeks)</label><br />' . "\n";
        echo '    <input type="number" id="duration" name="duration" ';
        echo echoIfValue($durationEdit);
        echo " /></p>\n";
        $i = null;
        foreach ($photos as $photo) {
            $i++;
            if ($photo == 1) {
                echo '    <p><img class="w b" src="imagec.php?i=' . muddle($idAd) . $i . '" alt="" /></p>' . "\n";
            }
        }
        echo '    <p><input type="submit" class="button" value="Publish" name="publish" /> <input type="submit" class="button" value="Delete" name="delete" /><input name="idAd" type="hidden" value="' . $idAd . '" /></p>' . "\n";
        echo "  </form>\n\n";
    }
}
$dbh = null;
if (is_null($i)) {
    echo '  <form class="wait" action="' . $uri . 'classifieds.php" method="post">' . "\n";
    echo "  </form>\n\n";
}
?>
</body>
</html>
