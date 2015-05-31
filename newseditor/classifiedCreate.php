<?php
/**
 * For management to place classified ads
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
session_start();
require 'z/system/configuration.php';
require $includesPath . '/authorization.php';
require $includesPath . '/common.php';
require $includesPath . '/password_compat/password.php';
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
$idAdEdit = null;
$idAdPost = inlinePost('idAd');
$message = null;
$startDateEdit = null;
$startDatePost = inlinePost('startDate');
$startTime = strtotime($startDatePost);
$review = date("Y-m-d", $startTime + ($durationPost * 7 * 86400));
$titleEdit = null;
$titlePost = inlinePost('title');
$photosOrdered = array(1, 2, 3, 4, 5, 6, 7);
$photosReverse = array_reverse($photosOrdered);
$photoAvailable = null;
//
// Button: Add / update
//
if (isset($_POST['addUpdate'])) {
    //
    // Determine insert or update
    //
    if (empty($idAdPost)) {
        $dbh = new PDO($dbClassifiedsNew);
        $stmt = $dbh->query('DELETE FROM ads WHERE title IS NULL');
        $stmt = $dbh->prepare('INSERT INTO ads (title) VALUES (?)');
        $stmt->execute(array(null));
        $idAdPost = $dbh->lastInsertId();
        $dbh = null;
    }
    //
    // Apply the update except for the image
    //
    if (empty($emailPost)) {
        $emailPost = muddle($_SESSION['username']);
    }
    $dbh = new PDO($dbClassifiedsNew);
    $stmt = $dbh->prepare('UPDATE ads SET email=?, title=?, description=?, categoryId=?, startDate=?, duration=?, photos=? WHERE idAd=?');
    $stmt->execute(array($emailPost, $titlePost, $descriptionPost, $categoryIdPost, $startDatePost, $durationPost, $_SESSION['userId'], $idAdPost));
    $dbh = null;
    //
    // Store the image, if any
    //
    if ($_FILES['image']['size'] > 0 and $_FILES['image']['error'] == 0) {
        $sizes = getimagesize($_FILES['image']['tmp_name']);
        if ($sizes['mime'] == 'image/jpeg') {
            //
            // Check for available images
            //
            foreach ($photosReverse as $photo) {
                $dbh = new PDO($dbClassifiedsNew);
                $stmt = $dbh->prepare('SELECT photo' . $photo . ' FROM ads WHERE idAd=?');
                $stmt->setFetchMode(PDO::FETCH_NUM);
                $stmt->execute(array($idAdPost));
                $row = $stmt->fetch();
                $dbh = null;
                if ($row['0'] == '') {
                    $photoAvailable = $photo;
                }
            }
            if (is_null($photoAvailable)) {
                $message = 'All seven images have been used.';
            } else {
                //
                // Calculate the aspect ratio
                //
                $widthOriginal = $sizes['0'];
                $heightOriginal = $sizes['1'];
                $aspectRatio = $widthOriginal / $heightOriginal;
                //
                // Reduce an oversize image
                //
                if ($widthOriginal > 1920) {
                    $widthHD = 1920;
                    $heightHD = round($widthHD / $aspectRatio);
                    $hd = imagecreatetruecolor($widthHD, $heightHD);
                    $srcImage = imagecreatefromjpeg($_FILES['image']['tmp_name']);
                    imagecopyresized($hd, $srcImage, 0, 0, 0, 0, $widthHD, $heightHD, ImageSX($srcImage), ImageSY($srcImage));
                    ob_start();
                    imagejpeg($hd, null, 90);
                    imagedestroy($hd);
                    $hdImage = ob_get_contents();
                    ob_end_clean();
                } else {
                    $hdImage = file_get_contents($_FILES['image']['tmp_name']);
                }
                $dbh = new PDO($dbClassifiedsNew);
                $stmt = $dbh->prepare('UPDATE ads SET photo' . $photoAvailable . '=? WHERE idAd=?');
                $stmt->execute(array($hdImage, $idAdPost));
                $dbh = null;
            }
        } else {
            $message = 'The uploaded file was not in the JPG format.';
        }
    }
}
//
// Button: Delete ad
//
if (isset($_POST['delete']) and isset($idAdPost)) {
    $dbh = new PDO($dbClassifiedsNew);
    $stmt = $dbh->prepare('DELETE FROM ads WHERE idAd=?');
    $stmt->execute(array($idAdPost));
    $dbh = null;
}
//
// Button: Publish
//
if (isset($_POST['publish']) and isset($idAdPost)) {
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->query('DELETE FROM ads WHERE title IS NULL');
    $stmt = $dbh->prepare('INSERT INTO ads (title) VALUES (?)');
    $stmt->execute(array(null));
    $idAdPublish = $dbh->lastInsertId();
    $num = array();
    foreach ($photosOrdered as $photo) {
        $dbh = new PDO($dbClassifiedsNew);
        $stmt = $dbh->prepare('SELECT photo' . $photo . ' FROM ads WHERE idAd=?');
        $stmt->setFetchMode(PDO::FETCH_NUM);
        $stmt->execute(array($idAdPost));
        $row = $stmt->fetch();
        $dbh = null;
        if (isset($row['0'])) {
            $num[] = 1;
            $dbh = new PDO($dbClassifieds);
            $stmt = $dbh->prepare('UPDATE ads SET photo' . $photo . '=? WHERE idAd=?');
            $stmt->execute(array($row['0'], $idAdPublish));
            $dbh = null;
        } else {
            $num[] = 0;
        }
    }
    $photosPublished = json_encode($num);
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('UPDATE ads SET email=?, title=?, description=?, categoryId=?, review=?, startDate=?, duration=?, photos=? WHERE idAd=?');
    $stmt->execute(array($_SESSION['username'], $titlePost, $descriptionPost, $categoryIdPost, $review, $startDatePost, $durationPost, $photosPublished, $idAdPublish));
    $dbh = null;
    $dbh = new PDO($dbClassifiedsNew);
    $stmt = $dbh->prepare('DELETE FROM ads WHERE idAd=?');
    $stmt->execute(array($idAdPost));
    $dbh = null;
    include $includesPath . '/addUpdateClassified.php';
    include $includesPath . '/syncClassifieds.php';
}
//
// Button: Delete photos
//
if (isset($_POST['photoDelete']) and isset($idAdPost)) {
    $dbh = new PDO($dbClassifiedsNew);
    $stmt = $dbh->prepare('UPDATE ads SET photo1=?, photo2=?, photo3=?, photo4=?, photo5=?, photo6=?, photo7=? WHERE idAd=?');
    $stmt->execute(array(null, null, null, null, null, null, null, $idAdPost));
    $dbh = null;
}
//
// Set the edit variables
//
$dbh = new PDO($dbClassifiedsNew);
$stmt = $dbh->prepare('SELECT idAd, email, title, description, categoryId, review, startDate, duration FROM ads WHERE photos=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array($_SESSION['userId']));
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $categoryIdEdit = $row['categoryId'];
    $descriptionEdit = $row['description'];
    $durationEdit = $row['duration'];
    $emailEdit = $row['email'];
    $idAdEdit = $row['idAd'];
    $reviewEdit = $row['review'];
    $startDateEdit = $row['startDate'];
    $titleEdit = $row['title'];
}
//
// HTML
//
require $includesPath . '/header1.inc';
?>
  <title>Create a new classified ad</title>
  <link rel="icon" type="image/png" href="images/favicon.png" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" type="text/css" href="z/jquery-ui.theme.css" />
  <link rel="stylesheet" type="text/css" href="z/jquery-ui.structure.css" />
  <link rel="stylesheet" type="text/css" href="z/base.css" />
  <link rel="stylesheet" type="text/css" media="(max-width: 768px)" href="z/small.css" />
  <link rel="stylesheet" type="text/css" media="(min-width: 768px)" href="z/large.css" />
  <script type="text/javascript" src="z/jquery.js"></script>
  <script type="text/javascript" src="z/jquery-ui.js"></script>
  <script type="text/javascript" src="z/datepicker.js"></script>
</head>
<?php require $includesPath . '/body.inc'; ?>

  <h4 class="m"><a class="m" href="classifieds.php">&nbsp;Pending review&nbsp;</a><a class="s" href="classifiedCreate.php">&nbsp;Create&nbsp;</a><a class="m" href="classifiedEdit.php">&nbsp;Edit&nbsp;</a></h4>
<?php echoIfMessage($message); ?>

  <h1 id="waiting">Please wait.</h1>

  <h1>Create a classified ad</h1>

  <p>One classified ad at a time may be created and edited by each classifieds management user. Until either published or deleted, the ad will be here available for further editing each time the user logs in.</p>

  <form class="wait" action="<?php echo $uri; ?>classifiedCreate.php" method="post" enctype="multipart/form-data">
    <p><label for="title">Title</label><br />
    <input id="title" name="title" type="text" class="w"<?php echoIfValue($titleEdit); ?> /><input type="hidden" name="idAd"<?php echoIfValue($idAdEdit); ?> /></p>

    <p><label for="description">Description</label><br />
    <textarea id="description" name="description" class="w"><?php echoIfText($descriptionEdit); ?></textarea><p>

    <p><label for="categoryId">Categories (select a subcategory)</label><br />
    <select id="categoryId" name="categoryId" size="1">
<?php
$dbh = new PDO($dbClassifieds);
$stmt = $dbh->query('SELECT idSection, section FROM sections ORDER BY sortOrderSection');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    extract($row);
    echo '        <option value="">' . html($section) . "</option>\n";
    $stmt = $dbh->prepare('SELECT idSubsection, subsection FROM subsections WHERE parentId=? ORDER BY sortOrderSubsection');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idSection));
    foreach ($stmt as $row) {
        extract($row);
        if ($idSubsection == $categoryIdEdit) {
            $selected = ' selected';
        } else {
            $selected = null;
        }
        echo '        <option value="' . $idSubsection . '"' . $selected . '>&nbsp;&nbsp;&nbsp;' . html($subsection) . "</option>\n";
    }
}
$dbh = null;
?>
    </select></p>

    <p><label for="startDate">Start date</label><br />
    <input type="text" class="datepicker h" id="startDate" name="startDate"<?php echoIfValue($startDateEdit); ?> /></p>

    <p><label for="duration">Duration (weeks)</label><br />
    <input type="number" id="duration" name="duration" class="h"<?php echoIfValue($durationEdit); ?> /></p>

    <p><label for="image">Photo upload (JPG image only)</label><br />
    <input id="image" name="image" type="file" class="w" accept="image/jpeg"></p>

    <p>Up to seven images may be included in an ad. Upload one image at a time. Edit the listing to add each additional image. JPG is the only permitted image format. The best image size is 1920 pixels wide. Larger images are reduced to that width.</p>

    <p><input type="submit" class="left" name="addUpdate" value="Add/update"/><input type="submit" name="publish" class="right" value="Publish" /></p>

    <p><input type="submit" class="left" name="photoDelete" value="Delete photos" /><input type="submit" name="delete" class="right" value="Delete ad" /></p>

  </form>
<?php
if (isset($idAdEdit)) {
    foreach ($photosOrdered as $photo) {
        $dbh = new PDO($dbClassifiedsNew);
        $stmt = $dbh->prepare('SELECT photo' . $photo . ' FROM ads WHERE idAd=?');
        $stmt->setFetchMode(PDO::FETCH_NUM);
        $stmt->execute(array($idAdEdit));
        $row = $stmt->fetch();
        $dbh = null;
        if ($row['0'] != '') {
            echo '    <p><img class="w b" src="imagen.php?i=' . muddle($idAdEdit) . $photo . '" alt="" /></p>' . "\n\n";
        }
    }
}
?>
<?php echo "<pre>\n"; print_r($_POST); echo "</pre>\n"; ?>
</body>
</html>
