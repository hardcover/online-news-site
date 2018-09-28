<?php
/**
 * For resetting a forgotten password
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2018 09 28
 * @link      https://hardcoverwebdesign.com/
 * @link      https://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
//
// Check for existing configuration file, create one if not found
//
if (!file_exists('z/system/configuration.php')) {
    copy('z/system/configuration.inc', 'z/system/configuration.php');
}
require 'z/system/configuration.php';
$uri = $uriScheme . '://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
//
// Create the databases on the first run
//
require $includesPath . '/createSubscriber1.php';
require $includesPath . '/createSubscriber2.php';
require $includesPath . '/createCrypt.php';
//
// Variables
//
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
} else {
    $message = null;
}
if (isset($_GET['v'])) {
    $vGet = secure($_GET['v']);
} else {
    $vGet = null;
}
//
// HTML
//
echoIfMessage($message);
?>
    <h1>Reset Password</h1>

    <p>Complete the form below to set a new password.</p>

    <form action="<?php echo $uri; ?>post.php" method="post">
      <p><label for="passOne">New password</label><br />
      <input id="passOne" name="passOne" type="password" class="w" required /></p>

      <p><label for="passTwo">Confirm new password</label><br />
      <input id="passTwo" name="passTwo" type="password" class="w" required /></p>

      <p><input type="submit" class="button" name="resetPassword" value="Set new password" /><input type="hidden" name="verify"<?php echoIfValue($vGet); ?> /></p>
    </form>
