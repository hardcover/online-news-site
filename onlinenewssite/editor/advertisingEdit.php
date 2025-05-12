<?php
/**
 * Maintenance of ads not currently published
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
if (empty($row['userType']) or strval($row['userType']) !== '3') {
    include 'logout.php';
    exit;
}
//
// Variables
//
$edit = inlinePost('edit');
$endDateAdEdit = null;
$endDateAdPost = inlinePost('endDateAd');
$idAd = null;
$idAdEdit = null;
$idAdPost = inlinePost('idAd');
$linkAltEdit = null;
$linkAltPost = str_replace("\"", '', str_replace("'", '', inlinePost('linkAlt')));
$linkEdit = null;
$linkPost = inlinePost('link');
$message = '';
$notPaidEdit = null;
$noteEdit = null;
$notePost = inlinePost('note');
$organizationEdit = null;
$organizationPost = inlinePost('organization');
$paidEdit = null;
$payStatusPost = inlinePost('payStatus');
$sortOrderAdEdit = null;
$sortOrderAdPost = inlinePost('sortOrderAd');
$sortPriorityEdit = null;
$startDateAdEdit = null;
$startDateAdPost = inlinePost('startDateAd');
$_FILES['image'] = isset($_FILES['image']) ? $_FILES['image'] : null;
//
// Button: Add / update
//
if (isset($_POST['addUpdate'])) {
    //
    // Determine insert or update, check for unique id
    //
    if (empty($_POST['existing'])) {
        $dbh = new PDO($dbAdvertising);
        $stmt = $dbh->query('DELETE FROM advertisements WHERE organization IS NULL');
        $stmt = $dbh->prepare('INSERT INTO advertisements (organization) VALUES (?)');
        $stmt->execute([null]);
        $idAd = $dbh->lastInsertId();
        $dbh = null;
    } else {
        $dbh = new PDO($dbAdvertising);
        $stmt = $dbh->prepare('SELECT idAd FROM advertisements WHERE idAd=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idAdPost]);
        $row = $stmt->fetch();
        $dbh = null;
        extract($row);
    }
    //
    // Apply update
    //
    if ($idAd !== null) {
        $dbh = new PDO($dbAdvertising);
        $stmt = $dbh->prepare('SELECT idAd, sortOrderAd FROM advertisements WHERE idAd=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idAd]);
        $row = $stmt->fetch();
        if ($sortOrderAdPost > $row['sortOrderAd']) {
            $sortOrderAdPost++;
        }
        if (empty($linkAltPost)) {
            $linkAltPost = $organizationPost;
        }
        $stmt = $dbh->prepare('UPDATE advertisements SET startDateAd=?, endDateAd=?, sortOrderAd=?, sortPriority=?, organization=?, payStatus=?, link=?, linkAlt=?, enteredBy=?, note=? WHERE idAd=?');
        $stmt->execute([$startDateAdPost, $endDateAdPost, $sortOrderAdPost, 1, $organizationPost, $payStatusPost, $linkPost, $linkAltPost, $_SESSION['username'], $notePost, $idAd]);
        $dbh = null;
        //
        // Resize and store the image
        //
        if ($_FILES['image']['size'] > 0 and strval($_FILES['image']['error']) === '0') {
            $sizes = getimagesize($_FILES['image']['tmp_name']);
            if ($sizes['mime'] === 'image/jpeg') {
                $widthOriginal = $sizes['0'];
                $heightOriginal = $sizes['1'];
                $aspectRatio = $widthOriginal / $heightOriginal;
                $width = 900;
                $height = round($width / $aspectRatio);
                $hd = imagecreatetruecolor($width, $height);
                imageinterlace($hd, true);
                $srcImage = imagecreatefromjpeg($_FILES['image']['tmp_name']);
                imagecopyresampled($hd, $srcImage, 0, 0, 0, 0, $width, $height, ImageSX($srcImage), ImageSY($srcImage));
                ob_start();
                imagejpeg($hd, null, 100);
                imagedestroy($hd);
                $image = ob_get_contents();
                ob_end_clean();
                $dbh = new PDO($dbAdvertising);
                $stmt = $dbh->prepare('UPDATE advertisements SET image=?, imageWidth=?, imageHeight=? WHERE idAd=?');
                $stmt->execute([$image, $width, $height, $idAd]);
                $dbh = null;
            } else {
                $message = 'The uploaded file was not in the JPG format.';
            }
        }
        include $includesPath . '/editor/sortAdvertisements.php';
    }
}
//
// Button: Delete
//
if (isset($_POST['delete']) and isset($idAdPost)) {
    $dbh = new PDO($dbAdvertising);
    $stmt = $dbh->prepare('SELECT idAd FROM advertisements WHERE idAd=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idAdPost]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        extract($row);
        $dbh = new PDO($dbAdvertising);
        $stmt = $dbh->prepare('DELETE FROM advertisements WHERE idAd=?');
        $stmt->execute([$idAd]);
        $dbh = null;
        include $includesPath . '/editor/sortAdvertisements.php';
    } else {
        $message = 'No ad was input.';
    }
}
//
// Button, edit
//
if (isset($_POST['edit'])) {
    $dbh = new PDO($dbAdvertising);
    $stmt = $dbh->prepare('SELECT startDateAd, endDateAd, sortOrderAd, sortPriority, organization, payStatus, link, linkAlt, note FROM advertisements WHERE idAd=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idAdPost]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        extract($row);
        $endDateAdEdit = $endDateAd;
        $idAdEdit = $idAdPost;
        $linkAltEdit = $linkAlt;
        $linkEdit = $link;
        $notPaidEdit = (isset($payStatus) and $payStatus === '0') ? 1 : null;
        $noteEdit = $note;
        $organizationEdit = $organization;
        $paidEdit = $payStatus;
        $sortOrderAdEdit = $sortOrderAd;
        $sortPriorityEdit = $sortPriority;
        $startDateAdEdit = $startDateAd;
    }
}
//
// HTML
//
require $includesPath . '/editor/header1.inc';
?>
  <title>Advertising maintenance</title>
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

<?php require $includesPath . '/editor/body.inc';?>

  <nav class="n">
    <h4 class="m"><a class="m" href="advertisingPublished.php">Published ads</a><a class="s" href="advertisingEdit.php">Edit ads</a><a class="m" href="advertisingMax.php">Settings</a></h4>
  </nav>
<?php echoIfMessage($message); ?>

  <div class="flex">
    <main>
      <form action="<?php echo $uri; ?>advertisingEdit.php" method="post" enctype="multipart/form-data">
        <h1>Add, update and delete ads not currently published</h1>

        <p>Organization is required for add, update and delete. Publication dates determine what ads are currently published. Unless a sort order is specified, ad order is random and will change each time the page is loaded.</p>

        <p><label for="startDateAd">Publication dates, start date to end date</label><br>
        <input class="datepicker date" id="startDateAd" name="startDateAd" <?php echoIfValue($startDateAdEdit); ?>><br><br>
        <input class="datepicker date" name="endDateAd" <?php echoIfValue($endDateAdEdit); ?>></p>

        <p><label for="sortOrderAd">Sort order (optional)</label><br>
        <input id="sortOrderAd" name="sortOrderAd" class="h" <?php echoIfValue($sortOrderAdEdit); ?>></p>

        <p><label for="organization">Organization</label><br>
        <input id="organization" name="organization" class="h" required<?php echoIfValue($organizationEdit); ?>><input name="idAd" type="hidden"<?php echoIfValue($idAdEdit); ?>></p>

        <p>Pay status<br>
        <label>
          <input name="payStatus" type="radio" value="1"<?php echoIfYes($paidEdit); ?>> Paid<br>
        </label>
        <label>
          <input name="payStatus" type="radio" value="0"<?php echoIfYes($notPaidEdit); ?>> Not paid
        </label></p>

        <p><label for="image">Ad image upload (JPG image only)</label><br>
        <input type="file" class="h" accept="image/jpeg" id="image" name="image"><br></p>

        <p><label for="link">Link from ad (optional)</label><br>
        <input id="link" name="link" class="h" <?php echoIfValue($linkEdit); ?>></p>

        <p><label for="linkAlt">Alternate text for ad image  (if different from the organization name above)</label><br>
        <input id="linkAlt" name="linkAlt" class="h" <?php echoIfValue($linkAltEdit); ?>></p>

        <p><label for="note">Note</label><br>
        <textarea id="note" name="note" class="h"><?php echoIfText($noteEdit); ?></textarea></p>

        <p><input type="submit" value="Add / update" name="addUpdate" class="button"> <input type="submit" value="Delete" name="delete" class="button"><input type="hidden" name="existing"<?php echoIfValue($edit); ?>></p>
      </form>
    </main>

    <aside>
      <h2>Ads not currently published</h2>

<?php
$rowcount = null;
$dbh = new PDO($dbAdvertising);
$stmt = $dbh->prepare('SELECT idAd, organization, link, linkAlt, enteredBy, note, imageWidth, imageHeight FROM advertisements WHERE ((? >= startDateAd AND ? >= endDateAd) OR (? >= startDateAd AND ? >= endDateAd) OR startDateAd IS NULL OR endDateAd IS NULL) ORDER BY organization');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$today, $today]);
foreach ($stmt as $row) {
    extract($row);
    if (empty($imageWidth) or $imageWidth === '0') {
        $imageWidth = 1;
    }
    $width = (200 / $imageWidth) * $imageWidth;
    $height = round((200 / $imageWidth) * $imageHeight);
    echo '      <form action="' . $uri . 'advertisingEdit.php" method="post">' . "\n";
    echo '        <p><img class="b" src="imaged.php?i=' . muddle($idAd) . '" alt="" width="' . $width . '" height="' . $height . '"><br>' . "\n";
    echo '        ' . $organization . ', by ' . $enteredBy . "<br>\n";
    if ($link !== null and $link !== '') {
        echo '        <a href="' . html($link) . '" target="_blank">' . $linkAlt . "</a><br>\n";
    }
    if ($note !== null and $note !== '') {
        echo '        ' . $note . "<br>\n";
    }
    echo '        <input name="idAd" type="hidden" value="' . $idAd . '"><input type="submit" value="Edit" name="edit" class="button"></p>' . "\n";
    echo '      </form>' . "\n\n";
}
$dbh = null;

?>
    </aside>
  </div>
</body>
</html>
