<?php
/**
 * For subscribers to place classified ads
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Online News <useTheContactForm@onlinenewssite.com>
 * @copyright 2025 Online News
 * @license   https://onlinenewssite.com/license.html
 * @version   2025 05 12
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/onlinenewsllc/online-news-site
 */
if (empty($_SESSION['userId'])) {
    echo '<meta http-equiv="refresh" content="0; url=' . $uri . '?t=l">';
    exit;
}
require $includesPath . '/subscriber/authorization.php';
//
// Variables
//
$categoryIdEdit = null;
$categoryIdPost = inlinePost('categoryId');
$descriptionEdit = null;
$descriptionPost = securePost('description');
$idAdEdit = null;
$idAdPost = inlinePost('idAd');
$invoiceEdit = null;
$invoicePost = inlinePost('invoice');
$message = '';
$photosOrdered = [1, 2, 3, 4, 5, 6, 7];
$photosReverse = array_reverse($photosOrdered);
$photoAvailable = null;
$review = time() + 15 * 60;
$titleEdit = null;
$titlePost = inlinePost('title');
$button = '        <p><input type="submit" class="button" name="addUpdate" value="Add / update"> <input type="submit" class="button" name="reset" value="Reset"></p>' . "\n";
if (isset($idAdPost)) {
    $_POST['edit'] = 1;
}
//
// Get the user's email address
//
$dbh = new PDO($dbSubscribers);
$stmt = $dbh->prepare('SELECT email FROM users WHERE idUser=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$_SESSION['userId']]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    extract($row);
}
//
// Button: Add / Update
//
if (isset($_POST['addUpdate'])) {
    //
    // Determine insert or update
    //
    if (!isset($_POST['existing']) and empty($idAdPost)) {
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->query('DELETE FROM ads WHERE title IS NULL');
        $stmt = $dbh->prepare('INSERT INTO ads (title) VALUES (?)');
        $stmt->execute([null]);
        $idAdPost = $dbh->lastInsertId();
        $dbh = null;
    }
    //
    // Apply the update except for the image
    //
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('UPDATE ads SET email=?, title=?, description=?, categoryId=?, review=?, invoice=? WHERE idAd=?');
    $stmt->execute([$email, $titlePost, $descriptionPost, $categoryIdPost, $review, $invoicePost, $idAdPost]);
    $dbh = null;
    //
    // Store the image, if any
    //
    if ($_FILES['image']['size'] > 0 and $_FILES['image']['error'] === 0) {
        $sizes = getimagesize($_FILES['image']['tmp_name']);
        if ($sizes['mime'] === 'image/jpeg') {
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
                if (empty($row['0'])) {
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
                if ($widthOriginal > 2360) {
                    $widthHD = 2360;
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
        } else {
            $message = 'The uploaded file was not in the JPG format.';
        }
    }
    //
    // Alert admin when an invoice must be created
    //
    if (file_exists($includesPath . '/custom/programs/classifiedInvoice.php')) {
        include $includesPath . '/custom/programs/classifiedInvoice.php';
    }
}
//
// Button: Delete
//
if (isset($_POST['deletePending']) and isset($idAdPost)) {
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('DELETE FROM ads WHERE idAd=?');
    $stmt->execute([$idAdPost]);
    $dbh = null;
}
//
// Button: Delete photos
//
if (isset($_POST['photoDelete']) and isset($idAdPost)) {
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('UPDATE ads SET photo1=?, photo2=?, photo3=?, photo4=?, photo5=?, photo6=?, photo7=? WHERE idAd=?');
    $stmt->execute([null, null, null, null, null, null, null, $idAdPost]);
    $dbh = null;
}
//
// Button: Remove an ad before its expiration
//
if (isset($_POST['deleteApproved']) and isset($idAdPost)) {
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('DELETE FROM ads WHERE idAd=?');
    $stmt->execute([$idAdPost]);
    $dbh = null;
}
//
// Button: Edit
//
if (isset($_POST['edit']) and isset($idAdPost)) {
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('SELECT title, description, categoryId, invoice, photo1 FROM ads WHERE idAd=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idAdPost]);
    $row = $stmt->fetch();
    if ($row) {
        $categoryIdEdit = $row['categoryId'];
        $descriptionEdit = $row['description'];
        $idAdEdit = $idAdPost;
        $invoiceEdit = $row['invoice'];
        $titleEdit = $row['title'];
        if (!empty($row['photo1'])) {
            $button = '      <p><input type="submit" class="button" name="addUpdate" value="Add / update"> <input type="submit" class="button" name="photoDelete" value="Delete photos"> <input type="submit" class="button" name="reset" value="Reset"></p>'. "\n";
        }
    }
    $dbh = null;
}
//
// Button: Reset
//
if (isset($_POST['reset'])) {
    echo ' <script> location.replace("' . $uri . '?m=place-classified"); </script>';
    exit;
}
//
// HTML
//
echo '    <div class="main">' . "\n";
echoIfMessage($message);
//
// List pending ads first
//
$i = 0;
$dbh = new PDO($dbClassifieds);
$stmt = $dbh->prepare('SELECT idAd, title, description, startDate FROM ads WHERE email=? ORDER BY title');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$email]);
foreach ($stmt as $row) {
    extract($row);
    $i++;
    if ($i === 1) {
        echo "      <h1>Ads pending review</h1>\n\n";
    }
    echo '      <form action="' . $uri . '?m=place-classified" method="post">' . "\n";
    echo '        <p>' . $title . '<input type="hidden" name="idAd" value="' . $idAd . '"><input type="hidden" name="existing" value="1"><br>' . "\n";
    echo '        ' . $description . '<br>' . "\n";
    echo '        <input type="submit" class="button" name="edit" value="Edit"> <input type="submit" class="button" name="deletePending" value="Delete"></p>' . "\n";
    echo '      </form>' . "\n\n";
}
$dbh = null;
//
// List approved ads second
//
$ii = null;
$dbh = new PDO($dbClassifieds);
$stmt = $dbh->prepare('SELECT idAd, title, description, review, startDate, duration, endDate FROM ads WHERE email=? and duration IS NOT NULL ORDER BY title');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$email]);
foreach ($stmt as $row) {
    extract($row);
    $ii++;
    if ($ii === 1) {
        echo "      <h1>Approved ads</h1>\n\n";
    }
    echo '      <form action="' . $uri . '?m=place-classified" method="post">' . "\n";
    echo '        <p>' . $title . '<input type="hidden" name="idAd" value="' . $idAd . '"><input type="hidden" name="existing" value="1"><br>' . "\n";
    echo '        Expires: ' . $endDate . '<br>' . "\n";
    echo '        <input type="submit" class="button" name="deleteApproved" value="Remove ad before expiration"></p>' . "\n";
    echo '      </form>' . "\n\n";
}
$dbh = null;
if (!empty($i) or !empty($ii)) {
    echo "      <hr>\n\n";
}
//
// The add / update ad form
//
?>
      <h1>Add / update a classified ad</h1>

      <p>After fifteen minutes from the last edit, the ad is available for approval. To remove an approved ad, log in and return to <a href="<?php echo $uri; ?>?m=place-classified">Add / update a classified ad</a>.</p>

      <form action="<?php echo $uri; ?>?m=place-classified" method="post" enctype="multipart/form-data">
        <p><label for="title">Title</label><br>
        <input id="title" name="title" class="wide"<?php echoIfValue($titleEdit); ?>><input type="hidden" name="idAd"<?php echoIfValue($idAdEdit); ?>></p>

        <p><label for="description">Description</label><br>
        <textarea id="description" name="description" class="wide" rows="9"><?php echoIfText($descriptionEdit); ?></textarea><p>

        <p><label for="invoice"><input id="invoice" name="invoice" type="checkbox" value="1"<?php echoIfYes($invoiceEdit); ?>> Send an invoice to also have the add in the print version of the paper.</label></p>

        <p><label for="categoryId">Categories (select a subcategory)</label><br>
        <select id="categoryId" name="categoryId" size="1" required>
