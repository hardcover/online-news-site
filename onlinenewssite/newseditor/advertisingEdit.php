<?php
/**
 * Maintenance of ads not currently published
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
if (empty($row['userType']) or $row['userType'] != 3) {
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
$message = null;
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
$remotes = array();
$dbh = new PDO($dbRemote);
$stmt = $dbh->query('SELECT remote FROM remotes');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    $remotes[] = $row['remote'];
}
$dbh = null;
//
// Button: Add / update
//
if (isset($_POST['addUpdate'])) {
    //
    // Determine insert or update, check for unique id
    //
    if ($_POST['existing'] == null) {
        $dbh = new PDO($dbAdvertising);
        $stmt = $dbh->query('DELETE FROM advertisements WHERE organization IS NULL');
        $stmt = $dbh->prepare('INSERT INTO advertisements (organization) VALUES (?)');
        $stmt->execute(array(null));
        $idAd = $dbh->lastInsertId();
        $dbh = null;
    } else {
        $dbh = new PDO($dbAdvertising);
        $stmt = $dbh->prepare('SELECT idAd FROM advertisements WHERE idAd=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(array($idAdPost));
        $row = $stmt->fetch();
        $dbh = null;
        extract($row);
    }
    //
    // Apply update
    //
    if ($idAd != null) {
        $dbh = new PDO($dbAdvertising);
        $stmt = $dbh->prepare('SELECT idAd, sortOrderAd FROM advertisements WHERE idAd=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(array($idAd));
        $row = $stmt->fetch();
        if ($sortOrderAdPost > $row['sortOrderAd']) {
            $sortOrderAdPost++;
        }
        $stmt = $dbh->prepare('UPDATE advertisements SET startDateAd=?, endDateAd=?, sortOrderAd=?, sortPriority=?, organization=?, payStatus=?, link=?, linkAlt=?, enteredBy=?, note=? WHERE idAd=?');
        $stmt->execute(array($startDateAdPost, $endDateAdPost, $sortOrderAdPost, 1, $organizationPost, $payStatusPost, $linkPost, $linkAltPost, $_SESSION['username'], $notePost, $idAd));
        $dbh = null;
        //
        // Create and save the image
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
                $dbh = new PDO($dbAdvertising);
                $stmt = $dbh->prepare('UPDATE advertisements SET originalImage=?, originalImageWidth=?, originalImageHeight=? WHERE idAd=?');
                $stmt->execute(array(file_get_contents($_FILES['image']['tmp_name']), $widthOriginal, $heightOriginal, $idAd));
                $dbh = null;
                if ($widthOriginal == 640) {
                    //
                    // If the original width is 640, then use the original image without resizing
                    //
                    $dbh = new PDO($dbAdvertising);
                    $stmt = $dbh->prepare('UPDATE advertisements SET image=?, imageWidth=?, imageHeight=? WHERE idAd=?');
                    $stmt->execute(array(file_get_contents($_FILES['image']['tmp_name']), $widthOriginal, $heightOriginal, $idAd));
                    $dbh = null;
                } else {
                    //
                    // Else resize the image for use
                    //
                    $width = 640;
                    $height = round($width / $aspectRatio);
                    $hd = imagecreatetruecolor($width, $height);
                    $srcImage = imagecreatefromjpeg($_FILES['image']['tmp_name']);
                    imagecopyresized($hd, $srcImage, 0, 0, 0, 0, $width, $height, ImageSX($srcImage), ImageSY($srcImage));
                    ob_start();
                    imagejpeg($hd, null, 100);
                    imagedestroy($hd);
                    $image = ob_get_contents();
                    ob_end_clean();
                    $dbh = new PDO($dbAdvertising);
                    $stmt = $dbh->prepare('UPDATE advertisements SET image=?, imageWidth=?, imageHeight=? WHERE idAd=?');
                    $stmt->execute(array($image, $width, $height, $idAd));
                    $dbh = null;
                }
            } else {
                $message = 'The uploaded file was not in the JPG format.';
            }
        }
        //
        // Update remote sites
        //
        $dbh = new PDO($dbAdvertising);
        $stmt = $dbh->prepare('SELECT startDateAd, endDateAd, sortOrderAd, link, linkAlt, image FROM advertisements WHERE idAd=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(array($idAd));
        $row = $stmt->fetch();
        $dbh = null;
        if ($row) {
            extract($row);
            $request = null;
            $request['task'] = 'adInsert';
            $request['idAd'] = $idAd;
            $request['startDateAd'] = $startDateAd;
            $request['endDateAd'] = $endDateAd;
            $request['sortOrderAd'] = $sortOrderAd;
            $request['link'] = $link;
            $request['linkAlt'] = $linkAlt;
            $request['image'] = $image;
            foreach ($remotes as $remote) {
                $response = soa($remote . 'z/', $request);
            }
        }
        include $includesPath . '/sortAdvertisements.php';
        include $includesPath . '/syncAdvertisements.php';
    }
}
//
// Button: Delete
//
if (isset($_POST['delete']) and isset($idAdPost)) {
    $dbh = new PDO($dbAdvertising);
    $stmt = $dbh->prepare('SELECT idAd FROM advertisements WHERE idAd=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idAdPost));
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        extract($row);
        $dbh = new PDO($dbAdvertising);
        $stmt = $dbh->prepare('DELETE FROM advertisements WHERE idAd=?');
        $stmt->execute(array($idAd));
        $dbh = null;
        //
        // Update remote sites
        //
        $response = null;
        $request['task'] = 'adDelete';
        $request['idAd'] = $idAd;
        foreach ($remotes as $remote) {
            $response = soa($remote . 'z/', $request);
        }
        include $includesPath . '/sortAdvertisements.php';
        include $includesPath . '/syncAdvertisements.php';
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
    $stmt->execute(array($idAdPost));
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        extract($row);
        $endDateAdEdit = $endDateAd;
        $idAdEdit = $idAdPost;
        $linkAltEdit = $linkAlt;
        $linkEdit = $link;
        $notPaidEdit = (isset($payStatus) and $payStatus == 0) ? 1 : null;
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
require $includesPath . '/header1.inc';
?>
  <title>Advertising maintenance</title>
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

<?php require $includesPath . '/body.inc';?>

  <h4 class="m"><a class="m" href="advertisingPublished.php">&nbsp;Published ads&nbsp;</a><a class="s" href="advertisingEdit.php">&nbsp;Edit ads&nbsp;</a><a class="m" href="advertisingMax.php">&nbsp;Ads max&nbsp;</a></h4>
<?php echoIfMessage($message); ?>

  <h1 id="waiting">Please wait.</h1>

  <h1><span class="h">Ads not currently published</span></h1>

<?php
$rowcount = null;
$dbh = new PDO($dbAdvertising);
$stmt = $dbh->prepare('SELECT idAd, organization, link, linkAlt, enteredBy, note, imageWidth, imageHeight FROM advertisements WHERE ((? >= startDateAd AND ? >= endDateAd) OR (? >= startDateAd AND ? >= endDateAd) OR startDateAd IS NULL OR endDateAd IS NULL) ORDER BY organization');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array($today, $today));
foreach ($stmt as $row) {
    extract($row);
    if ($imageWidth == 0) {
        $imageWidth = 1;
    }
    $width = (200 / $imageWidth) * $imageWidth;
    $height = round((200 / $imageWidth) * $imageHeight);
    echo '  <form class="wait" action="' . $uri . 'advertisingEdit.php" method="post">' . "\n";
    echo '    <p><span class="p"><img class="b" src="imaged.php?i=' . muddle($idAd) . '" alt="" width="' . $width . '" height="' . $height . '" /><br />' . "\n";
    echo '    ' . $organization . ', by ' . $enteredBy . "<br />\n";
    if ($link != null and $link != '') {
        echo '    <a href="' . html($link) . '" target="_blank">' . $linkAlt . "</a><br />\n";
    }
    if ($note != null and $note != '') {
        echo '    ' . $note . "<br />\n";
    }
    echo '    <input name="idAd" type="hidden" value="' . $idAd . '" /><input type="submit" value="Edit" name="edit" class="button" /></span></p>' . "\n";
    echo '  </form>' . "\n\n";
}
$dbh = null;

?>
  <form class="wait" action="<?php echo $uri; ?>advertisingEdit.php" method="post" enctype="multipart/form-data">
    <h1>Add, update and delete ads not currently published</h1>

    <p>Organization is required for add, update and delete. Publication dates determine what ads are currently published. Unless a sort order is specified, ad order is random and will change each time the page is loaded.</p>

    <p><label for="startDateAd">Publication dates</label><br />
    <input type="text" class="datepicker h" id="startDateAd" name="startDateAd" placeholder="Start date" <?php echoIfValue($startDateAdEdit); ?> /><br /><br />
    <input type="text" class="datepicker h" name="endDateAd" placeholder="End date" <?php echoIfValue($endDateAdEdit); ?> /></p>

    <p><label for="sortOrderAd">Sort order (optional)</label><br />
    <input id="sortOrderAd" name="sortOrderAd" type="text" class="h" <?php echoIfValue($sortOrderAdEdit); ?> /></p>

    <p><label for="organization">Organization</label><br />
    <input id="organization" name="organization" type="text" class="h" required<?php echoIfValue($organizationEdit); ?> /><input name="idAd" type="hidden"<?php echoIfValue($idAdEdit); ?> /></p>

    <p>Pay status<br />
    <label>
      <input name="payStatus" type="radio" value="1"<?php echoIfYes($paidEdit); ?> /> Paid<br>
    </label>
    <label>
      <input name="payStatus" type="radio" value="0"<?php echoIfYes($notPaidEdit); ?> /> Not paid
    </label></p>

    <p><label for="image">Ad image upload (JPG image only)</label><br />
    <input type="file" class="h" accept="image/jpeg" id="image" name="image" /><br /></p>

    <p><label for="link">Link from ad (optional)</label><br />
    <input id="link" name="link" type="text" class="h" <?php echoIfValue($linkEdit); ?> /></p>

    <p><label for="linkAlt">Alternate text for ad image (optional)</label><br />
    <input id="linkAlt" name="linkAlt" type="text" class="h" <?php echoIfValue($linkAltEdit); ?> /></p>

    <p><label for="note">Note</label><br />
    <span class="hl"><textarea id="note" name="note" class="h"><?php echoIfText($noteEdit); ?></textarea></span></p>

    <p><input type="submit" value="Add / update" name="addUpdate" class="button" /></p>

    <p><input type="submit" value="Delete" name="delete" class="button" /><input type="hidden" name="existing"<?php echoIfValue($edit); ?> /></p>
  </form>
</body>
</html>
