<?php
/**
 * Classified ad section maintenance
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2021 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2022 09 19
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
session_start();
require 'z/system/configuration.php';
require $includesPath . '/authorization.php';
require $includesPath . '/common.php';
//
// User-group authorization
//
if ($_SESSION['username'] !== 'admin') {
    include 'logout.php';
    exit;
}
//
// Variables
//
$adminPassPost = inlinePost('adminPass');
$edit = inlinePost('edit');
$idSectionEdit = null;
$idSectionPost = inlinePost('idSection');
$message = '';
$parentIdEdit = null;
$parentSectionPost = inlinePost('parentSection');
$sectionEdit = null;
$sectionPost = inlinePost('section');
$sortOrderSectionEdit = null;
$sortOrderSectionPost = inlinePost('sortOrderSection');
$subsectionFlagPost = inlinePost('subsectionFlag');
//
$remotes = [];
$dbh = new PDO($dbRemote);
$stmt = $dbh->query('SELECT remote FROM remotes');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    $remotes[] = $row['remote'];
}
$dbh = null;
//
// Test user password
//
$dbh = new PDO($dbEditors);
$stmt = $dbh->prepare('SELECT pass FROM users WHERE user=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$_SESSION['username']]);
$row = $stmt->fetch();
$dbh = null;
if (password_verify($adminPassPost, $row['pass'])) {
    //
    // Button: Add / update section
    //
    if (isset($_POST['addUpdate'])) {
        //
        // Determine insert or update, check for unique email address
        //
        if (empty($_POST['existing'])) {
            if ($parentSectionPost === '0') {
                //
                // Parent sections
                //
                $dbh = new PDO($dbClassifieds);
                $stmt = $dbh->prepare('SELECT section FROM sections WHERE section=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$sectionPost]);
                $row = $stmt->fetch();
                $dbh = null;
                if ($row) {
                    //
                    // Do not allow a duplicate section name
                    //
                    header('Location: ' . $uri . 'classifiedSections.php');
                    exit;
                } else {
                    //
                    // Establish idSection
                    //
                    $dbh = new PDO($dbClassifieds);
                    $stmt = $dbh->query('DELETE FROM sections WHERE section IS NULL');
                    $stmt = $dbh->prepare('INSERT INTO sections (section) VALUES (?)');
                    $stmt->execute([null]);
                    $idSection = $dbh->lastInsertId();
                    $dbh = null;
                }
            } else {
                //
                // Subsections
                //
                $dbh = new PDO($dbClassifieds);
                $stmt = $dbh->prepare('SELECT subsection FROM subsections WHERE subsection=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$sectionPost]);
                $row = $stmt->fetch();
                $dbh = null;
                if ($row) {
                    //
                    // Do not allow a duplicate section name
                    //
                    header('Location: ' . $uri . 'classifiedSections.php');
                    exit;
                } else {
                    //
                    // Establish idSubsection
                    //
                    $dbh = new PDO($dbClassifieds);
                    $stmt = $dbh->query('DELETE FROM subsections WHERE subsection IS NULL');
                    $stmt = $dbh->prepare('INSERT INTO subsections (subsection) VALUES (?)');
                    $stmt->execute([null]);
                    $idSection = $dbh->lastInsertId();
                    $dbh = null;
                }
            }
        } else {
            //
            // Or verify idSection
            //
            if ($parentSectionPost === '0') {
                //
                // Parent sections
                //
                $dbh = new PDO($dbClassifieds);
                $stmt = $dbh->prepare('SELECT idSection FROM sections WHERE idSection=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$idSectionPost]);
                $row = $stmt->fetch();
                $dbh = null;
                extract($row);
            } else {
                //
                // Subsections
                //
                $dbh = new PDO($dbClassifieds);
                $stmt = $dbh->prepare('SELECT idSubsection FROM subsections WHERE idSubsection=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$idSectionPost]);
                $row = $stmt->fetch();
                $dbh = null;
                $idSection = $row['idSubsection'];
            }
            syncRemotes();
        }
        //
        // Apply update
        //
        if (isset($sectionPost)) {
            if ($parentSectionPost === '0') {
                //
                // Parent sections
                //
                $dbh = new PDO($dbClassifieds);
                $stmt = $dbh->prepare('SELECT sortOrderSection FROM sections WHERE idSection=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$idSection]);
                $row = $stmt->fetch();
                if ($row
                    and !empty($row['sortOrderSection'])
                    and $sortOrderSectionPost > $row['sortOrderSection']
                ) {
                    $sortOrderSectionPost++;
                }
                $stmt = $dbh->prepare('UPDATE sections SET section=?, sortOrderSection=?, sortPriority=? WHERE idSection=?');
                $stmt->execute([$sectionPost, $sortOrderSectionPost, 1, $idSection]);
                $dbh = null;
            } else {
                //
                // Subsections
                //
                $dbh = new PDO($dbClassifieds);
                $stmt = $dbh->prepare('SELECT sortOrderSubsection FROM subsections WHERE idSubsection=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$idSection]);
                $row = $stmt->fetch();
                if ($row and !empty($row['sortOrderSubsection']) and $sortOrderSectionPost > $row['sortOrderSubsection']) {
                    $sortOrderSectionPost++;
                }
                $stmt = $dbh->prepare('UPDATE subsections SET subsection=?, parentId=?, sortOrderSubsection=?, sortPrioritySubSection=? WHERE idSubsection=?');
                $stmt->execute([$sectionPost, $parentSectionPost, $sortOrderSectionPost, 1, $idSection]);
                $dbh = null;
            }
            sortSections();
            syncRemotes();
        } else {
            $message = 'No section was input.';
        }
    }
    //
    // Button: Delete
    //
    if (isset($_POST['delete'])) {
        if (isset($sectionPost)) {
            if ($parentSectionPost === '0') {
                //
                // Parent sections
                //
                $dbh = new PDO($dbClassifieds);
                $stmt = $dbh->prepare('SELECT idSection FROM sections WHERE section=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$sectionPost]);
                $row = $stmt->fetch();
                $dbh = null;
                if ($row) {
                    extract($row);
                    $dbh = new PDO($dbClassifieds);
                    $stmt = $dbh->prepare('DELETE FROM sections WHERE idSection=?');
                    $stmt->execute([$idSection]);
                    $dbh = null;
                } else {
                    $message = 'The section name was not found.';
                }
            } else {
                //
                // Subsections
                //
                $dbh = new PDO($dbClassifieds);
                $stmt = $dbh->prepare('SELECT idSubsection FROM subsections WHERE subsection=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$sectionPost]);
                $row = $stmt->fetch();
                $dbh = null;
                if ($row) {
                    extract($row);
                    $dbh = new PDO($dbClassifieds);
                    $stmt = $dbh->prepare('DELETE FROM subsections WHERE idSubsection=?');
                    $stmt->execute([$idSubsection]);
                    $dbh = null;
                } else {
                    $message = 'The section name was not found.';
                }
            }
            sortSections();
            syncRemotes();
        } else {
            $message = 'No section name was input.';
        }
    }
} elseif (isset($_POST['addUpdate']) or isset($_POST['delete'])) {
    if (empty($_POST['adminPass'])) {
        $message = 'The admin password is required for all user maintenance.';
    } else {
        $message = 'The admin password is invalid.';
    }
}
//
// Button, edit
//
if (isset($_POST['edit'])) {
    if (isset($subsectionFlagPost) and $subsectionFlagPost === '1') {
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->prepare('SELECT idSubsection, subsection, parentId, sortOrderSubsection FROM subsections WHERE idSubsection=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idSectionPost]);
        $row = $stmt->fetch();
        $dbh = null;
        if ($row) {
            extract($row);
            $idSectionEdit = $idSubsection;
            $parentIdEdit = $parentId;
            $sectionEdit = $subsection;
            $sortOrderSectionEdit = $sortOrderSubsection;
        }
    } else {
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->prepare('SELECT idSection, section, sortOrderSection FROM sections WHERE idSection=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idSectionPost]);
        $row = $stmt->fetch();
        $dbh = null;
        if ($row) {
            extract($row);
            $idSectionEdit = $idSectionPost;
            $parentIdEdit = null;
            $sectionEdit = $section;
            $sortOrderSectionEdit = $sortOrderSection;
        }
    }
}
/**
 * Function to sync the changes to the remote sites
 *
 * @return Nothing
 */
