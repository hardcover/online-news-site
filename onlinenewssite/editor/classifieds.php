<?php
/**
 * Pending classified ad maintenance
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2025 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 * @version:  2025 05 12
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
session_start();
require 'z/system/configuration.php';
$includesPath = '../' . $includesPath;
require $includesPath . '/editor/authorization.php';
require $includesPath . '/editor/common.php';
//
// User-group authorization
//
$dbh = new PDO($dbEditors);
$stmt = $dbh->prepare('SELECT userType FROM users WHERE idUser=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$_SESSION['userId']]);
$row = $stmt->fetch();
$dbh = null;
if (empty($row['userType']) or strval($row['userType']) !== '4') {
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
$invoiceEdit = null;
$invoicePost = inlinePost('invoice');
$message = '';
$startDateEdit = null;
$startDatePost = inlinePost('startDate');
$titleEdit = null;
$titlePost = inlinePost('title');
$photosOrdered = [1, 2, 3, 4, 5, 6, 7];
$photosReverse = array_reverse($photosOrdered);
$photoAvailable = null;
//
// Button: Publish
//
if (isset($_POST['publish'])) {
    if (isset($idAdPost)) {
        $startTime = strtotime($startDatePost);
        $endDate = date("Y-m-d", $startTime + ($durationPost * 7 * 86400));
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->prepare('UPDATE ads SET title=?, description=?, categoryId=?, review=?, startDate=?, duration=?, endDate=? WHERE idAd=?');
        $stmt->execute([$titlePost, $descriptionPost, $categoryIdPost, null, $startDatePost, $durationPost, $endDate, $idAdPost]);
        $dbh = null;
        $idAdPublish = $idAdPost;
    } else {
        $message = 'No ad was selected.';
    }
}
//
// Button: Delete
//
if (isset($_POST['delete'])) {
    if (isset($idAdPost)) {
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->prepare('SELECT idAd FROM ads WHERE idAd=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idAdPost]);
        $row = $stmt->fetch();
        $dbh = null;
        if ($row) {
            $dbh = new PDO($dbClassifieds);
            $stmt = $dbh->prepare('DELETE FROM ads WHERE idAd=?');
            $stmt->execute([$idAdPost]);
            $dbh = null;
        } else {
            $message = 'The selected ad was not found.';
        }
    } else {
        $message = 'No ad was selected.';
    }
}
//
// HTML
//
require $includesPath . '/editor/header1.inc';
?>
  <title>Pending classifieds maintenance</title>
  <link rel="icon" type="image/png" href="images/32.png">
  <link rel="stylesheet" type="text/css" href="z/jquery-ui.min.css">
  <link rel="stylesheet" type="text/css" href="z/base.css">
  <link rel="stylesheet" type="text/css" href="z/admin.css">
  <script src="z/jquery.min.js"></script>
  <script src="z/jquery-ui.min.js"></script>
  <script src="z/datepicker.js"></script>
  <link rel="manifest" href="manifest.json">
  <link rel="apple-touch-icon" href="images/192.png">
</head>
<?php require $includesPath . '/editor/body.inc'; ?>

  <nav class="n">
    <h4 class="m"><a class="s" href="classifieds.php">Pending review</a><a class="m" href="classifiedCreate.php">Create</a><a class="m" href="classifiedEdit.php">Edit</a></h4>
  </nav>
<?php echoIfMessage($message); ?>

  <div class="column">
    <h1>Pending classified ads maintenance</h1>

<?php
$rowcount = null;
$dbh = new PDO($dbClassifieds);
$stmt = $dbh->query('SELECT count(idAd) FROM ads WHERE review IS NOT NULL AND review < ?');
$stmt->execute([time()]);
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$dbRowCount = $row['count(idAd)'];
echo '    <p>' . number_format($dbRowCount) . ' ad(s) pending.</p>' . "\n" . '    <hr>' . "\n\n";
$i = null;
$stmt = $dbh->query('SELECT idAd, email, title, description, categoryId, startDate, duration, invoice, photos FROM ads WHERE review IS NOT NULL ORDER BY email');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    $i++;
    if ($i === intval(1)) {
        extract($row);
        $rowcount++;
        $categoryIdEdit = $categoryId;
        $invoiceEdit = $invoice;
        $durationEdit = '1';
        $email = plain($email);
        if (!empty($photos)) {
            $photos = json_decode($photos, true);
            $photos = array_map('strval', $photos);
        }
        $startDateEdit = $startDate;
        $titleEdit = $title;
        if (empty($startDate)) {
            $startDateEdit = $today;
        } else {
            $startDateEdit = $startDate;
        }
        echo '    <form action="' . $uri . 'classifieds.php" method="post">' . "\n";
        echo '      <p>Submitted by<br>' . "\n";
        echo '      ' . $email . "</p>\n\n";
        echo '      <p><label for="title">Title</label><br>' . "\n";
        echo '      <input class="wide" id="title" name="title" ';
        echoIfValue($titleEdit);
        echo '></p>' . "\n\n";
        echo '      <p><label for="description">Description</label><br>' . "\n";
        echo '      <textarea class="wide" id="description" name="description" rows="9">';
        echo echoIfText($description);
        echo '</textarea></p>' . "\n\n";
        echo '      <p><label for="invoice"><input id="invoice" name="invoice" type="checkbox" value="1"';
        echoIfYes($invoiceEdit);
        echo '> Send an invoice to also have the add in the print version of the paper.</label></p>' . "\n\n";
        echo '      <p><label for="categoryId">Category (select a subcategory)</label><br>' . "\n";
        echo '      <select size="1" id="categoryId" name="categoryId" required>' . "\n";
        $stmt = $dbh->query('SELECT idSection, section FROM sections ORDER BY sortOrderSection');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            $row = array_map('strval', $row);
            extract($row);
            echo '        <option value="">' . html($section) . "</option>\n";
            $stmt = $dbh->prepare('SELECT idSubsection, subsection FROM subsections WHERE parentId=? ORDER BY sortPrioritySubSection');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$idSection]);
            foreach ($stmt as $row) {
                $row = array_map('strval', $row);
                extract($row);
                if ($idSubsection === strval($categoryIdEdit)) {
                    $selected = ' selected';
                } else {
                    $selected = null;
                }
                echo '        <option value="' . $idSubsection . '"' . $selected . '>&nbsp;&nbsp;&nbsp;' . html($subsection) . "</option>\n";
            }
        }
        echo '      </select></p>' . "\n\n";
        echo '      <p><label for="startDate">Start date</label><br>' . "\n";
        echo '      <input class="datepicker date" id="startDate" name="startDate" ';
        echo echoIfValue($startDateEdit);
        echo '></p>' . "\n\n";
        echo '      <p><label for="duration">Duration (weeks)</label><br>' . "\n";
        echo '      <input type="number" id="duration" name="duration" class="date" ';
        echo echoIfValue($durationEdit);
        echo '></p>' . "\n\n";
        $i = null;
        if (!empty($photos)) {
            foreach ($photos as $photo) {
                $i++;
                if ($photo === '1') {
                    echo '      <p><img class="wide border" src="imagec.php?i=' . muddle($idAd) . $i . '" alt=""></p>' . "\n";
                }
            }
        }
        echo '      <p><input type="submit" class="button" value="Publish" name="publish"> <input type="submit" class="button" value="Delete" name="delete"><input name="idAd" type="hidden" value="' . $idAd . '"></p>' . "\n";
        echo '    </form>' . "\n\n";
    }
}
$dbh = null;
if (is_null($i)) {
    echo '    <form action="' . $uri . 'classifieds.php" method="post">' . "\n";
    echo '    </form>' . "\n";
}
?>
  </div>
</body>
</html>
