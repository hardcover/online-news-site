<?php
/**
 * For subscribers to manage their account information
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2018 10 08
 * @link      https://hardcoverwebdesign.com/
 * @link      https://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
//
// Variables
//
$bCityRegionPostalEdit = null;
$bCityRegionPostalPost = inlinePost('bCityRegionPostal');
$billingAddressEdit = null;
$billingAddressPost = inlinePost('billingAddress');
$classifiedOnlyEdit = null;
$classifiedOnlyPost = inlinePost('classifiedOnly');
$dCityRegionPostalEdit = null;
$dCityRegionPostalPost = inlinePost('dCityRegionPostal');
$deliverEdit = null;
$deliverPost = inlinePost('deliver');
$deliveryAddressEdit = null;
$deliveryAddressPost = inlinePost('deliveryAddress');
$emailNewsPost = inlinePost('emailNews');
$emailNewsEdit = null;
$emailEdit = null;
$emailPost = muddle(inlinePost('email'));
$idUserEdit = null;
$idUserPost = inlinePost('idUser');
$message = null;
$payNow = null;
//
// Button: Send
//
if (isset($_POST['submit']) and isset($idUserPost)) {
    if ($_SESSION['db'] === 's') {
        $dbh = new PDO($dbSubscribers);
    } elseif ($_SESSION['db'] === 'n') {
        $dbh = new PDO($dbSubscribersNew);
    }
    $stmt = $dbh->prepare('UPDATE users SET email=?, classifiedOnly=?, deliver=?, deliver2=?, deliveryAddress=?, dCityRegionPostal=?, billingAddress=?, bCityRegionPostal=?, soa=? WHERE idUser=?');
    $stmt->execute([$emailPost, $classifiedOnlyPost, $deliverPost, $emailNewsPost, $deliveryAddressPost, $dCityRegionPostalPost, $billingAddressPost, $bCityRegionPostalPost, 1, $idUserPost]);
    $dbh = null;
    $message = 'The preferences were sent.';
}
//
// Set form variables for edit
//
if ($_SESSION['db'] === 's') {
    $dbh = new PDO($dbSubscribers);
} elseif ($_SESSION['db'] === 'n') {
    $dbh = new PDO($dbSubscribersNew);
}
$stmt = $dbh->prepare('SELECT email, payStatus, classifiedOnly, deliver, deliver2, deliveryAddress, dCityRegionPostal, billingAddress, bCityRegionPostal FROM users WHERE idUser=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$_SESSION['userId']]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    extract($row);
    $idUserEdit = $_SESSION['userId'];
    $emailEdit = ' value="' . html(plain($email)) . '"';
    $deliveryAddressEdit = ' value="' . html($deliveryAddress) . '"';
    $dCityRegionPostalEdit = ' value="' . html($dCityRegionPostal) . '"';
    $billingAddressEdit = ' value="' . html($billingAddress) . '"';
    $bCityRegionPostalEdit = ' value="' . html($bCityRegionPostal) . '"';
    if ($classifiedOnly == 1) {
        $classifiedOnlyEdit = ' checked';
    }
    if ($deliver == 1) {
        $deliverEdit = ' checked';
    }
    if ($deliver2 == 1) {
        $emailNewsEdit = ' checked';
    }
    if (empty($payStatus)) {
        $payStatusEdit = 'The account is not paid.';
    } else {
        $paidThrough = date("F j, Y", $payStatus);
        $payStatusEdit = 'The account is paid through ' . $paidThrough . '.';
    }
    if (($emailNewsEdit === ' checked' or $deliverEdit === ' checked') and $payStatusEdit === 'The account is not paid.') {
        $payNow = "      <p><label>\n";
        $payNow.= '        <input type="checkbox" name="payNow" value="1" /> Pay now' . "\n";
        $payNow.= "      </label></p>\n\n";
    }
}
//
// HTML
//
echoIfMessage($message);
echo '    <h1>Manage my account</h1>' . "\n\n";
echo '    <p>' . $payStatusEdit . "</p>\n\n";
echo '    <form method="post" action="' . $uri . '?m=my-account">' . "\n";
echo $payNow;
echo '      <p><label for="email">Email</label><br />' . "\n";
echo '      <input id="email" name="email" type="email" class="w"' . $emailEdit . ' /></p>' . "\n\n";
if (file_exists($includesPath . '/custom/programs/emailEdition.php')) {
    include $includesPath . '/custom/programs/emailEdition.php';
}
echo "      <p><label>\n";
echo '        <input type="checkbox" name="deliver" value="1"' . $deliverEdit . ' /> Send a print edition to the delivery address below' . "\n";
echo "      </label></p>\n\n";
echo '      <p><label for="deliveryAddress">Delivery address</label><br />' . "\n";
echo '      <input id="deliveryAddress" name="deliveryAddress" type="text" class="w"' . $deliveryAddressEdit . ' /></p>' . "\n\n";
echo '      <p><label for="dCityRegionPostal">Delivery city region postal code</label><br />' . "\n";
echo '      <input id="dCityRegionPostal" name="dCityRegionPostal" type="text" class="w"' . $dCityRegionPostalEdit . ' /></p>' . "\n\n";
echo '      <p><label for="billingAddress">Billing address (if different from the delivery address)</label><br />' . "\n";
echo '      <input id="billingAddress" name="billingAddress" type="text" class="w"' . $billingAddressEdit . ' /></p>' . "\n\n";
echo '      <p><label for="bCityRegionPostal">Billing city region postal code</label><br />' . "\n";
echo '      <input id="bCityRegionPostal" name="bCityRegionPostal" type="text" class="w"' . $bCityRegionPostalEdit . ' /></p>' . "\n\n";
echo '      <p><input type="submit" name="submit" class="button" value="Send preferences" /><input type="hidden" name="idUser" value="' . $idUserEdit . '" /></p>' . "\n";
echo "    </form>\n";
?>