function syncRemotes()
{
    global $dbClassifieds, $remotes;
    $sections = [];
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->query('SELECT idSection, section, sortOrderSection FROM sections ORDER BY idSection');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        $rowSections = $row;
        $sections[].= json_encode($row);
        $subsections = [];
        $stmt = $dbh->prepare('SELECT idSubsection, subsection, parentId, sortOrderSubsection FROM subsections WHERE parentId=? ORDER BY idSubsection');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$row['idSection']]);
        foreach ($stmt as $row) {
            $sections[].= json_encode($row);
        }
    }
    $dbh = null;
    foreach ($remotes as $remote) {
        extract($row);
        $request = [];
        $response = [];
        $request['task'] = 'classifiedsSyncSections';
        $request['sections'] = json_encode($sections);
        $response = soa($remote . 'z/', $request);
    }
}
/**
 * Function to sort the sections and subsections
 *
 * @return Nothing
 */
function sortSections()
{
    global $dbClassifieds, $parentSectionPost;
    //
    // Sort the main sections
    //
    $count = null;
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->query('SELECT idSection, sortOrderSection FROM sections ORDER BY sortOrderSection, sortPriority');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        if ($row) {
            extract($row);
            $count++;
            $stmt = $dbh->prepare('UPDATE sections SET sortOrderSection=?, sortPriority=? WHERE idSection=?');
            $stmt->execute([$count, 2, $idSection]);
        }
    }
    $dbh = null;
    //
    // Sort the subsections
    //
    if (isset($parentSectionPost) and $parentSectionPost !== '0') {
        $count = null;
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->prepare('SELECT idSubsection FROM subsections WHERE parentId=? ORDER BY sortOrderSubsection, sortPrioritySubSection');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$parentSectionPost]);
        foreach ($stmt as $row) {
            if ($row) {
                extract($row);
                $count++;
                $stmt = $dbh->prepare('UPDATE subsections SET sortOrderSubsection=?, sortPrioritySubSection=? WHERE idSubsection=?');
                $stmt->execute([$count, 2, $idSubsection]);
            }
        }
        $dbh = null;
    }
}
//
// HTML
//
require $includesPath . '/header1.inc';
echo "  <title>Classified sections</title>\n";
echo '  <script src="z/wait.js"></script>' . "\n";
require $includesPath . '/header2.inc';
?>

  <nav class="n">
    <h4 class="m"><a class="m" href="usersEditors.php">Editing users</a> <a class="m" href="usersSubscribers.php">Patron mgt users</a> <a class="m" href="usersAdvertising.php">Advertising users</a> <a class="m" href="usersClassified.php">Classified users</a> <a class="m" href="usersMenu.php">Menu users</a> <a class="m" href="settings.php">Settings</a> <a class="s" href="classifiedSections.php">Classifieds</a></h4>
  </nav>
