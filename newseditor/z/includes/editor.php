<?php
/**
 * The editing form, used by both edit and published
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
// Move expired articles from published to the archive
//
$expired = null;
$dbh = new PDO($dbPublished);
$stmt = $dbh->query('SELECT idArticle FROM articles WHERE endDate < "' . $today . '"');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    $expired[] = $row['idArticle'];
}
$dbh = null;
$archive = 'archive';
$dbFrom = $dbPublished;
$dbTo = $dbArchive;
if (is_array($expired)) {
    foreach ($expired as $idArticleMove) {
        include $includesPath . '/moveRow.php';
    }
}
//
// Download latest contributed articles
//
require $includesPath . '/syncArticles.php';
//
// Variables
//
$archive = null;
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
$photoCaptionEdit = null;
$photoCaptionPost = inlinePost('photoCaption');
$photoCreditEdit = null;
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
//
if ($bylinePost != null) {
    $dbh = new PDO($dbEditors);
    $stmt = $dbh->prepare('SELECT fullName, email FROM users WHERE fullName=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($bylinePost));
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
if ($use == 'edit') {
    $database = $dbEdit;
    $imagePath = 'imagee.php';
    $soa = null;
} elseif ($use == 'published') {
    $database = $dbPublished;
    $imagePath = 'imagep.php';
    $soa = 1;
}
if (!isset($_FILES['image'])) {
    $_FILES['image'] = null;
}
$byline = $endDate = $headline = $idSection = $message = $photoCaption = $photoCredit = $publicationDate = $publish = $sortOrderArticle = $standfirst = $text = null;
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
        $dbh = new PDO($database);
        $stmt = $dbh->prepare('INSERT INTO articles (headline) VALUES (?)');
        $stmt->execute(array(null));
        $idArticle = $dbh->lastInsertId();
        $dbh = null;
    } else {
        //
        // Adjust sort order when increasing the sort position of an exisiting article
        //
        $dbh = new PDO($database);
        $stmt = $dbh->prepare('SELECT sortOrderArticle FROM articles WHERE idArticle=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(array($idArticle));
        $row = $stmt->fetch();
        $dbh = null;
        if ($sortOrderArticlePost > $row['sortOrderArticle']) {
            $sortOrderArticlePost++;
        }
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
            $dbh = new PDO($database);
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
            $dbh = new PDO($database);
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
            $dbh = new PDO($database);
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
    $dbh = new PDO($database);
    $stmt = $dbh->prepare('UPDATE articles SET publicationDate=?, endDate=?, idSection=?, sortOrderArticle=?, byline=?, headline=?, standfirst=?, text=?, summary=?, photoCredit=?, photoCaption=? WHERE idArticle=?');
    $stmt->execute(array($publicationDatePost, $endDatePost, $idSectionPost, $sortOrderArticlePost, $bylinePost, $headlinePost, $standfirstPost, $textPost, $summaryPost, $photoCreditPost, $photoCaptionPost, $idArticle));
    $dbh = null;
    if ($soa == 1) {
        $idSection = $idSectionPost;
        //
        // Loop through each remote location
        //
        $dbhRemote = new PDO($dbRemote);
        $stmt = $dbhRemote->query('SELECT remote FROM remotes');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            extract($row);
            include $includesPath . '/addUpdateArticle.php';
        }
        $dbhRemote = null;
        include $includesPath . '/sortPublished.php';
        include $includesPath . '/syncArticles.php';
    }
}
//
// Button: Delete photo
//
if (isset($_POST['deletePhoto']) and isset($idArticle)) {
    $dbh = new PDO($database);
    $stmt = $dbh->prepare('UPDATE articles SET photoCredit=?, photoCaption=?, originalImage=?, originalImageWidth=?, originalImageHeight=?, thumbnailImage=?, thumbnailImageWidth=?, thumbnailImageHeight=?, hdImage=?, hdImageWidth=?, hdImageHeight=? WHERE idArticle=?');
    $stmt->execute(array(null, null, null, null, null, null, null, null, null, null, null, $idArticle));
    $dbh = null;
    $dbh = new PDO($dbPublished);
    $stmt = $dbh->prepare('UPDATE articles SET photoCredit=?, photoCaption=?, originalImage=?, originalImageWidth=?, originalImageHeight=?, thumbnailImage=?, thumbnailImageWidth=?, thumbnailImageHeight=?, hdImage=?, hdImageWidth=?, hdImageHeight=? WHERE idArticle=?');
    $stmt->execute(array(null, null, null, null, null, null, null, null, null, null, null, $idArticle));
    $dbh = null;
    if ($soa == 1) {
        $request = null;
        $request['task'] = 'publishedDeletePhoto';
        $request['idArticle'] = $idArticle;
        $dbhRemote = new PDO($dbRemote);
        $stmt = $dbhRemote->query('SELECT remote FROM remotes');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            extract($row);
            $response = soa($remote . 'z/', $request);
        }
        $dbhRemote = null;
        include $includesPath . '/syncArticles.php';
    }
}
//
// Button: Delete
//
if (isset($_POST['delete']) and isset($idArticle)) {
    $dbh = new PDO($database);
    $stmt = $dbh->prepare('SELECT idSection FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idArticle));
    $row = $stmt->fetch();
    extract($row);
    $stmt = $dbh->prepare('DELETE FROM articles WHERE idArticle=?');
    $stmt->execute(array($idArticle));
    $dbh = null;
    if ($soa == 1) {
        $request = null;
        $request['task'] = 'publishedDelete';
        $request['idArticle'] = $idArticle;
        $dbhRemote = new PDO($dbRemote);
        $stmt = $dbhRemote->query('SELECT remote FROM remotes');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            $response = soa($row['remote'] . 'z/', $request);
        }
        $dbhRemote = null;
        include $includesPath . '/sortPublished.php';
        include $includesPath . '/syncArticles.php';
    }
}
//
// Button: Edit
//
if (isset($_POST['edit'])) {
    $dbh = new PDO($database);
    $stmt = $dbh->prepare('SELECT publicationDate, endDate, idSection, sortOrderArticle, byline, headline, standfirst, text, photoCredit, photoCaption FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idArticle));
    $row = $stmt->fetch();
    $dbh = null;
    extract($row);
    $bylineEdit = $byline;
    $endDateEdit = $endDate;
    $headlineEdit = $headline;
    $idArticleEdit = $idArticle;
    $idSectionEdit = $idSection;
    $photoCaptionEdit = $photoCaption;
    $photoCreditEdit = $photoCredit;
    $publicationDateEdit = $publicationDate;
    $sortOrderArticleEdit = $sortOrderArticle;
    $standfirstEdit = $standfirst;
    $textEdit = $text;
}
//
// Buttons: Up or down arrows
//
if (isset($_POST['up']) or isset($_POST['down'])) {
    include $includesPath . '/sortPublished.php';
    include $includesPath . '/syncArticles.php';
}
//
// Buttons: Publish and Archive
//
if (isset($_POST['publish'])
    and $_POST['publish'] == 'Publish'
    and (!isset($_POST['addUpdate'])
    and !isset($_POST['delete'])
    and !isset($_POST['deletePhoto'])
    and !isset($_POST['down'])
    and !isset($_POST['edit'])
    and !isset($_POST['up']))
) {
    //
    // Set the database for publish or archive
    //
    $dbh = new PDO($database);
    $stmt = $dbh->prepare('SELECT idSection, publicationDate, endDate FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idArticle));
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        extract($row);
        if (isset($_POST['archive']) and $_POST['archive'] == 'Archive') {
            $archive = 'archive';
            $dbFrom = $dbPublished;
            $dbTo = $dbArchive;
        } else {
            if (isset($publicationDate) and isset($endDate)) {
                $archive = null;
                $dbFrom = $dbEdit;
                $dbTo = $dbPublished;
            } else {
                $message = 'Publication dates are required fields.';
            }
        }
        if (isset($dbFrom) and isset($dbTo)) {
            $idArticleMove = $idArticle;
            include $includesPath . '/moveRow.php';
            include $includesPath . '/sortPublished.php';
            include $includesPath . '/syncArticles.php';
            if ($archive = 'archive') {
                $archiveSync = 1;
                include $includesPath . '/sortPublished.php';
                include $includesPath . '/syncArticles.php';
            }
        }
    }
}
//
// HTML
//
require $includesPath . '/header1.inc';
echo '  <title>' . $title . "</title>\n";
?>
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

<?php
require $includesPath . '/body.inc';
echo $menu;
echo '  <h1 id="waiting">Please wait.</h1>' . "\n\n";
echo '  <h1>' . $title . "</h1>\n";
echoIfMessage($message);
?>

  <form class="wait" method="post" action="<?php echo $uri . $use; ?>.php" enctype="multipart/form-data">
    <p><span class="rp"><label for="byline">Byline</label><br />
    <input id="byline" name="byline" type="text" class="w" list="bylineList"<?php echoIfValue($bylineEdit); ?> required autofocus /><input type="hidden" name="idArticle" value="<?php echo $idArticleEdit; ?>"></span></p>
    <datalist id="bylineList">
<?php
$dbh = new PDO($dbEditors);
$stmt = $dbh->query('SELECT user, fullName FROM users ORDER BY fullName');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    extract($row);
    if ($user != 'admin') {
        echo '      <option label="' . $fullName . '" value="' . $fullName . '">' . "\n";
    }
}
$dbh = null;
?>
    </datalist>

    <p><label for="publicationDate">Publication dates</label><br />
    <input id="publicationDate" name="publicationDate" type="text" class="datepicker h" placeholder="Start date"<?php echoIfValue($publicationDateEdit); ?> /> <input name="endDate" type="text" class="datepicker h" placeholder="End date"<?php echoIfValue($endDateEdit); ?> /></p>

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

    <p><label for="text">Text</label><br />
    <textarea id="text" name="text" rows="9" class="w"><?php echoIfText($textEdit); ?></textarea></p>

    <p><label for="photoCredit">Photo credit</label><br />
    <input id="photoCredit" name="photoCredit" type="text" class="w"<?php echoIfValue($photoCreditEdit); ?> /></p>

    <p><label for="photoCaption">Photo caption</label><br />
    <input id="photoCaption" name="photoCaption" type="text" class="w" autocomplete="on"<?php echoIfValue($photoCaptionEdit); ?> /></p>

    <p><label for="image">Photo upload (JPG image only)</label><br />
    <input id="image" name="image" type="file" class="w" accept="image/jpeg" /></p>

    <p><input type="submit" value="Add / update" name="addUpdate" class="left" /><input type="submit" value="Delete photo" name="deletePhoto" class="right" /><input type="hidden" name="existing"<?php echoIfValue($edit); ?> /></p>
  </form>

<?php
require $includesPath . '/displayIndex.inc';
?>
</body>
</html>
