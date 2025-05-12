<?php
/**
 * Form to request a password reset
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
//
// HTML
//
?>
    <div class="main">
      echoIfMessage($message);
      <h1>Reset Password</h1>

      <p>Complete the form below to receive an email to reset the password.</p>

      <form action="<?php echo $uri; ?>post.php" method="post">
        <p><label for="email">Email</label><br>
        <input id="email" name="email" class="wide" type="email" required></p>

        <p><label>
          <input name="forgot" type="checkbox" value="1" required> Send me an email with a link to reset my password
        </label></p>

        <p><input type="submit" class="button" name="forgotPassword" value="Forgot password"></p>
      </form>
    </div>
