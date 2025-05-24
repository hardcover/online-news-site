<?php
/**
 * For registering and logging in users
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
// Check for existing configuration file, create one if not found
//
if (!file_exists('editor/z/system/configuration.php')) {
    copy('editor/z/system/configuration.inc', 'z/system/configuration.php');
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
$information = null;
//
// HTML
//
echo '    <div class="main">' . "\n";
echoIfMessage($message);
echo "      <h1>Log in / Register</h1>\n\n";
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT information FROM registration');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $temp = Parsedown::instance()->parse($row['information']);
    $temp = str_replace("\n", "\n\n      ", $temp);
    $information = '      ' . $temp . "\n\n";
    $temp = null;
}
?>
      <form action="<?php echo $uri; ?>post.php" method="post">
        <p><label for="email">Email</label><br>
        <input id="email" name="email" type="email" class="wide" required></p>

        <p><label for="pass">Password</label><br>
        <input id="pass" name="pass" type="password" class="wide" required></p>

        <p><input type="submit" name="login" value="Log in" class="button"> <input type="submit" name="register" value="Register" class="button"></p>
      </form>

      <p><a href="<?php echo $uri; ?>?t=c">Forgot password?</a></p>

      <?php echo $information; ?>
    </div>
