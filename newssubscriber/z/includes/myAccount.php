<?php
/**
 * For subscribers to manage their account information
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
$emailEdit = null;
$emailPost = muddle(inlinePost('email'));
$idUserEdit = null;
$idUserPost = inlinePost('idUser');
$message = null;
//
// Button: Send
//
if (isset($_POST['submit']) and isset($idUserPost)) {
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->prepare('UPDATE users SET email=?, classifiedOnly=?, deliver=?, deliveryAddress=?, dCityRegionPostal=?, billingAddress=?, bCityRegionPostal=?, soa=? WHERE idUser=?');
    $stmt->execute(array($emailPost, $classifiedOnlyPost, $deliverPost, $deliveryAddressPost, $dCityRegionPostalPost, $billingAddressPost, $bCityRegionPostalPost, 1, $idUserPost));
    $dbh = null;
    $message = 'The updated record has been sent.';
}
//
// Set form variables for edit
//
$dbh = new PDO($dbSubscribers);
$stmt = $dbh->prepare('SELECT email, classifiedOnly, deliver, deliveryAddress, dCityRegionPostal, billingAddress, bCityRegionPostal FROM users WHERE idUser=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array($_SESSION['userId']));
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
}
//
// HTML
//
echoIfMessage($message);
echo '    <h1>Manage my account</h1>' . "\n\n";
echo '    <form method="post" action="' . $uri . '?m=my-account">' . "\n";
echo '      <p><label for="email">E-mail</label><br />' . "\n";
echo '      <input id="email" name="email" type="email" class="w"' . $emailEdit . ' /></p>' . "\n\n";
echo "      <p><label>\n";
echo '        <input type="checkbox" name="deliver" value="1"' . $deliverEdit . ' /> Also deliver a paper to the delivery address below' . "\n";
echo "      </label></p>\n\n";
echo "      <p><label>\n";
echo '        <input type="checkbox" name="classifiedOnly" value="1"' . $classifiedOnlyEdit . ' /> Free registration for entering classified ads' . "\n";
echo "      </label></p>\n\n";
echo '      <p><label for="billingAddress">Billing address</label><br />' . "\n";
echo '      <input id="billingAddress" name="billingAddress" type="text" class="w"' . $billingAddressEdit . ' /></p>' . "\n\n";
echo '      <p><label for="bCityRegionPostal">Billing city region postal code</label><br />' . "\n";
echo '      <input id="bCityRegionPostal" name="bCityRegionPostal" type="text" class="w"' . $bCityRegionPostalEdit . ' /></p>' . "\n\n";
echo '      <p><label for="deliveryAddress">Delivery address (if different from the billing address)</label><br />' . "\n";
echo '      <input id="deliveryAddress" name="deliveryAddress" type="text" class="w"' . $deliveryAddressEdit . ' /></p>' . "\n\n";
echo '      <p><label for="dCityRegionPostal">Delivery city region postal code</label><br />' . "\n";
echo '      <input id="dCityRegionPostal" name="dCityRegionPostal" type="text" class="w"' . $dCityRegionPostalEdit . ' /></p>' . "\n\n";
echo '      <p><input type="submit" name="submit" class="button" value="Send changes" /><input type="hidden" name="idUser" value="' . $idUserEdit . '" /></p>' . "\n";
echo "    </form>\n";
?>
