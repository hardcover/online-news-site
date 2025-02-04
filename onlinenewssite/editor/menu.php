<?php
/**
 * Menu maintenance
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
session_start();
require 'z/system/configuration.php';
$includesPath = '../' . $includesPath;
require $includesPath . '/editor/authorization.php';
require $includesPath . '/editor/common.php';
//
// User-group authorization
//
$dbh = new PDO($dbEditors);
$stmt = $dbh->prepare('SELECT userType FROM users WHERE idUser=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$_SESSION['userId']]);
$row = $stmt->fetch();
$dbh = null;
if (empty($row['userType']) or strval($row['userType']) !== '5') {
    include 'logout.php';
    exit;
}
//
// Variables
//
$edit = inlinePost('edit');
$idMenuEdit = null;
$idMenuPost = inlinePost('idMenu');
$menuContentEdit = null;
if (isset($_POST['menuContent'])) {
    $menuContentPost = stripslashes($_POST['menuContent']);
    $menuContentPost = preg_replace('{^\xEF\xBB\xBF|\x1A}', '', $menuContentPost);
    $menuContentPost = str_replace("\r\n", "\n", $menuContentPost);
    $menuContentPost = str_replace("\r", "\n", $menuContentPost);
    $menuContentPost = str_replace("\t", '    ', $menuContentPost);
}
$menuNameEdit = null;
$menuNamePost = inlinePost('menuName');
$menuSortOrderEdit = null;
$menuSortOrderPost = inlinePost('menuSortOrder');
$message = '';
//
// Button: Add / update
//
if (isset($_POST['addUpdate'])) {
    //
    // Determine insert or update, check for unique user name
    //
    if (empty($_POST['existing']) and isset($menuNamePost)) {
        $dbh = new PDO($dbMenu);
        $stmt = $dbh->prepare('SELECT menuName FROM menu WHERE menuName=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$menuNamePost]);
        $row = $stmt->fetch();
        $dbh = null;
        if ($row) {
            $message = 'The menu name is already in use.';
            header('Location: ' . $uri . 'menu.php');
            exit;
        } else {
            $dbh = new PDO($dbMenu);
            $stmt = $dbh->query('DELETE FROM menu WHERE menuName IS NULL');
            $stmt = $dbh->prepare('INSERT INTO menu (menuName) VALUES (?)');
            $stmt->execute([null]);
            $idMenu = $dbh->lastInsertId();
            $dbh = null;
        }
    } elseif (isset($menuNamePost)) {
        $dbh = new PDO($dbMenu);
        $stmt = $dbh->prepare('SELECT idMenu FROM menu WHERE idMenu=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idMenuPost]);
        $row = $stmt->fetch();
        $dbh = null;
        extract($row);
    }
    //
    // Apply update
    //
    if (isset($_POST['menuName'])) {
        //
        // Establish the change in sort order, if any
        //
        $dbh = new PDO($dbMenu);
        $stmt = $dbh->prepare('SELECT menuSortOrder FROM menu WHERE idMenu=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idMenu]);
        $row = $stmt->fetch();
        $dbh = null;
        if ($row
            and !empty($row['menuSortOrder'])
            and $menuSortOrderPost > $row['menuSortOrder']
        ) {
            $menuSortOrderPost++;
        }
        //
        $menuPath = mb_strtolower($menuNamePost);
        $menuPath = str_replace(' ', '-', $menuPath);
        $menuPath = str_replace("'", '', $menuPath);
        $dbh = new PDO($dbMenu);
        $stmt = $dbh->prepare('UPDATE menu SET menuName=?, menuSortOrder=?, sortPriority=?, menuPath=?, menuContent=? WHERE idMenu=?');
        $stmt->execute([$menuNamePost, $menuSortOrderPost, 1, $menuPath, $menuContentPost, $idMenu]);
        $dbh = null;
    } else {
        $message = 'No menu name was input.';
    }
}
//
// Button: Delete
//
if (isset($_POST['delete'])) {
    if (isset($_POST['menuName'])) {
        $dbh = new PDO($dbMenu);
        $stmt = $dbh->prepare('SELECT menuName FROM menu WHERE menuName=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$menuNamePost]);
        $dbh = null;
        $row = $stmt->fetch();
        if ($row) {
            $dbh = new PDO($dbMenu);
            $stmt = $dbh->prepare('DELETE FROM menu WHERE menuName=?');
            $stmt->execute([$menuNamePost]);
            $dbh = null;
        } else {
            $message = 'The menu name was not found.';
        }
    } else {
        $message = 'No menu name was input.';
    }
}
//
// Update menu sort order
//
if (isset($_POST['addUpdate']) or isset($_POST['delete'])) {
    $count = null;
    $dbh = new PDO($dbMenu);
    $stmt = $dbh->query('SELECT idMenu, menuSortOrder FROM menu ORDER BY menuSortOrder, sortPriority');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        extract($row);
        $count++;
        $stmt = $dbh->prepare('UPDATE menu SET menuSortOrder=? WHERE idMenu=?');
        $stmt->execute([$count, $idMenu]);
    }
    $stmt = $dbh->prepare('UPDATE menu SET sortPriority=?');
    $stmt->execute([2]);
    $dbh = null;
}
//
// Button: Edit
//
if (isset($_POST['edit'])) {
    $dbh = new PDO($dbMenu);
    $stmt = $dbh->prepare('SELECT idMenu, menuName, menuSortOrder, menuContent FROM menu WHERE idMenu=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idMenuPost]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        extract($row);
        $idMenuEdit = $idMenu;
        $menuContentEdit = $menuContent;
        $menuNameEdit = $menuName;
        $menuSortOrderEdit = $menuSortOrder;
    }
}
//
// HTML
//
require $includesPath . '/editor/header1.inc';
echo "  <title>Menu maintenance</title>\n";
echo '  <script src="z/wait.js"></script>' . "\n";
require $includesPath . '/editor/header2.inc';
?>

  <nav class="n">
    <h4 class="m"><a class="s" href="menu.php">Menu</a><a class="m" href="menuCalendar.php">Calendar</a><a class="m" href="menuPredefine.php">Predefined</a></h4>
  </nav>
<?php echoIfMessage($message); ?>

  <div class="flex">
    <main>
      <h1>Menu maintenance</h1>

      <form action="<?php echo $uri; ?>menu.php" method="post">
        <p>Add, update, order and delete menu items. The name and sort order fields are required to add or update a menu item. Menu names must be unique and may not contain ampersands (&amp;).</p>

        <p><label for="menuName">Name / page title</label><br>
        <input id="menuName" name="menuName" class="h"<?php echoIfValue($menuNameEdit); ?>></p>

        <p><label for="menuSortOrder">Sort order</label><br>
        <input id="menuSortOrder" name="menuSortOrder" class="h"<?php echoIfValue($menuSortOrderEdit); ?>><input name="idMenu" type="hidden" <?php echoIfValue($idMenuEdit); ?>></p>

        <p><label for="menuContent">Page content is entered as <a href="markdown.html" target="_blank">markdown syntax</a>, HTML or a custom program. Enter iframe and video tags inside paragraph tags, for example, &lt;p&gt;&lt;iframe height="315"&gt;&lt;/iframe&gt;&lt;/p&gt;. Locate custom programs in the subscriber directory includes/custom/programs. Reference them here with the word "require" and the name of the program in the page content field without quotes or punctuation, for example: require payment-form.php</label><br>
        <textarea id="menuContent" name="menuContent" class="h" rows="8"><?php echoIfText($menuContentEdit); ?></textarea></p>

        <p><input type="submit" value="Add / update" name="addUpdate" class="button"> <input type="submit" value="Delete" name="delete" class="button"><input type="hidden" name="existing"<?php echoIfValue($edit); ?>></p>
      </form>
    </main>

    <aside>
      <h2>Menu</h2>

<?php
$rowcount = null;
$dbh = new PDO($dbMenu);
$stmt = $dbh->query('SELECT idMenu, menuName, menuSortOrder, menuContent FROM menu ORDER BY menuSortOrder');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    extract($row);
    $rowcount++;
    echo '      <form action="' . $uri . 'menu.php" method="post">' . "\n";
    echo '        <p>' . html($menuName) . " - order: $rowcount<br>\n";
    echo '        <input name="idMenu" type="hidden" value="' . $idMenu . '"><input type="submit" value="Edit" name="edit" class="button"></p>' . "\n";
    echo "      </form>\n\n";
}
$dbh = null;
?>
    </aside>
  </div>
</body>
</html>
