<?php
/**
 * User maintenance for subscribing users
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
if (empty($row['userType']) or $row['userType'] != 2) {
    include 'logout.php';
    exit;
}
//
// Variables
//
$billingAddressEdit = null;
$billingAddressPost = inlinePost('billingAddress');
$bCityRegionPostalEdit = null;
$bCityRegionPostalPost = inlinePost('bCityRegionPostal');
$deliveryAddressEdit = null;
$deliveryAddressPost = inlinePost('deliveryAddress');
$dCityRegionPostalEdit = null;
$dCityRegionPostalPost = inlinePost('dCityRegionPostal');
$classifiedOnlyPost = inlinePost('classifiedOnly');
$classifiedOnlyEdit = null;
$contributorPost = inlinePost('contributor');
$contributorEdit = null;
$deliverPost = inlinePost('deliver');
$deliverEdit = null;
$edit = inlinePost('edit');
$emailEdit = null;
$emailPost = inlinePost('email');
$idUserEdit = null;
$idUserPost = inlinePost('idUser');
$message = null;
$notPaidEdit = null;
$noteEdit = null;
$notePost = inlinePost('note');
$paidEdit = null;
$passPost = inlinePost('pass');
$payStatusPost = inlinePost('payStatus');
$subscribers = null;
if ($passPost != null) {
    $hash = password_hash($passPost, PASSWORD_DEFAULT);
} else {
    $hash = null;
}
//
// Button: Add / update
//
if (isset($_POST['addUpdate'])) {
    //
    // Determine insert or update, check for unique e-mail address
    //
    if ($_POST['existing'] == null) {
        $dbh = new PDO($dbSubscribers);
        $stmt = $dbh->prepare('SELECT email FROM users WHERE email=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(array(muddle($emailPost)));
        $row = $stmt->fetch();
        $dbh = null;
        if (isset($row['email'])) {
            header('Location: ' . $uri . 'subscribers.php');
            exit;
        } else {
            $dbh = new PDO($dbSubscribers);
            $stmt = $dbh->query('DELETE FROM users WHERE email IS NULL');
            $stmt = $dbh->prepare('INSERT INTO users (email) VALUES (?)');
            $stmt->execute(array(null));
            $idUser = $dbh->lastInsertId();
            $dbh = null;
        }
    } else {
        $dbh = new PDO($dbSubscribers);
        $stmt = $dbh->prepare('SELECT idUser FROM users WHERE idUser=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(array($idUserPost));
        $row = $stmt->fetch();
        $dbh = null;
        extract($row);
    }
    //
    // Apply update
    //
    if ($_POST['email'] != null) {
        $dbh = new PDO($dbSubscribers);
        if ($passPost == null or $passPost == '') {
            $stmt = $dbh->prepare('UPDATE users SET email=?, payStatus=?, note=?, contributor=?, classifiedOnly=?, deliver=?, deliveryAddress=?, dCityRegionPostal=?, billingAddress=?, bCityRegionPostal=?, soa=? WHERE idUser=?');
            $stmt->execute(array(muddle($emailPost), $payStatusPost, $notePost, $contributorPost, $classifiedOnlyPost, $deliverPost, $deliveryAddressPost, $dCityRegionPostalPost, $billingAddressPost, $bCityRegionPostalPost, 1, $idUser));
        } else {
            $stmt = $dbh->prepare('UPDATE users SET email=?, pass=?, payStatus=?, note=?, contributor=?, classifiedOnly=?, deliver=?, deliveryAddress=?, dCityRegionPostal=?, billingAddress=?, bCityRegionPostal=?, soa=? WHERE idUser=?');
            $stmt->execute(array(muddle($emailPost), $hash, $payStatusPost, $notePost, $contributorPost, $classifiedOnlyPost, $deliverPost, $deliveryAddressPost, $dCityRegionPostalPost, $billingAddressPost, $bCityRegionPostalPost, 1, $idUser));
        }
        $dbh = null;
        //
        // Synchronize with remote databases
        //
        include $includesPath . '/syncSubscribers.php';
    } else {
        $message = 'No e-mail address was input.';
    }
}
//
// Button: Delete
//
if (isset($_POST['delete'])) {
    if ($_POST['email'] != null) {
        $dbh = new PDO($dbSubscribers);
        $stmt = $dbh->prepare('SELECT idUser FROM users WHERE email=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(array(muddle($emailPost)));
        $row = $stmt->fetch();
        $dbh = null;
        if (isset($row['idUser'])) {
            extract($row);
            $dbh = new PDO($dbSubscribers);
            $stmt = $dbh->prepare('DELETE FROM users WHERE idUser=?');
            $stmt->execute(array($idUser));
            $dbh = null;
            //
            // Update remote sites
            //
            $response = null;
            $request['task'] = 'subscriberDelete';
            $request['idUser'] = $idUser;
            $dbh = new PDO($dbRemote);
            $stmt = $dbh->query('SELECT remote FROM remotes');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            foreach ($stmt as $row) {
                extract($row);
                $response = soa($remote . 'z/', $request);
            }
            $dbh = null;
        } else {
            $message = 'The e-mail address was not found.';
        }
    } else {
        $message = 'No e-mail address was input.';
    }
}
//
// Button, edit
//
if (isset($_POST['edit'])) {
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->prepare('SELECT idUser, email, payStatus, note, contributor, classifiedOnly, deliver, deliveryAddress, dCityRegionPostal, billingAddress, bCityRegionPostal FROM users WHERE idUser=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idUserPost));
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        extract($row);
        $bCityRegionPostalEdit = $bCityRegionPostal;
        $billingAddressEdit = $billingAddress;
        $classifiedOnlyEdit = $classifiedOnly;
        $contributorEdit = $contributor;
        $deliverEdit = $deliver;
        $dCityRegionPostalEdit = $dCityRegionPostal;
        $deliveryAddressEdit = $deliveryAddress;
        $emailEdit = plain($email);
        $idUserEdit = $idUser;
        $noteEdit = $note;
        $paidEdit = $payStatus;
    }
}

//
// HTML
//
require $includesPath . '/header1.inc';
echo "  <title>Subscriber maintenance</title>\n";
echo '  <script type="text/javascript" src="z/wait.js"></script>' . "\n";
require $includesPath . '/header2.inc';
require $includesPath . '/body.inc';
?>

  <h4 class="m"><a class="s" href="subscribers.php">&nbsp;Subscriber maintenance&nbsp;</a></h4>
<?php echoIfMessage($message); ?>

  <h1 id="waiting">Please wait.</h1>

  <h1><span class="h">Subscribing users</span></h1>

<?php
require $includesPath . '/syncSubscribers.php';
$rowcount = null;
$dbh = new PDO($dbSubscribers);
$stmt = $dbh->query('SELECT idUser, email, pass, payStatus, note, contributor, classifiedOnly, deliver, deliveryAddress, dCityRegionPostal, billingAddress, bCityRegionPostal FROM users ORDER BY email');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    extract($row);
    $rowcount++;
    $email = '    <p><span class="p">' . plain($email) . "<br />\n";
    if (empty($pass)) {
        $pass = "    The password is not set!<br />\n";
    } else {
        $pass = null;
    }
    if ($payStatus == 1) {
        $payStatus = "    Paid<br />\n";
    } else {
        $payStatus = "    Not paid<br />\n";
    }
    if (!empty($deliver)) {
        $deliver = "    Deliver a printed paper<br />\n";
    } else {
        $deliver = "    Web subscription only<br />\n";
    }
    if (!empty($contributor)) {
        $contributor = "    Article contributor<br />\n";
    }
    if (!empty($classifiedOnly)) {
        $classifiedOnly = "    Free reg. to enter classified ads<br />\n";
    }
    if (!empty($note)) {
        $note = '    ' . $note . "<br />";
    }
    if (!empty($billingAddress)) {
        $billingAddress = "    Billing address:<br />\n" . '    &nbsp;&nbsp;' . $billingAddress . "<br />\n";
    }
    if (!empty($bCityRegionPostal)) {
        $bCityRegionPostal = '    &nbsp;&nbsp;' . $bCityRegionPostal . "<br />\n";
    }
    if (!empty($deliveryAddress)) {
        $deliveryAddress = "    Delivery address:<br />\n" . '    &nbsp;&nbsp;' . $deliveryAddress . "<br />\n";
    }
    if (!empty($dCityRegionPostal)) {
        $dCityRegionPostal = '    &nbsp;&nbsp;' . $dCityRegionPostal . "<br />\n";
    }
    echo '  <form class="wait" action="' . $uri . 'subscribers.php" method="post">' . "\n";
    echo $email;
    echo $pass;
    echo $payStatus;
    echo $deliver;
    echo $contributor;
    echo $classifiedOnly;
    echo $note;
    echo $billingAddress;
    echo $bCityRegionPostal;
    echo $deliveryAddress;
    echo $dCityRegionPostal;
    echo '    <input name="idUser" type="hidden" value="' . $idUser . '" /><input type="submit" value="Edit" name="edit" class="button" /></span></p>' . "\n";
    echo "  </form>\n\n";

    $patron[$rowcount]['email'] = $email;
    $patron[$rowcount]['payStatus'] = $payStatus;
    $patron[$rowcount]['note'] = $note;
    $patron[$rowcount]['idUser'] = $idUser;
}
$dbh = null;
?>
  <form class="wait" action="<?php echo $uri; ?>subscribers.php" method="post">
    <h1>Add, update and delete users</h1>

    <p>Email and password are required for add. Password is not required for an update unless the password is changing. The e-mail address only is required for delete and to update the e-mail address. E-mail addresses must be unique.</p>

    <p><label for="email">E-mail</label><br />
    <input id="email" name="email" type="text" class="h" required<?php echoIfValue($emailEdit); ?> /><input name="idUser" type="hidden" <?php echoIfValue($idUserEdit); ?> /></p>

    <p><label for="pass">Password</label><br />
    <input id="pass" name="pass" type="text" class="h" /></p>

    <p><label>
      <input name="payStatus" type="checkbox" value="1"<?php echoIfYes($paidEdit); ?> /> Paid subscriber<br />
    </label>
    <label>
      <input name="deliver" type="checkbox" value="1"<?php echoIfYes($deliverEdit); ?> /> Deliver a printed paper<br />
    </label>
    <label>
      <input name="contributor" type="checkbox" value="1"<?php echoIfYes($contributorEdit); ?> /> Article contributor<br />
    </label>
    <label>
      <input name="classifiedOnly" type="checkbox" value="1"<?php echoIfYes($classifiedOnlyEdit); ?> /> Free reg. to enter classified ads
    </label></p>

    <p><label for="billingAddress">Billing address</label><br />
    <input id="billingAddress" name="billingAddress" type="text" class="h" placeholder="Billing address" <?php echoIfValue($billingAddressEdit); ?> /><br />
    <br />
    <input name="bCityRegionPostal" type="text" class="h" placeholder="City Region Post Code" <?php echoIfValue($bCityRegionPostalEdit); ?> /></p>

    <p><label for="deliveryAddress">Delivery address (if different than billing address)</label><br />
    <input id="deliveryAddress" name="deliveryAddress" type="text" class="h" placeholder="Delivery address" <?php echoIfValue($deliveryAddressEdit); ?> /><br />
    <br />
    <input name="dCityRegionPostal" type="text" class="h" placeholder="City Region Post Code" <?php echoIfValue($dCityRegionPostalEdit); ?> /></p>

    <p><label for="note">Note</label><br />
    <textarea id="note" name="note" class="h"><?php echoIfText($noteEdit); ?></textarea></p>

    <p class="b"><input type="submit" value="Add / update" name="addUpdate" class="button" /><br />
    <input type="submit" value="Delete" name="delete" class="button" /><input type="hidden" name="existing"<?php echoIfValue($edit); ?> /></p>
  </form>
</body>
</html>
