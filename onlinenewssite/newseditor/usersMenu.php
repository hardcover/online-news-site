<?php
/**
 * User maintenance for the users who maintain the site menu
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2018 11 29
 * @link      https://hardcoverwebdesign.com/
 * @link      https://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
session_start();
require 'z/system/configuration.php';
require $includesPath . '/authorization.php';
require $includesPath . '/common.php';
//
// User-group authorization
//
if ($_SESSION['username'] != 'admin') {
    include 'logout.php';
    exit;
}
//
// Variables
//
$adminPassPost = inlinePost('adminPass');
$edit = inlinePost('edit');
$fullNamePost = inlinePost('fullName');
$idUserPost = inlinePost('idUser');
$passPost = inlinePost('pass');
$userPost = inlinePost('user');
//
$fullNameEdit = null;
if ($passPost != null) {
    $hash = password_hash($passPost, PASSWORD_DEFAULT);
} else {
    $hash = null;
}
$idUserEdit = null;
$message = null;
$userEdit = null;
//
// Test admin password authentication
//
if (isset($_POST['adminPass']) and ($_POST['adminPass'] == null or $_POST['adminPass'] == '')) {
    $message = 'The admin password is required for all user maintenance.';
}
$dbh = new PDO($dbEditors);
$stmt = $dbh->prepare('SELECT pass FROM users WHERE user=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$_SESSION['username']]);
$row = $stmt->fetch();
$dbh = null;
if (password_verify($adminPassPost, $row['pass'])) {
    //
    // Button: Add / update
    //
    if (isset($_POST['addUpdate'])) {
        //
        // Determine insert or update, check for unique user name
        //
        if ($_POST['existing'] == null) {
            $dbh = new PDO($dbEditors);
            $stmt = $dbh->prepare('SELECT user FROM users WHERE user=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$userPost]);
            $row = $stmt->fetch();
            $dbh = null;
            if (isset($row['user'])) {
                header('Location: ' . $uri . 'usersMenu.php');
                exit;
            } else {
                $dbh = new PDO($dbEditors);
                $stmt = $dbh->query('DELETE FROM users WHERE user IS NULL');
                $stmt = $dbh->prepare('INSERT INTO users (user) VALUES (?)');
                $stmt->execute([null]);
                $idUser = $dbh->lastInsertId();
                $dbh = null;
            }
        } else {
            $dbh = new PDO($dbEditors);
            $stmt = $dbh->prepare('SELECT idUser FROM users WHERE idUser=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$idUserPost]);
            $row = $stmt->fetch();
            $dbh = null;
            extract($row);
        }
        //
        // Apply update
        //
        if ($_POST['user'] != null) {
            $dbh = new PDO($dbEditors);
            if (is_null($hash)) {
                $stmt = $dbh->prepare('UPDATE users SET user=?, fullName=?, userType=? WHERE idUser=?');
                $stmt->execute([$userPost, $fullNamePost, 5, $idUser]);
            } else {
                $stmt = $dbh->prepare('UPDATE users SET user=?, pass=?, fullName=?, userType=? WHERE idUser=?');
                $stmt->execute([$userPost, $hash, $fullNamePost, 5, $idUser]);
            }
            $dbh = null;
        } else {
            $message = 'No user name was input.';
        }
    }
    //
    // Button: Delete
    //
    if (isset($_POST['delete'])) {
        if ($userPost != "admin") {
            if ($_POST['user'] != null) {
                $dbh = new PDO($dbEditors);
                $stmt = $dbh->prepare('SELECT user FROM users WHERE user=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$userPost]);
                $row = $stmt->fetch();
                $dbh = null;
                if (isset($row['user'])) {
                    $dbh = new PDO($dbEditors);
                    $stmt = $dbh->prepare('DELETE FROM users WHERE user=?');
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $stmt->execute([$userPost]);
                    $dbh = null;
                } else {
                    $message = 'The user name was not found.';
                }
            } else {
                $message = 'No user name was input.';
            }
        }
    }
} elseif (isset($_POST['addUpdate']) or isset($_POST['delete'])) {
    $message = 'The admin password is invalid.';
}
//
// Button: Edit
//
if (isset($_POST['edit'])) {
    $dbh = new PDO($dbEditors);
    $stmt = $dbh->prepare('SELECT idUser, user, fullName FROM users WHERE idUser=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idUserPost]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        extract($row);
        $fullNameEdit = $fullName;
        $idUserEdit = $idUser;
        $userEdit = $user;
    }
}
//
// HTML
//
require $includesPath . '/header1.inc';
echo "  <title>Menu user maintenance</title>\n";
echo '  <script src="z/wait.js"></script>' . "\n";
require $includesPath . '/header2.inc';
require $includesPath . '/body.inc';
?>

  <h4 class="m"><a class="m" href="usersEditors.php">&nbsp;Editing users&nbsp;</a><a class="m" href="usersSubscribers.php">&nbsp;Patron mgt users&nbsp;</a></h4>

  <h4 class="m"><a class="m" href="usersAdvertising.php">&nbsp;Advertising users&nbsp;</a><a class="m" href="usersClassified.php">&nbsp;Classified users&nbsp;</a></h4>

  <h4 class="m"><a class="s" href="usersMenu.php">&nbsp;Menu users&nbsp;</a><a class="m" href="settings.php">&nbsp;Settings&nbsp;</a><a class="m" href="classifiedSections.php">&nbsp;Classifieds&nbsp;</a></h4>
<?php echoIfMessage($message); ?>

  <h1 id="waiting">Please wait.</h1>

  <h1><span class="h">Menu management users</span></h1>

<?php
$rowcount = null;
$dbh = new PDO($dbEditors);
$stmt = $dbh->query('SELECT idUser, user, pass, fullName FROM users WHERE userType = 5 ORDER BY fullName');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute();
foreach ($stmt as $row) {
    extract($row);
    if (empty($pass)) {
        $printPass = '<b>NOT SET!</b>';
    } else {
        $printPass = 'set.';
    }
    if ($user != 'admin') {
        $rowcount++;
        echo '  <form class="wait" action="' . $uri . 'usersMenu.php" method="post">' . "\n";
        echo '    <p><span class="p">' . html($fullName) . " - Full name<br />\n";
        echo '    ' . html($user) . " - User name, count: $rowcount<br />\n";
        echo "    The password is $printPass<br />\n";
        echo '    <input name="idUser" type="hidden" value="' . $idUser . '" /><input type="submit" class="button" value="Edit" name="edit" /></span></p>' . "\n";
        echo "  </form>\n\n";
    }
}
$dbh = null;
?>
  <h1>Menu management user maintenance</h1>

  <form class="wait" action="<?php echo $uri; ?>usersMenu.php" method="post">
    <p>The admin password is required for all user maintenance.</p>

    <p><label for="adminPass">Password</label><br />
    <input id="adminPass" name="adminPass" type="password" class="h" autofocus required /></p>

    <h1>Add, update and delete users</h1>

    <p>All fields are required to add a user. For an update, the full name and user name are required, the password will remain unchanged if left blank. The user name only is required for delete. User names must be unique.</p>

    <p><label for="fullName">Full name</label><br />
    <input id="fullName" name="fullName" type="text" class="h"<?php echoIfValue($fullNameEdit); ?> /></p>

    <p><label for="user">User name</label><br />
    <input id="user" name="user" type="text" class="h" required<?php echoIfValue($userEdit); ?> /><input name="idUser" type="hidden" <?php echoIfValue($idUserEdit); ?> /></p>

    <p><label for="pass">Password</label><br />
    <input id="pass" name="pass" type="text" class="h" /></p>

    <p class="b"><input type="submit" class="button" value="Add / update" name="addUpdate" /><br />
    <input type="submit" class="button" value="Delete" name="delete" /><input type="hidden" name="existing"<?php echoIfValue($edit); ?> /></p>
  </form>
</body>
</html>
