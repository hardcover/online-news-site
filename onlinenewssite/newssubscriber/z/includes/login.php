<?php
/**
 * For registering and logging in users
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2016 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *.@license   http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2016-09-19
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
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
//
// HTML
//
echoIfMessage($message);
?>
    <h1>Log in / Register</h1>

    <p>A free registration is required to place classified ads and to submit other site forms. A paid registration might be required to access some site content. All registrations begin with an e-mail and password. The e-mail must be verified before the registration can be used to log in. Instructions to verify the e-mail address will follow after the information below is sent.</p>

    <form action="<?php echo $uri; ?>post.php" method="post">
      <p><label for="email">E-mail</label><br />
      <input id="email" name="email" type="email" class="w" required autofocus /></p>

      <p><label for="pass">Password</label><br />
      <input id="pass" name="pass" type="password" class="w" required /></p>

      <p><input type="submit" name="login" value="Log in" class="button" /> <input type="submit" name="register" value="Register" class="button" /></p>
    </form>

    <p><a href="<?php echo $uri; ?>?t=c">Forgot password?</a></p>
