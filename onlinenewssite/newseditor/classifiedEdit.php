<?php
/**
 * For management to place classified ads
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2018 12 21
 * @link      https://hardcoverwebdesign.com/
 * @link      https://online-news-site.com/
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
$stmt->execute([$_SESSION['userId']]);
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
$invoiceEdit = inlinePost('invoice');
$message = null;
$startDateEdit = null;
$startDatePost = inlinePost('startDate');
$startTime = strtotime($startDatePost);
$review = date("Y-m-d", $startTime + ($durationPost * 7 * 86400));
$titleEdit = null;
$titlePost = inlinePost('title');
$titleSearchPost = inlinePost('titleSearch');
$photosOrdered = [1, 2, 3, 4, 5, 6, 7];
$photosReverse = array_reverse($photosOrdered);
$photoAvailable = null;
$searchResults = [];
//
// Button: Search
//
if (isset($_POST['search']) and isset($_POST['titleSearch'])) {
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('SELECT idAd, email, title, description FROM ads WHERE title LIKE ? ORDER BY title, description');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(['%'. $titleSearchPost .'%']);
    foreach ($stmt as $row) {
        extract($row);
        $searchResults[] = $row;
    }
}
//
// Button: Update
//
if (isset($_POST['update']) and isset($idAdPost)) {
    if (empty($emailPost)) {
        $emailPost = $_SESSION['username'];
    }
    if (is_null($titlePost)) {
        $message = 'Title is a required field.';
    }
    if (is_null($descriptionPost)) {
        $message = 'Description is a required field.';
    }
    if (is_null($categoryIdPost)) {
        $message = 'Category is a required field and must be a subcategory.';
    }
    if (is_null($startDatePost)) {
        $message = 'Start date is a required field.';
    }
    if (is_null($durationPost)) {
        $message = 'Duration is a required field.';
    }
    if (is_null($startDatePost) and is_null($durationPost)) {
        $message = 'Start date and duration are required fields.';
    }
    if (isset($titlePost) and isset($descriptionPost) and isset($categoryIdPost) and isset($startDatePost) and isset($durationPost)) {
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
                    $dbh = new PDO($dbClassifieds);
                    $stmt = $dbh->prepare('SELECT photo' . $photo . ' FROM ads WHERE idAd=?');
                    $stmt->setFetchMode(PDO::FETCH_NUM);
                    $stmt->execute([$idAdPost]);
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
                    if ($widthOriginal > 2370) {
                        $widthHD = 2370;
                        $heightHD = round($widthHD / $aspectRatio);
                        $hd = imagecreatetruecolor($widthHD, $heightHD);
                        imageinterlace($hd, true);
                        $srcImage = imagecreatefromjpeg($_FILES['image']['tmp_name']);
                        imagecopyresampled($hd, $srcImage, 0, 0, 0, 0, $widthHD, $heightHD, ImageSX($srcImage), ImageSY($srcImage));
                        ob_start();
                        imagejpeg($hd, null, 83);
                        imagedestroy($hd);
                        $hdImage = ob_get_contents();
                        ob_end_clean();
                    } else {
                        $hdImage = file_get_contents($_FILES['image']['tmp_name']);
                    }
                    $dbh = new PDO($dbClassifieds);
                    $stmt = $dbh->prepare('UPDATE ads SET photo' . $photoAvailable . '=? WHERE idAd=?');
                    $stmt->execute([$hdImage, $idAdPost]);
                    $dbh = null;
                }
                $num = [];
            } else {
                $message = 'The uploaded file was not in the JPG format.';
            }
        }
        //
        // Update the other fields
        //
        foreach ($photosOrdered as $photo) {
            $dbh = new PDO($dbClassifieds);
            $stmt = $dbh->prepare('SELECT photo' . $photo . ' FROM ads WHERE idAd=?');
            $stmt->setFetchMode(PDO::FETCH_NUM);
            $stmt->execute([$idAdPost]);
            $row = $stmt->fetch();
            $dbh = null;
            if (isset($row['0'])) {
                $num[] = 1;
            } else {
                $num[] = 0;
            }
        }
        $photosPublished = json_encode($num);
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->prepare('UPDATE ads SET title=?, description=?, categoryId=?, review=?, startDate=?, duration=?, photos=? WHERE idAd=?');
        $stmt->execute([$titlePost, $descriptionPost, $categoryIdPost, $review, $startDatePost, $durationPost, $photosPublished, $idAdPost]);
        $dbh = null;
        include $includesPath . '/addUpdateClassified.php';
        include $includesPath . '/syncClassifieds.php';
        $categoryIdEdit = $categoryIdPost;
        $descriptionEdit = $descriptionPost;
        $durationEdit = $durationPost;
        $emailEdit = $emailPost;
        $idAdEdit = $idAdPost;
        $reviewEdit = $review;
        $startDateEdit = $startDatePost;
        $titleEdit = $titlePost;
    }
}
//
// Button: Delete photos
//
if (isset($_POST['photoDelete']) and isset($idAdPost)) {
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('UPDATE ads SET photos=?, photo1=?, photo2=?, photo3=?, photo4=?, photo5=?, photo6=?, photo7=? WHERE idAd=?');
    $stmt->execute(['[0,0,0,0,0,0,0]', null, null, null, null, null, null, null, $idAdPost]);
    $dbh = null;
    include $includesPath . '/addUpdateClassified.php';
    include $includesPath . '/syncClassifieds.php';
    $categoryIdEdit = $categoryIdPost;
    $descriptionEdit = $descriptionPost;
    $durationEdit = $durationPost;
    $idAdEdit = $idAdPost;
    $startDateEdit = $startDatePost;
    $titleEdit = $titlePost;
}
//
// Button: Delete ad
//
if (isset($_POST['delete']) and isset($idAdPost)) {
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('DELETE FROM ads WHERE idAd=?');
    $stmt->execute([$idAdPost]);
    $dbh = null;
    include $includesPath . '/syncClassifieds.php';
}
//
// Button: Edit
//
if (isset($_POST['edit']) and isset($_POST['idAd'])) {
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('SELECT idAd, email, title, description, categoryId, review, startDate, duration FROM ads WHERE idAd=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idAdPost]);
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
  <script src="z/jquery.min.js"></script>
  <script src="z/jquery-ui.min.js"></script>
  <script src="z/datepicker.js"></script>
</head>
<?php require $includesPath . '/body.inc'; ?>

  <h4 class="m"><a class="m" href="classifieds.php">&nbsp;Pending review&nbsp;</a><a class="m" href="classifiedCreate.php">&nbsp;Create&nbsp;</a><a class="s" href="classifiedEdit.php">&nbsp;Edit&nbsp;</a></h4>
<?php echoIfMessage($message); ?>

  <h1 id="waiting">Please wait.</h1>

  <h1><span class="h">Search results</span></h1>
<?php
foreach ($searchResults as $searchResult) {
    echo '  <form class="wait" action="' . $uri . 'classifiedEdit.php" method="post">' . "\n";
    echo '    <p><span class="p">' . $title . " - Title<br />\n";
    echo '    ' . plain($email) . " - By<br />\n";
    echo '    ' . $description . " - Description<br />\n";
    echo '    <input name="idAd" type="hidden" value="' . $idAd . '" /><input type="submit" value="Edit" name="edit" class="button" /></span></p>' . "\n";
    echo "  </form>\n\n";
}
?>

  <h1>Edit a published classified ad</h1>

  <form class="wait" action="<?php echo $uri; ?>classifiedEdit.php" method="post" enctype="multipart/form-data">
    <p><label for="titleSearch">Search for an ad by title</label><br />
    <input id="titleSearch" name="titleSearch" type="text" class="h"<?php echoIfValue($titleSearchPost); ?> /></p>

    <p><input type="submit" class="button" name="search" value="Search" /></p>

    <p><label for="title">Title</label><br />
    <input id="title" name="title" type="text" class="h"<?php echoIfValue($titleEdit); ?> /><input type="hidden" name="idAd"<?php echoIfValue($idAdEdit); ?> /></p>

    <p><label for="description">Description</label><br />
    <span class="hl"><textarea id="description" name="description" class="h"><?php echoIfText($descriptionEdit); ?></textarea></span><p>

    <p><label for="invoice"><input id="invoice" name="invoice" type="checkbox" value="1"<?php echoIfYes($invoiceEdit); ?> /> Send an invoice to also have the add in the print version of the paper.</label></p>

    <p><label for="categoryId">Categories (select a subcategory)</label><br />
    <select id="categoryId" name="categoryId" size="1" class="h">
<?php
$dbh = new PDO($dbClassifieds);
$stmt = $dbh->query('SELECT idSection, section FROM sections ORDER BY sortOrderSection');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    extract($row);
    echo '        <option value="">' . html($section) . "</option>\n";
    $stmt = $dbh->prepare('SELECT idSubsection, subsection FROM subsections WHERE parentId=? ORDER BY sortOrderSubsection');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idSection]);
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

    <p><label for="image">Photo upload (JPG image only<?php uploadFilesizeMaximum(); ?>)</label><br />
    <input id="image" name="image" type="file" class="h" accept="image/jpeg"></p>

    <p>Up to seven images may be included in an ad. Upload one image at a time. Edit the listing to add each additional image. JPG is the only permitted image format. The best image size is 2370 pixels or wider. Larger images are reduced to that width.</p>

    <p><input type="submit" class="button" name="update" value="Update"/></p>

    <p><input type="submit" class="button" name="photoDelete" value="Delete photos" /></p>

    <p><input type="submit" class="button" name="delete" value="Delete ad" /></p>

  </form>
<?php
if (isset($idAdEdit)) {
    foreach ($photosOrdered as $photo) {
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->prepare('SELECT photo' . $photo . ' FROM ads WHERE idAd=?');
        $stmt->setFetchMode(PDO::FETCH_NUM);
        $stmt->execute([$idAdEdit]);
        $row = $stmt->fetch();
        $dbh = null;
        if ($row['0'] != '') {
            echo '    <p><img class="w b" src="imagec.php?i=' . muddle($idAdEdit) . $photo . '" alt="" /></p>' . "\n\n";
        }
    }
}
?>
</body>
</html>