<?php
$dbh = new PDO($dbClassifieds);
$stmt = $dbh->query('SELECT idSection, section FROM sections ORDER BY sortOrderSection');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    extract($row);
    echo '          <option value="">' . html($section) . "</option>\n";
    $stmt = $dbh->prepare('SELECT idSubsection, subsection FROM subsections WHERE parentId=? ORDER BY sortOrderSubsection');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idSection]);
    foreach ($stmt as $row) {
        extract($row);
        if ($idSubsection === $categoryIdEdit) {
            $selected = ' selected';
        } else {
            $selected = null;
        }
        echo '          <option value="' . $idSubsection . '"' . $selected . '>&nbsp;&nbsp;&nbsp;' . html($subsection) . "</option>\n";
    }
}
$dbh = null;
?>
        </select></p>

        <p><label for="image">Photo upload (JPG image only<?php uploadFilesizeMaximum(); ?>)</label><br>
        <input id="image" name="image" type="file" class="wide" accept="image/jpeg"></p>

        <p>Up to seven images may be included in an ad. Upload one image at a time. Edit the listing to add each additional image. JPG is the only permitted image format. The best image size is 2360 pixels or wider. Larger images are reduced to that width.</p>

<?php
echo $button;
echo "      </form>\n";
if (isset($idAdEdit)) {
    foreach ($photosOrdered as $photo) {
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->prepare('SELECT photo' . $photo . ' FROM ads WHERE idAd=?');
        $stmt->setFetchMode(PDO::FETCH_NUM);
        $stmt->execute([$idAdEdit]);
        $row = $stmt->fetch();
        $dbh = null;
        if (!empty($row['0'])) {
            echo '      <p><img class="wide border" src="imagen.php?i=' . muddle($idAdEdit) . $photo . '" alt=""></p>' . "\n";
        }
    }
}
echo '    </div>' . "\n";
?>
