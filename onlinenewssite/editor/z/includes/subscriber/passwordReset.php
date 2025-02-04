<?php
/**
 * For resetting a forgotten password
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2025 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 * @version:  2025 02 03
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
//
// Check for existing configuration file, create one if not found
//
if (!file_exists('z/system/configuration.php')) {
    copy('editor/z/system/configuration.inc', 'editor/z/system/configuration.php');
}
require 'editor/z/system/configuration.php';
$uri = $uriScheme . '://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
//
// Create the databases on the first run
//
require $includesPath . '/editor/createDatabases.php';
//
// Variables
//
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
} else {
    $message = '';
}
if (isset($_GET['v'])) {
    $vGet = secure($_GET['v']);
} else {
    $vGet = null;
}
//
// HTML
//
?>
    <div class="main">
      echoIfMessage($message);
      <h1>Reset Password</h1>

      <p>Complete the form below to set a new password.</p>

      <form action="<?php echo $uri; ?>post.php" method="post">
        <p><label for="passOne">New password</label><br>
        <input id="passOne" name="passOne" type="password" class="wide" required></p>

        <p><label for="passTwo">Confirm new password</label><br>
        <input id="passTwo" name="passTwo" type="password" class="wide" required></p>

        <p><input type="submit" class="button" name="resetPassword" value="Set new password"><input type="hidden" name="verify"<?php echoIfValue($vGet); ?>></p>
      </form>
    </div>
