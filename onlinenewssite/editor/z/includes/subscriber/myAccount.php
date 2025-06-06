<?php
/**
 * For subscribers to manage their account information
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
$idUserEdit = '';
$idUserPost = inlinePost('idUser');
$message = '';
$payNow = null;
$payStatusEdit = '';
//
// Button: Send
//
if (isset($_POST['submit']) and isset($idUserPost)) {
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->prepare('UPDATE users SET email=?, classifiedOnly=?, deliver=?, deliver2=?, deliveryAddress=?, dCityRegionPostal=?, billingAddress=?, bCityRegionPostal=? WHERE idUser=?');
    $stmt->execute([$emailPost, $classifiedOnlyPost, $deliverPost, $emailNewsPost, $deliveryAddressPost, $dCityRegionPostalPost, $billingAddressPost, $bCityRegionPostalPost, $idUserPost]);
    $dbh = null;
    $message = 'The preferences were sent.';
}
//
// Set form variables for edit
//
$dbh = new PDO($dbSubscribers);
$stmt = $dbh->prepare('SELECT email, payStatus, classifiedOnly, deliver, deliver2, deliveryAddress, dCityRegionPostal, billingAddress, bCityRegionPostal FROM users WHERE idUser=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$_SESSION['userId']]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $row = array_map('strval', $row);
    extract($row);
    $idUserEdit = $_SESSION['userId'];
    $emailEdit = ' value="' . html(plain($email)) . '"';
    $deliveryAddressEdit = ' value="' . html($deliveryAddress) . '"';
    $dCityRegionPostalEdit = ' value="' . html($dCityRegionPostal) . '"';
    $billingAddressEdit = ' value="' . html($billingAddress) . '"';
    $bCityRegionPostalEdit = ' value="' . html($bCityRegionPostal) . '"';
    if ($classifiedOnly === '1') {
        $classifiedOnlyEdit = ' checked';
    }
    if ($deliver === '1') {
        $deliverEdit = ' checked';
    }
    if ($deliver2 === '1') {
        $emailNewsEdit = ' checked';
    }
    if (empty($payStatus)) {
        $payStatusEdit = 'The account is not paid.';
    } else {
        $paidThrough = date("F j, Y", $payStatus);
        $payStatusEdit = 'The account is paid through ' . $paidThrough . '.';
    }
    /*
    if (($emailNewsEdit === ' checked' or $deliverEdit === ' checked') and $payStatusEdit === 'The account is not paid.') {
        $payNow = "      <p><label>\n";
        $payNow.= '        <input type="checkbox" name="payNow" value="1"> Pay now' . "\n";
        $payNow.= "      </label></p>\n\n";
    }
    */
}
//
// HTML
//
echo '    <div class="main">' . "\n";
echoIfMessage($message);
echo '      <h1>Manage my account</h1>' . "\n\n";
echo '      <p>' . $payStatusEdit . "</p>\n\n";
echo '      <form method="post" action="' . $uri . '?m=my-account">' . "\n";
echo $payNow;
echo '        <p><label for="email">Email</label><br>' . "\n";
echo '        <input id="email" name="email" type="email" class="wide"' . $emailEdit . '></p>' . "\n\n";
if (file_exists($includesPath . '/custom/programs/emailEdition.php')) {
    include $includesPath . '/custom/programs/emailEdition.php';
}
echo "        <p><label>\n";
echo '            <input type="checkbox" name="deliver" value="1"' . $deliverEdit . '> Send a print edition to the delivery address below' . "\n";
echo "        </label></p>\n\n";
echo '        <p><label for="deliveryAddress">Delivery address</label><br>' . "\n";
echo '        <input id="deliveryAddress" name="deliveryAddress" class="wide"' . $deliveryAddressEdit . '></p>' . "\n\n";
echo '        <p><label for="dCityRegionPostal">Delivery city region postal code</label><br>' . "\n";
echo '        <input id="dCityRegionPostal" name="dCityRegionPostal" class="wide"' . $dCityRegionPostalEdit . '></p>' . "\n\n";
echo '        <p><label for="billingAddress">Billing address (if different from the delivery address)</label><br>' . "\n";
echo '        <input id="billingAddress" name="billingAddress" class="wide"' . $billingAddressEdit . '></p>' . "\n\n";
echo '        <p><label for="bCityRegionPostal">Billing city region postal code</label><br>' . "\n";
echo '        <input id="bCityRegionPostal" name="bCityRegionPostal" class="wide"' . $bCityRegionPostalEdit . '></p>' . "\n\n";
echo '        <p><input type="submit" name="submit" class="button" value="Send preferences"><input type="hidden" name="idUser" value="' . $idUserEdit . '"></p>' . "\n";
echo "      </form>\n";
echo '    </div>' . "\n";
?>