<?php echoIfMessage($message); ?>

  <h1 id="waiting">Please wait.</h1>

  <div class="flex">
    <main>
      <h1>Classified section maintenance</h1>

      <form class="wait" action="<?php echo $uri; ?>classifiedSections.php" method="post">
        <p>The admin password is required for all classified section maintenance.</p>

        <p><label for="adminPass">Password</label><br />
        <input id="adminPass" name="adminPass" type="password" class="h" autofocus required /></p>

        <h1>Add, update and delete sections</h1>

        <p>Parent section level, section name and sort order are required for add and update. The section name only is required for delete. Section names must be unique.</p>

        <p>Parent section level (two parent levels)<br />
        <select name="parentSection">
          <option value="0">Top index</option>
    <?php
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->query('SELECT idSection, section, sortOrderSection FROM sections ORDER BY sortOrderSection');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        if ($row['idSection'] === $parentIdEdit) {
            $selected = ' selected';
        } else {
            $selected = null;
        }
        echo '          <option value="' . $row['idSection'] . '"' . $selected . '>- ' . $row['section'] . "</option>\n";
    }
    $dbh = null;
    ?>
        </select></p>

        <p><label for="section">Section name</label><br />
        <input id="section" name="section" type="text" class="h" required<?php echoIfValue($sectionEdit); ?> /><input name="idSection" type="hidden" <?php echoIfValue($idSectionEdit); ?> /></p>

        <p><label for="sortOrderSection">Sort order</label><br />
        <input id="sortOrderSection" name="sortOrderSection" type="number" class="h"<?php echoIfValue($sortOrderSectionEdit); ?> /></p>

        <p><input type="submit" value="Add / update" name="addUpdate" class="button" /> <input type="submit" value="Delete" name="delete" class="button" /><input type="hidden" name="existing"<?php echoIfValue($edit); ?> /></p>
      </form>
    </main>

    <aside>
      <h1>Classified sections</h1>

<?php
$dbh = new PDO($dbClassifieds);
$stmt = $dbh->query('SELECT idSection, section, sortOrderSection FROM sections ORDER BY sortOrderSection');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    echo '      <form class="wait" action="' . $uri . 'classifiedSections.php" method="post">' . "\n";
    echo '        <p>' . $row['section'] . ', sort order: ' . $row['sortOrderSection'] . "<br />\n";
    echo '        <input type="hidden" name="idSection" value="' . $row['idSection'] . '" /><input name="section" type="hidden" value="' . html($row['section']) . '" /><input name="sortOrderSection" type="hidden" value="' . html($row['sortOrderSection']) . '" /><input type="submit" value="Edit" name="edit" class="button" /></p>' . "\n";
    echo "      </form>\n\n";
    $stmt = $dbh->prepare('SELECT idSubsection, subsection, sortOrderSubsection FROM subsections WHERE parentId=? ORDER BY sortOrderSubsection');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$row['idSection']]);
    foreach ($stmt as $row) {
        echo '      <form class="wait" action="' . $uri . 'classifiedSections.php" method="post">' . "\n";
        echo '        <p> - ' . $row['subsection'] . ', sort order: ' . $row['sortOrderSubsection'] . "<br />\n";
        echo '        <input type="hidden" name="idSection" value="' . $row['idSubsection'] . '" /><input name="section" type="hidden" value="' . html($row['subsection']) . '" /><input name="subsectionFlag" type="hidden" value="1"><input name="sortOrderSection" type="hidden" value="' . html($row['sortOrderSubsection']) . '" /><input type="submit" value="Edit" name="edit" class="button" /></p>' . "\n";
        echo "      </form>\n\n";
    }
}
$dbh = null;
?>
    </aside>
  </div>
</body>
</html>
