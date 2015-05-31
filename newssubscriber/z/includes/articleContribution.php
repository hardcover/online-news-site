<?php
/**
 * For authorized article contributions
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
//
// Authorization
//
if (empty($_SESSION['userId'])) {
    include 'logout.php';
    exit;
}
$dbh = new PDO($dbSubscribers);
$stmt = $dbh->prepare('SELECT contributor FROM users WHERE idUser=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array($_SESSION['userId']));
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    if ($row['contributor'] != 1) {
        include 'logout.php';
        exit;
    }
}
//
// Variables
//
$bylineEdit = null;
$bylinePost = inlinePost('byline');
$edit = inlinePost('edit');
$headlineEdit = null;
$headlinePost = securePost('headline');
$idArticle = inlinePost('idArticle');
$idArticleEdit = null;
$idSectionEdit = null;
$idSectionPost = inlinePost('idSection');
$imagePath = 'imagee.php';
$photoCaptionEdit = null;
$photoCaptionPost = inlinePost('photoCaption');
$photoCreditEdit = null;
$photoCreditPost = inlinePost('photoCredit');
$standfirstEdit = null;
$standfirstPost = securePost('standfirst');
$textEdit = null;
$textPost = securePost('text');
//
$byline = $headline = $idSection = $message = $photoCaption = $photoCredit = $standfirst = $text = null;
$summaryPost = substr(preg_replace("'\s+'", ' ', $textPost), 0, 500);
$summaryPost = str_replace(strrchr($summaryPost, ' '), ' ', $summaryPost);
//
// Button: Add / Update
//
if (isset($_POST['addUpdate']) and isset($bylinePost)) {
    //
    // Determine insert or update
    //
    if ($_POST['existing'] == null) {
        $dbh = new PDO($dbEdit);
        $stmt = $dbh->prepare('INSERT INTO articles (headline) VALUES (?)');
        $stmt->execute(array(null));
        $idArticle = $dbh->lastInsertId();
        $dbh = null;
    }
    //
    // Store the image, if any
    //
    if ($_FILES['image']['size'] > 0 and $_FILES['image']['error'] == 0) {
        $sizes = getimagesize($_FILES['image']['tmp_name']);
        if ($sizes['mime'] == 'image/jpeg') {
            //
            // Save the original image and calculate the aspect ratio
            //
            $widthOriginal = $sizes['0'];
            $heightOriginal = $sizes['1'];
            $aspectRatio = $widthOriginal / $heightOriginal;
            $dbh = new PDO($dbEdit);
            $stmt = $dbh->prepare('UPDATE articles SET originalImage=?, originalImageWidth=?, originalImageHeight=? WHERE idArticle=?');
            $stmt->execute(array(file_get_contents($_FILES['image']['tmp_name']), $widthOriginal, $heightOriginal, $idArticle));
            $dbh = null;
            //
            // Create and save the thumbnail image
            //
            $heightThumbnail = 71 * 2; // Double pixels for retina displays
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
            // Create and save the HD image
            //
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
            $dbh = new PDO($dbEdit);
            $stmt = $dbh->prepare('UPDATE articles SET hdImage=?, hdImageWidth=?, hdImageHeight=? WHERE idArticle=?');
            $stmt->execute(array($hdImage, $widthHD, $heightHD, $idArticle));
            $dbh = null;
        } else {
            $message = 'The uploaded file was not in the JPG format.';
        }
    }
    //
    // Send the non-image information to the database
    //
    $dbh = new PDO($dbEdit);
    $stmt = $dbh->prepare('UPDATE articles SET userId=?, idSection=?, sortOrderArticle=?, byline=?, headline=?, standfirst=?, text=?, summary=?, photoCredit=?, photoCaption=? WHERE idArticle=?');
    $stmt->execute(array($_SESSION['userId'], $idSectionPost, time() + 900, $bylinePost, $headlinePost, $standfirstPost, $textPost, $summaryPost, $photoCreditPost, $photoCaptionPost, $idArticle));
    $dbh = null;
}
//
// Button: Delete photo
//
if (isset($_POST['deletePhoto']) and isset($idArticle)) {
    $dbh = new PDO($dbEdit);
    $stmt = $dbh->prepare('UPDATE articles SET photoCredit=?, photoCaption=?, originalImage=?, originalImageWidth=?, originalImageHeight=?, thumbnailImage=?, thumbnailImageWidth=?, thumbnailImageHeight=?, hdImage=?, hdImageWidth=?, hdImageHeight=? WHERE idArticle=?');
    $stmt->execute(array(null, null, null, null, null, null, null, null, null, null, null, $idArticle));
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
}
//
// Button: Edit
//
if (isset($_POST['edit'])) {
    $dbh = new PDO($dbEdit);
    $stmt = $dbh->prepare('SELECT idSection, byline, headline, standfirst, text, photoCredit, photoCaption FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idArticle));
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        extract($row);
        $bylineEdit = $byline;
        $headlineEdit = $headline;
        $idArticleEdit = $idArticle;
        $idSectionEdit = $idSection;
        $photoCaptionEdit = $photoCaption;
        $photoCreditEdit = $photoCredit;
        $standfirstEdit = $standfirst;
        $textEdit = $text;
    }
}
//
// HTML
//
echoIfMessage($message);
?>
    <h1>Article contribution</h1>

    <p>Edits should be complete within fifteen minutes of starting the article. After fifteen minutes the article is available for approval, after which it can no longer be edited.</p>

    <form class="wait" method="post" action="<?php echo $uri; ?>?m=article-contribution" enctype="multipart/form-data">
      <p><label for="byline">Byline</label><br />
      <input id="byline" name="byline" type="text" class="w" required autofocus<?php echoIfValue($bylineEdit); ?> /><input type="hidden" name="idArticle" value="<?php echo $idArticleEdit; ?>"></p>

      <p><label for="idSection">Section</label><br />
      <select id="idSection" name="idSection">
<?php
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT idSection, section FROM sections ORDER BY sortOrderSection');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    $selected = $idSectionEdit == $row['idSection'] ? ' selected="selected"' : null;
    echo '        <option value="' . $row['idSection'] . '"' . $selected . '>' . $row['section'] . "</option>\n";
}
$dbh = null;
?>
      </select></p>

      <p><label for="headline">Headline</label><br />
      <input id="headline" name="headline" type="text" class="w" <?php echoIfValue($headlineEdit); ?> /></p>

      <p><label for="standfirst">Standfirst</label><br />
      <input id="standfirst" name="standfirst" type="text" class="w"<?php echoIfValue($standfirstEdit); ?> /></p>

      <p><label for="text">Text</label><br />
      <textarea id="text" name="text" rows="9" class="w"><?php echoIfText($textEdit); ?></textarea></p>

      <p><label for="photoCredit">Photo credit</label><br />
      <input id="photoCredit" name="photoCredit" type="text" class="w"<?php echoIfValue($photoCreditEdit); ?> /></p>

      <p><label for="photoCaption">Photo caption</label><br />
      <input id="photoCaption" name="photoCaption" type="text" class="w"<?php echoIfValue($photoCaptionEdit); ?> /></p>

      <p><label for="image">Photo upload (JPG image only)</label><br />
      <input id="image" name="image" type="file" accept="image/jpeg" class="w" /></p>

      <p><input type="submit" value="Add / update" name="addUpdate" class="left" /><input type="submit" value="Delete photo" name="deletePhoto" class="right" /><input type="hidden" name="existing"<?php echoIfValue($edit); ?> /></p>
    </form>

    <hr />

<?php
$html = null;
$dbhSection = new PDO($dbSettings);
$stmt = $dbhSection->query('SELECT idSection, section, sortOrderSection FROM sections ORDER BY sortOrderSection');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    extract($row);
    $dbh = new PDO($dbEdit);
    $stmt = $dbh->prepare('SELECT idSection FROM articles WHERE idSection=? and userId=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idSection, $_SESSION['userId']));
    $row = $stmt->fetch();
    if ($row) {
        $count = null;
        $html.= "\n" . '    <h4>' . $section . "</h4>\n\n";
        $stmt = $dbh->query('SELECT idArticle, publicationDate, headline, summary, thumbnailImageWidth, thumbnailImageHeight FROM articles WHERE idSection = ? and userId=? ORDER BY sortOrderArticle');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(array($idSection, $_SESSION['userId']));
        foreach ($stmt as $row) {
            extract($row);
            if ($count != null) {
                $html.= "    <hr />\n\n";
            }
            $count++;
            $html.= '    <h2>' . html($headline) . "</h2>\n\n";
            if ($thumbnailImageWidth != null) {
                $html.= '    <p class="s">';
                $html.= '<img class="fr b" src="' . $imagePath . '?i=' . muddle($idArticle) . 't" width="' . $thumbnailImageWidth . '" height="' . $thumbnailImageHeight . '" alt="">';
                $html.= html($summary) . " (continued)</p>\n";
            } else {
                $html.= '    <p class="s">' . html($summary) . " (continued)</p>\n";
            }
            $html.= "\n" . '    <form class="wait" action="' . $uri . '?m=article-contribution" method="post">' . "\n";
            $html.= '      <p> <input type="hidden" name="idArticle" value="' . $idArticle . '"><input type="submit" value="Delete" name="delete" class="left" /><input type="submit" value="Edit" name="edit" class="right" /></p>' . "\n";
            $html.= "    </form>\n";
        }
    }
    $dbh = null;
}
$dbhSection = null;
echo $html;
?>
