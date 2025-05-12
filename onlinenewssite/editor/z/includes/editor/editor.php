<?php
/**
 * The editing form, used by both edit and published
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
//
// Variables
//
$altPost = inlinePost('alt');
$bylineEdit = null;
$bylinePost = inlinePost('byline');
$dbFrom = null;
$dbTo = null;
$edit = inlinePost('edit');
$endDateEdit = null;
$endDatePost = inlinePost('endDate');
$expired = null;
$headlineEdit = null;
$headlinePost = securePost('headline');
$idArticle = inlinePost('idArticle');
$idArticleEdit = null;
$idSectionEdit = null;
$idSectionPost = inlinePost('idSection');
$keywordsEdit = null;
$keywordsPost = inlinePost('keywords');
$message = '';
$photoCaptionPost = inlinePost('photoCaption');
$photoCreditPost = inlinePost('photoCredit');
$publicationDateEdit = null;
$publicationDatePost = inlinePost('publicationDate');
$publishPost = inlinePost('publish');
$publishedPost = inlinePost('published');
$sortOrderArticleEdit = null;
$sortOrderArticlePost = inlinePost('sortOrderArticle');
$standfirstEdit = null;
$standfirstPost = securePost('standfirst');
$textEdit = null;
$textPost = securePost('text');
$widthPost = inlinePost('width');
if (!empty($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $_SESSION['message'] = '';
} else {
    $message = '';
}
//
if ($widthPost === 'third') {
    $widthEditFull = null;
    $widthEditThird = ' checked';
} else {
    $widthEditFull = ' checked';
    $widthEditThird = null;
}
//
if ($publicationDatePost === $today) {
    $publicationTimePost = time();
} else {
    $publicationTimePost = strtotime($publicationDatePost);
}
//
if (!empty($bylinePost)) {
    $dbh = new PDO($dbEditors);
    $stmt = $dbh->prepare('SELECT fullName, email FROM users WHERE fullName=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$bylinePost]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        extract($row);
        if (!empty($email)) {
            $bylinePost = '<a href="mailto:' . $email . '">' . $fullName . '</a>';
        }
    }
}
//
if ($use === 'edit') {
    $database = $dbEdit;
    $database2 = $dbEdit2;
    $imagePath = 'imagee.php';
    $imagePath2 = 'imagee2.php';
    $required = '';
} elseif ($use === 'published') {
    $database = $dbPublished;
    $database2 = $dbPublished2;
    $imagePath = 'imagep.php';
    $imagePath2 = 'imagep2.php';
    $required = ' required';
}
if (!isset($_FILES['image'])) {
    $_FILES['image'] = null;
}
if (mb_strlen($textPost) > 500) {
    $summaryPost = preg_replace("'\s+'", ' ', $textPost);
    $summaryPost = mb_substr($summaryPost, 0, 500);
    $tail = mb_strrchr($summaryPost, ' ');
    $summaryPost = rtrim($summaryPost, $tail);
} else {
    $summaryPost = $textPost;
}
if (mb_strpos($summaryPost, '<') === false and mb_strlen($summaryPost) > 1) {
    if (mb_substr($summaryPost, -1) === '.') {
        $summaryPost = $summaryPost . '...';
    } else {
        $summaryPost = $summaryPost . '....';
    }
} else {
    $summaryPost = null;
}
//
// When working with published artciles, move expired articles from published to the archive
//
if ($dbFrom === $dbPublished) {
    $expired = null;
    $dbh = new PDO($dbPublished);
    $stmt = $dbh->query('SELECT idArticle FROM articles WHERE endDate < "' . $today . '"');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        $expired[] = $row['idArticle'];
    }
    $dbh = null;
    if (is_array($expired)) {
        foreach ($expired as $idArticle) {
            include $includesPath . '/editor/moveArticle.php';
        }
        $idArticle = inlinePost('idArticle');
    }
}
//
// Button: Add / Update
//
if (isset($_POST['addUpdate'])) {
    //
    // Create messages if the minimum fields are blank
    //
    if (empty($headlinePost)) {
        $message.= 'Headline is a required field. ';
    }
    if (empty($idSectionPost)) {
        $message.= 'Section is a required field. Define the newspaper sections in administrative settings.';
    }
    if (empty($message)) {
        //
        // Determine insert or update
        //
        if (empty($_POST['existing'])) {
            $dbh = new PDO($dbArticleId);
            $stmt = $dbh->prepare('INSERT INTO articles (headline) VALUES (?)');
            $stmt->execute([null]);
            $idArticle = $dbh->lastInsertId();
            $stmt = $dbh->prepare('UPDATE articles SET idArticle=? WHERE rowid=?');
            $stmt->execute([$idArticle, $idArticle]);
            $dbh = null;
            $dbh = new PDO($database);
            $stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE idArticle=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$idArticle]);
            $row = $stmt->fetch();
            if (empty($row)) {
                $stmt = $dbh->prepare('INSERT INTO articles (idArticle) VALUES (?)');
                $stmt->execute([$idArticle]);
            }
            $dbh = null;
        } else {
            //
            // Adjust sort order when increasing the sort position of an exisiting article
            //
            $dbh = new PDO($database);
            $stmt = $dbh->prepare('SELECT sortOrderArticle FROM articles WHERE idArticle=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$idArticle]);
            $row = $stmt->fetch();
            $dbh = null;
            if ($row
                and !empty($row['sortOrderArticle'])
                and $sortOrderArticlePost > $row['sortOrderArticle']
            ) {
                $sortOrderArticlePost++;
            }
        }
        //
        // Store the image, if any
        //
        if ($_FILES['image']['size'] > 0 and $_FILES['image']['error'] === 0) {
            //
            // Verify JPG file
            //
            $sizes = getimagesize($_FILES['image']['tmp_name']);
            if ($sizes['mime'] === 'image/jpeg') {
                //
                // Variables
                //
                $widthOriginal = $sizes['0'];
                $heightOriginal = $sizes['1'];
                $aspectRatio = $widthOriginal / $heightOriginal;
                $widthHD = 2360;
                $heightHD = round($widthHD / $aspectRatio);
                //
                // Determine if the image is the primary image or a secondary image
                //
                $dbh = new PDO($database);
                $stmt = $dbh->prepare('SELECT originalImageWidth FROM articles WHERE idArticle=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$idArticle]);
                $row = $stmt->fetch();
                $dbh = null;
                if (empty($row['originalImageWidth'])) {
                    //
                    // Primary image
                    //
                    // Save the original image information
                    //
                    $dbh = new PDO($database);
                    $stmt = $dbh->prepare('UPDATE articles SET photoName=?, originalImageWidth=?, originalImageHeight=? WHERE idArticle=?');
                    $stmt->execute([$widthPost, $widthOriginal, $heightOriginal, $idArticle]);
                    $dbh = null;
                    //
                    // Create and save the thumbnail image
                    //
                    $heightThumbnail = 84 * 2; // Double pixels for 4K displays
                    $widthThumbnail = round($heightThumbnail * $aspectRatio);
                    $thumbnail = imagecreatetruecolor($widthThumbnail, $heightThumbnail);
                    imageinterlace($thumbnail, true);
                    $srcImage = imagecreatefromjpeg($_FILES['image']['tmp_name']);
                    imagecopyresampled($thumbnail, $srcImage, 0, 0, 0, 0, $widthThumbnail, $heightThumbnail, ImageSX($srcImage), ImageSY($srcImage));
                    ob_start();
                    imagejpeg($thumbnail, null, 90);
                    imagedestroy($thumbnail);
                    $thumbnailImage = ob_get_contents();
                    ob_end_clean();
                    $heightThumbnail = $heightThumbnail / 2; // Display size
                    $widthThumbnail = round($widthThumbnail / 2);
                    $dbh = new PDO($database);
                    $stmt = $dbh->prepare('UPDATE articles SET thumbnailImage=?, thumbnailImageWidth=?, thumbnailImageHeight=? WHERE idArticle=?');
                    $stmt->execute([$thumbnailImage, $widthThumbnail, $heightThumbnail, $idArticle]);
                    $dbh = null;
                    //
                    // Create and save the HD image, photo credit and caption
                    //
                    $hd = imagecreatetruecolor($widthHD, $heightHD);
                    imageinterlace($hd, true);
                    $srcImage = imagecreatefromjpeg($_FILES['image']['tmp_name']);
                    imagecopyresampled($hd, $srcImage, 0, 0, 0, 0, $widthHD, $heightHD, ImageSX($srcImage), ImageSY($srcImage));
                    ob_start();
                    imagejpeg($hd, null, 83);
                    imagedestroy($hd);
                    $hdImage = ob_get_contents();
                    ob_end_clean();
                    if (empty($altPost)) {
                        $altPost = $photoCaptionPost;
                    }
                    $dbh = new PDO($database);
                    $stmt = $dbh->prepare('UPDATE articles SET photoCredit=?, photoCaption=?, alt=?, hdImage=?, hdImageWidth=?, hdImageHeight=? WHERE idArticle=?');
                    $stmt->execute([$photoCreditPost, $photoCaptionPost, $altPost, $hdImage, $widthHD, $heightHD, $idArticle]);
                    $dbh = null;
                } else {
                    //
                    // Secondary images
                    //
                    // Create and save the HD image, photo credit and caption
                    //
                    $hd = imagecreatetruecolor($widthHD, $heightHD);
                    imageinterlace($hd, true);
                    $srcImage = imagecreatefromjpeg($_FILES['image']['tmp_name']);
                    imagecopyresampled($hd, $srcImage, 0, 0, 0, 0, $widthHD, $heightHD, ImageSX($srcImage), ImageSY($srcImage));
                    ob_start();
                    imagejpeg($hd, null, 83);
                    imagedestroy($hd);
                    $hdImage = ob_get_contents();
                    ob_end_clean();
                    $dbh = new PDO($dbPhotoId);
                    $stmt = $dbh->prepare('INSERT INTO photos (idArticle) VALUES (?)');
                    $stmt->execute([null]);
                    $idPhoto = $dbh->lastInsertId();
                    $stmt = $dbh->prepare('UPDATE photos SET idPhoto=? WHERE rowid=?');
                    $stmt->execute([$idPhoto, $idPhoto]);
                    $dbh = null;
                    if (empty($altPost)) {
                        $altPost = $photoCaptionPost;
                    }
                    $dbh = new PDO($database2);
                    $stmt = $dbh->prepare('INSERT INTO imageSecondary (idPhoto, idArticle, image, photoName, photoCredit, photoCaption, alt, time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$idPhoto, $idArticle, $hdImage, $widthPost, $photoCreditPost, $photoCaptionPost, $altPost, time()]);
                    $dbh = null;
                }
            } else {
                $message = 'The uploaded file was not in the JPG format.';
            }
        } elseif (isset($_FILES['image']['error']) and $_FILES['image']['error'] === 1) {
            $message = 'The uploaded image exceeds the upload_max_filesize directive in php.ini.';
        } elseif (isset($_FILES['image']['error']) and ($_FILES['image']['error'] !== 4 and $_FILES['image']['error'] !== 1)) {
            $message = 'There was an unknown error with the uploaded image.';
        }
        //
        // Send the non-image information to the database
        //
        $dbh = new PDO($database);
        $stmt = $dbh->prepare('UPDATE articles SET publicationDate=?, publicationTime=?, endDate=?, idSection=?, sortOrderArticle=?, byline=?, headline=?, standfirst=?, text=?, summary=? WHERE idArticle=?');
        $stmt->execute([$publicationDatePost, $publicationTimePost, $endDatePost, $idSectionPost, $sortOrderArticlePost, $bylinePost, $headlinePost, $standfirstPost, $textPost, $summaryPost, $idArticle]);
        $dbh = null;
        //
        // Update the sort order of published articles
        //
        if ($use === 'published') {
            $archive = null;
            $idSection = $idSectionPost;
            include $includesPath . '/editor/sortPublished.php';
        }
    }
}
//
// Button: Delete photos
//
if (isset($_POST['deletePhoto']) and isset($idArticle)) {
    $dbh = new PDO($database);
    $stmt = $dbh->prepare('UPDATE articles SET photoName=?, photoCredit=?, photoCaption=?, alt=?, originalImageWidth=?, originalImageHeight=?, thumbnailImage=?, thumbnailImageWidth=?, thumbnailImageHeight=?, hdImage=?, hdImageWidth=?, hdImageHeight=? WHERE idArticle=?');
    $stmt->execute([null, null, null, null, null, null, null, null, null, null, null, null, $idArticle]);
    $dbh = null;
    $dbh = new PDO($database2);
    $stmt = $dbh->prepare('DELETE FROM imageSecondary WHERE idArticle=?');
    $stmt->execute([$idArticle]);
    $dbh = null;
}
//
// Button: Delete
//
if (isset($_POST['delete']) and isset($idArticle)) {
    $dbh = new PDO($database);
    $stmt = $dbh->prepare('DELETE FROM articles WHERE idArticle=?');
    $stmt->execute([$idArticle]);
    $dbh = null;
    $dbh = new PDO($database2);
    $stmt = $dbh->prepare('DELETE FROM imageSecondary WHERE idArticle=?');
    $stmt->execute([$idArticle]);
    $dbh = null;
    $dbh = new PDO($dbSurvey);
    $stmt = $dbh->prepare('DELETE FROM answers WHERE idArticle=?');
    $stmt->execute([$idArticle]);
    $dbh = null;
    $dbh = new PDO($dbArchive);
    $stmt = $dbh->prepare('DELETE FROM articles WHERE idArticle=?');
    $stmt->execute([$idArticle]);
    $dbh = null;
}
//
// Button: Edit
//
if (isset($_POST['edit'])) {
    $dbh = new PDO($database);
    $stmt = $dbh->prepare('SELECT publicationDate, endDate, keywords, idSection, sortOrderArticle, byline, headline, standfirst, text FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idArticle]);
    $row = $stmt->fetch();
    $dbh = null;
    extract($row);
    $bylineEdit = $byline;
    $endDateEdit = $endDate;
    $headlineEdit = $headline;
    $idArticleEdit = $idArticle;
    $keywordsEdit = $keywords;
    $idSectionEdit = $idSection;
    $publicationDateEdit = $publicationDate;
    $sortOrderArticleEdit = $sortOrderArticle;
    $standfirstEdit = $standfirst;
    $textEdit = $text;
    $message = '';
}
//
// Buttons: Up or down arrows
//
if (isset($_POST['up']) or isset($_POST['down'])) {
    include $includesPath . '/editor/sortPublished.php';
}
//
// Buttons: Publish and Archive
//
if (isset($_POST['publish'])
    and $_POST['publish'] === 'Publish'
    and (!isset($_POST['addUpdate'])
    and !isset($_POST['delete'])
    and !isset($_POST['deletePhoto'])
    and !isset($_POST['down'])
    and !isset($_POST['edit'])
    and !isset($_POST['up']))
) {
    $dbh = new PDO($database);
    $stmt = $dbh->prepare('SELECT idSection, publicationDate, endDate FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idArticle]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        //
        // Set the database for publish or archive
        //
        extract($row);
        if (isset($_POST['archive']) and $_POST['archive'] === 'Archive') {
            $dbFrom = $dbPublished;
        } else {
            $dbFrom = $dbEdit;
        }
        if (empty($publicationDate) or empty($endDate)) {
            $message = 'For publication, dates are required fields.';
        } else {
            //
            // Move the article
            //
            include $includesPath . '/editor/moveArticle.php';
            include $includesPath . '/editor/sortPublished.php';
        }
    }
}
//
// When not working with published artciles, move expired articles from published to the archive
//
if ($dbFrom !== $dbPublished) {
    $expired = null;
    $dbh = new PDO($dbPublished);
    $stmt = $dbh->query('SELECT idArticle FROM articles WHERE endDate < "' . $today . '"');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        $expired[] = $row['idArticle'];
    }
    $dbh = null;
    $dbFrom = $dbPublished;
    if (is_array($expired)) {
        foreach ($expired as $idArticle) {
            include $includesPath . '/moveArticle.php';
        }
    }
}
//
// HTML
//
require $includesPath . '/editor/header1.inc';
echo '  <title>' . $title . "</title>\n";
?>
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

<?php
require $includesPath . '/editor/body.inc';
echo $menu;
echo '  <div class="column">' . "\n";
echo '    <h1>' . $title . "</h1>\n";
echoIfMessage($message);
?>

    <form method="post" action="<?php echo $uri . $use; ?>.php" enctype="multipart/form-data">
      <p><label for="byline">Byline</label><br>
      <input id="byline" name="byline" class="wide" list="bylineList"<?php echoIfValue($bylineEdit); ?>><input type="hidden" name="idArticle" value="<?php echo $idArticleEdit; ?>"></p>
      <datalist id="bylineList">
<?php
$dbh = new PDO($dbEditors);
$stmt = $dbh->prepare('SELECT user, fullName FROM users WHERE userType=? ORDER BY fullName');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([1]);
foreach ($stmt as $row) {
    extract($row);
    if ($user !== 'admin') {
        echo '        <option label="' . $fullName . '" value="' . $fullName . '">' . "\n";
    }
}
$dbh = null;
?>
      </datalist>

      <p><label for="publicationDate">Publication dates, start date to end date (expired articles move to the archives)</label><br>
      <input id="publicationDate" name="publicationDate" class="datepicker date"<?php echoIfValue($publicationDateEdit); echo $required; ?>> <input name="endDate" class="datepicker date"<?php echoIfValue($endDateEdit); echo $required; ?>></p>

      <p><label for="idSection">Section</label><br>
      <select id="idSection" name="idSection">
<?php
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT idSection, section FROM sections ORDER BY sortOrderSection');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    $selected = $idSectionEdit === $row['idSection'] ? ' selected="selected"' : null;
    echo '        <option value="' . $row['idSection'] . '"' . $selected . '>' . $row['section'] . "</option>\n";
}
$dbh = null;
?>
      </select></p>

<?php
if ($use === 'published') {
    echo '      <p><span class="rp">Sort order within section<br>' . "\n";
    echo '      <select name="sortOrderArticle">' . "\n";
    $count = 1;
    $dbh = new PDO($dbPublished);
    $stmt = $dbh->query('SELECT DISTINCT sortOrderArticle FROM articles ORDER BY sortOrderArticle');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        extract($row);
        $selected = ($sortOrderArticle === $count and $sortOrderArticle === $sortOrderArticleEdit) ? ' selected="selected"' : null;
        echo '        <option value="' . $count . '"' . $selected . '>' . $count . '</option>' . "\n";
        $count++;
    }
    $dbh = null;
    echo '        <option value="' . $count . '">' . $count . '</option>' . "\n";
    echo '      </select></span></p>' . "\n\n";
}
?>
      <p><label for="headline">Headline</label><br>
      <input id="headline" name="headline" class="wide" <?php echoIfValue($headlineEdit); ?>></p>

      <p><label for="standfirst">Standfirst</label><br>
      <input id="standfirst" name="standfirst" class="wide"<?php echoIfValue($standfirstEdit); ?>></p>

      <p><label for="text">Article text is entered in either HTML or the <a href="markdown.html" target="_blank">markdown syntax</a>. Enter iframe and video tags inside paragraph tags, for example, &lt;p&gt;&lt;iframe height="315"&gt;&lt;/iframe&gt;&lt;/p&gt;. Do not enter a width attribute.</label><br>
      <textarea id="text" name="text" rows="9" class="wide"><?php echoIfText($textEdit); ?></textarea></p>

      <p><label for="image">Photo upload (JPG image only<?php uploadFilesizeMaximum(); ?>)</label><br>
      <input id="image" name="image" type="file" class="wide" accept="image/jpeg"></p>

      <p><label for="full"><input type="radio" name="width" id="full" value=""<?php echo $widthEditFull; ?>> Full width</label> <label for="third"><input type="radio" name="width" id="third" value="third"<?php echo $widthEditThird; ?>> One-third width</label></p>

      <p><label for="photoCaption">Photo caption</label><br>
      <input id="photoCaption" name="photoCaption" class="wide" autocomplete="on"></p>

      <p><label for="alt">Alt text (if different from the caption)</label><br>
      <input id="alt" name="alt" class="wide" autocomplete="on"></p>

      <p><label for="photoCredit">Photo credit</label><br>
      <input id="photoCredit" name="photoCredit" class="wide"></p>

      <p><input type="submit" class="button" value="Add / update" name="addUpdate"> <input type="submit" class="button" value="Delete photos" name="deletePhoto"><input type="hidden" name="existing"<?php echoIfValue($edit); ?>>&nbsp;&nbsp;&nbsp;&nbsp;<a href="<?php echo $uri; ?>survey.php" target="_blank">Create or edit a survey</a></p>
    </form>

    <p>When there are photos, upload the primary photo first. Then edit the article to upload additional photos one at a time. To correct any photo error — width, caption, credit, order — delete the photos and begin again.</p>

<?php
require $includesPath . '/editor/displayIndex.inc';
?>
  </div>
</body>
</html>
