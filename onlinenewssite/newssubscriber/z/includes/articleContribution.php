<?php
/**
 * The editing form, used by both edit and published
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2016 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *.@license   http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2016-10-16
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
//
// Variables
//
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
$html = null;
$idArticle = inlinePost('idArticle');
$idArticleEdit = null;
$idSectionEdit = null;
$idSectionPost = inlinePost('idSection');
$keywordsEdit = null;
$keywordsPost = inlinePost('keywords');
$message = null;
$photoCaptionPost = inlinePost('photoCaption');
$photoCreditPost = inlinePost('photoCredit');
$publicationDateEdit = null;
$publicationDatePost = inlinePost('publicationDate');
$publishPost = inlinePost('publish');
$publishedPost = inlinePost('published');
$sortOrderArticleEdit = null;
$sortOrderArticlePost = time() + (15 * 60);
$standfirstEdit = null;
$standfirstPost = securePost('standfirst');
$textEdit = null;
$textPost = securePost('text');
//
$dbEdit = $dbEdit;
$dbEdit2 = $dbEdit2;
$imagePath = 'imagee.php';
$imagePath2 = 'imagee2.php';
if (!isset($_FILES['image'])) {
    $_FILES['image'] = null;
}
if (strlen($textPost) > 500) {
    $summaryPost = substr(preg_replace("'\s+'", ' ', $textPost), 0, 500);
    $summaryPost = str_replace(strrchr($summaryPost, ' '), ' ', $summaryPost);
} else {
    $summaryPost = $textPost;
}
if (strpos($summaryPost, '<') === false) {
    $summaryPost = $summaryPost . ' (continued)';
} else {
    $summaryPost = null;
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
            $dbh = new PDO($dbArchive);
            $stmt = $dbh->prepare('INSERT INTO articles (headline) VALUES (?)');
            $stmt->execute(array(null));
            $idArticle = $dbh->lastInsertId();
            $stmt = $dbh->prepare('UPDATE articles SET idArticle=? WHERE rowid=?');
            $stmt->execute(array($idArticle, $idArticle));
            $dbh = null;
            $dbh = new PDO($dbEdit);
            $stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE idArticle=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute(array($idArticle));
            $row = $stmt->fetch();
            if (empty($row)) {
                $stmt = $dbh->prepare('INSERT INTO articles (idArticle) VALUES (?)');
                $stmt->execute(array($idArticle));
            }
        }
        //
        // Store the image, if any
        //
        if ($_FILES['image']['size'] > 0 and $_FILES['image']['error'] == 0) {
            //
            // Verify JPG file
            //
            $sizes = getimagesize($_FILES['image']['tmp_name']);
            if ($sizes['mime'] == 'image/jpeg') {
                //
                // Variables
                //
                $widthOriginal = $sizes['0'];
                $heightOriginal = $sizes['1'];
                $aspectRatio = $widthOriginal / $heightOriginal;
                $widthHD = 1920;
                $heightHD = round($widthHD / $aspectRatio);
                //
                // Determine if the image is the primary image or a secondary image
                //
                $dbh = new PDO($dbEdit);
                $stmt = $dbh->prepare('SELECT originalImageWidth FROM articles WHERE idArticle=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute(array($idArticle));
                $row = $stmt->fetch();
                $dbh = null;
                if (empty($row['originalImageWidth'])) {
                    //
                    // Primary image
                    //
                    // Save the original image dimensions
                    //
                    $dbh = new PDO($dbEdit);
                    $stmt = $dbh->prepare('UPDATE articles SET originalImageWidth=?, originalImageHeight=? WHERE idArticle=?');
                    $stmt->execute(array($widthOriginal, $heightOriginal, $idArticle));
                    $dbh = null;
                    //
                    // Create and save the thumbnail image
                    //
                    $heightThumbnail = 84 * 2; // Double pixels for 4K displays
                    $widthThumbnail = round($heightThumbnail * $aspectRatio);
                    $thumbnail = imagecreatetruecolor($widthThumbnail, $heightThumbnail);
                    $srcImage = imagecreatefromjpeg($_FILES['image']['tmp_name']);
                    imagecopyresized($thumbnail, $srcImage, 0, 0, 0, 0, $widthThumbnail, $heightThumbnail, ImageSX($srcImage), ImageSY($srcImage));
                    ob_start();
                    imagejpeg($thumbnail, null, 90);
                    imagedestroy($thumbnail);
                    $thumbnailImage = ob_get_contents();
                    ob_end_clean();
                    $heightThumbnail = $heightThumbnail / 2; // Display size
                    $widthThumbnail = round($widthThumbnail / 2);
                    $dbh = new PDO($dbEdit);
                    $stmt = $dbh->prepare('UPDATE articles SET thumbnailImage=?, thumbnailImageWidth=?, thumbnailImageHeight=? WHERE idArticle=?');
                    $stmt->execute(array($thumbnailImage, $widthThumbnail, $heightThumbnail, $idArticle));
                    $dbh = null;
                    //
                    // Create and save the HD image, photo credit and caption
                    //
                    $hd = imagecreatetruecolor($widthHD, $heightHD);
                    $srcImage = imagecreatefromjpeg($_FILES['image']['tmp_name']);
                    imagecopyresized($hd, $srcImage, 0, 0, 0, 0, $widthHD, $heightHD, ImageSX($srcImage), ImageSY($srcImage));
                    ob_start();
                    imagejpeg($hd, null, 90);
                    imagedestroy($hd);
                    $hdImage = ob_get_contents();
                    ob_end_clean();
                    $dbh = new PDO($dbEdit);
                    $stmt = $dbh->prepare('UPDATE articles SET photoCredit=?, photoCaption=?, hdImage=?, hdImageWidth=?, hdImageHeight=? WHERE idArticle=?');
                    $stmt->execute(array($photoCreditPost, $photoCaptionPost, $hdImage, $widthHD, $heightHD, $idArticle));
                    $dbh = null;
                } else {
                    //
                    // Secondary images
                    //
                    // Create and save the HD image, photo credit and caption
                    //
                    $hd = imagecreatetruecolor($widthHD, $heightHD);
                    $srcImage = imagecreatefromjpeg($_FILES['image']['tmp_name']);
                    imagecopyresized($hd, $srcImage, 0, 0, 0, 0, $widthHD, $heightHD, ImageSX($srcImage), ImageSY($srcImage));
                    ob_start();
                    imagejpeg($hd, null, 90);
                    imagedestroy($hd);
                    $hdImage = ob_get_contents();
                    ob_end_clean();
                    $dbh = new PDO($dbEdit2);
                    $stmt = $dbh->prepare('INSERT INTO imageSecondary (idArticle, image, photoCredit, photoCaption, time) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute(array($idArticle, $hdImage, $photoCreditPost, $photoCaptionPost, time()));
                    $dbh = null;
                }
            } else {
                $message = 'The uploaded file was not in the JPG format.';
            }
        } elseif (isset($_FILES['image']['error']) and $_FILES['image']['error'] == 1) {
            $message = 'The uploaded image exceeds the upload_max_filesize directive in php.ini.';
        } elseif (isset($_FILES['image']['error']) and ($_FILES['image']['error'] != 4 and $_FILES['image']['error'] != 1)) {
            $message = 'There was an unknown error with the uploaded image.';
        }
        //
        // Send the non-image information to the database
        //
        $dbh = new PDO($dbEdit);
        $stmt = $dbh->prepare('UPDATE articles SET userId=?, publicationDate=?, endDate=?, idSection=?, sortOrderArticle=?, byline=?, headline=?, standfirst=?, text=?, summary=? WHERE idArticle=?');
        $stmt->execute(array($_SESSION['userId'], $publicationDatePost, $endDatePost, $idSectionPost, $sortOrderArticlePost, $bylinePost, $headlinePost, $standfirstPost, $textPost, $summaryPost, $idArticle));
        $dbh = null;
    }
}
//
// Button: Delete photos
//
if (isset($_POST['deletePhoto']) and isset($idArticle)) {
    $dbh = new PDO($dbEdit);
    $stmt = $dbh->prepare('UPDATE articles SET photoCredit=?, photoCaption=?, originalImageWidth=?, originalImageHeight=?, thumbnailImage=?, thumbnailImageWidth=?, thumbnailImageHeight=?, hdImage=?, hdImageWidth=?, hdImageHeight=? WHERE idArticle=?');
    $stmt->execute(array(null, null, null, null, null, null, null, null, null, null, $idArticle));
    $dbh = null;
    $dbh = new PDO($dbEdit2);
    $stmt = $dbh->prepare('DELETE FROM imageSecondary WHERE idArticle=?');
    $stmt->execute(array($idArticle));
    $dbh = null;
}
//
// Button: Delete
//
if (isset($_POST['delete']) and isset($idArticle)) {
    $dbh = new PDO($dbEdit);
    $stmt = $dbh->prepare('DELETE FROM articles WHERE idArticle=?');
    $stmt->execute(array($idArticle));
    $dbh = null;
    $dbh = new PDO($dbEdit2);
    $stmt = $dbh->prepare('DELETE FROM imageSecondary WHERE idArticle=?');
    $stmt->execute(array($idArticle));
    $dbh = null;
    $dbh = new PDO($dbArchive);
    $stmt = $dbh->prepare('DELETE FROM articles WHERE idArticle=?');
    $stmt->execute(array($idArticle));
    $dbh = null;
}
//
// Button: Edit
//
if (isset($_POST['edit'])) {
    $dbh = new PDO($dbEdit);
    $stmt = $dbh->prepare('SELECT keywords, idSection, byline, headline, standfirst, text FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idArticle));
    $row = $stmt->fetch();
    $dbh = null;
    extract($row);
    $bylineEdit = $byline;
    $headlineEdit = $headline;
    $idArticleEdit = $idArticle;
    $keywordsEdit = $keywords;
    $idSectionEdit = $idSection;
    $standfirstEdit = $standfirst;
    $textEdit = $text;
}
//
// HTML
//
echo "  <h1>Article contribution</h1>\n";
echoIfMessage($message);
?>
  <p>Fifteen minutes from the last time an edit was made to a submitted article, the article becomes available to be sent to the editor after which it will no longer be available for edit here.</p>

  <form class="wait" method="post" action="<?php echo $uri; ?>?m=article-contribution" enctype="multipart/form-data">
    <p><label for="byline">Byline</label><br />
    <input id="byline" name="byline" type="text" class="w" <?php echoIfValue($bylineEdit); ?> /><input type="hidden" name="idArticle" value="<?php echo $idArticleEdit; ?>"></p>

    <p><label for="idSection">Section</label><br />
    <select id="idSection" name="idSection">
<?php
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT idSection, section FROM sections ORDER BY sortOrderSection');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    $selected = $idSectionEdit == $row['idSection'] ? ' selected="selected"' : null;
    echo '      <option value="' . $row['idSection'] . '"' . $selected . '>' . $row['section'] . "</option>\n";
}
$dbh = null;
?>
    </select></p>

<?php
if ($use == 'published') {
    echo '    <p><span class="rp">Sort order within section<br />' . "\n";
    echo '    <select name="sortOrderArticle">' . "\n";
    $count = 1;
    $dbh = new PDO($dbPublished);
    $stmt = $dbh->query('SELECT DISTINCT sortOrderArticle FROM articles ORDER BY sortOrderArticle');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        extract($row);
        $selected = ($sortOrderArticle == $count and $sortOrderArticle == $sortOrderArticleEdit) ? ' selected="selected"' : null;
        echo '      <option value="' . $count . '"' . $selected . '>' . $count . '</option>' . "\n";
        $count++;
    }
    $dbh = null;
    echo '      <option value="' . $count . '">' . $count . '</option>' . "\n";
    echo "    </select></span></p>\n\n";
}
?>
    <p><label for="headline">Headline</label><br />
    <input id="headline" name="headline" type="text" class="w" <?php echoIfValue($headlineEdit); ?> /></p>

    <p><label for="standfirst">Standfirst</label><br />
    <input id="standfirst" name="standfirst" type="text" class="w"<?php echoIfValue($standfirstEdit); ?> /></p>

    <p><label for="text">Article text is entered in either HTML or the <a href="http://daringfireball.net/projects/markdown/syntax/" target="_blank">markdown syntax</a>. Enter iframe and video tags inside paragraph tags, for example, &lt;p&gt;&lt;iframe&gt;&lt;/iframe&gt;&lt;/p&gt;.</label><br />
    <textarea id="text" name="text" rows="9" class="w"><?php echoIfText($textEdit); ?></textarea></p>

    <p><label for="image">Photo upload (JPG image only)</label><br />
    <input id="image" name="image" type="file" class="w" accept="image/jpeg" /></p>

    <p><label for="photoCaption">Photo caption</label><br />
    <input id="photoCaption" name="photoCaption" type="text" class="w" autocomplete="on" /></p>

    <p><label for="photoCredit">Photo credit</label><br />
    <input id="photoCredit" name="photoCredit" type="text" class="w" /></p>

    <p><input type="submit" class="button" value="Add / update" name="addUpdate" /> <input type="submit" class="button" value="Delete photos" name="deletePhoto" /><input type="hidden" name="existing"<?php echoIfValue($edit); ?> /></p>
  </form>

  <p>When there are photos, upload the primary photo first. Then edit the article to upload secondary photos one at a time. To correct an error in a caption or in the order of the photos, delete the photos and begin again.</p>
<?php
$dbhSection = new PDO($dbSettings);
$stmt = $dbhSection->query('SELECT idSection, section FROM sections ORDER BY sortOrderSection');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    extract($row);
    $dbh = new PDO($dbEdit);
    $stmt = $dbh->prepare('SELECT idSection FROM articles WHERE idSection=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idSection));
    $row = $stmt->fetch();
    if ($row) {
        $count = null;
        $html.= "\n" . '  <h4>' . $section . "</h4>\n\n";
        $stmt = $dbh->prepare('SELECT idArticle, headline, summary, thumbnailImageWidth, thumbnailImageHeight FROM articles WHERE idSection = ? AND userId=? ORDER BY idArticle DESC');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(array($idSection, $_SESSION['userId']));
        foreach ($stmt as $row) {
            extract($row);
            if ($count != null) {
                $html.= "  <hr />\n\n";
            }
            $count++;
            $html.= '  <h2>' . html($headline) . "</h2>\n\n";
            if ($summary != '') {
                $html.= '  <p class="s"><a href="' . $use . '.php?a=' . $idArticle . '">';
                if ($thumbnailImageWidth != null) {
                    $html.= '<img class="fr b" src="' . $imagePath . '?i=' . muddle($idArticle) . 't" width="' . $thumbnailImageWidth . '" height="' . $thumbnailImageHeight . '" alt="">';
                }
                $summary = str_replace('*', '', $summary);
                $html.= '</a>' . html($summary) . "</p>\n";
            }
            $html.= "\n" . '  <form class="wait" action="' . $uri . '?m=article-contribution" method="post">' . "\n";
            $html.= '    <p> <input type="hidden" name="idArticle" value="' . $idArticle . '"><input type="submit" class="button" value="Delete" name="delete" /> <input type="submit" class="button" value="Edit" name="edit" /></p>' . "\n";
            $html.= "  </form>\n";
        }
    }
    $dbh = null;
}
$dbhSection = null;
echo $html;
?>
