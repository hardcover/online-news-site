<?php
/**
 * An admin page for configuring the system
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2021 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2023 03 13
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
session_start();
require 'z/system/configuration.php';
require $includesPath . '/authorization.php';
require $includesPath . '/common.php';
require $includesPath . '/crypt.php';
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
$adMaxAdvertsPost = inlinePost('adMaxAdverts');
$adMinParagraphsPost = inlinePost('adMinParagraphs');
$adminPassPost = inlinePost('adminPass');
$editPost = inlinePost('edit');
$emailClassifiedPost = inlinePost('emailClassified');
$idNamePost = inlinePost('idName');
$idRemotePost = inlinePost('idRemote');
$idSectionPost = inlinePost('idSection');
$infoFormsPost = securePost('infoForms');
$informationPost = securePost('information');
$newAdminPassOnePost = inlinePost('newAdminPassOne');
$newAdminPassTwoPost = inlinePost('newAdminPassTwo');
$newsDescriptionPost = inlinePost('newsDescription');
$newsNamePost = inlinePost('newsName');
$remotePost = inlinePost('remote');
$sectionPost = inlinePost('section');
$sortOrderSectionPost = inlinePost('sortOrderSection');
//
$hash = null;
$idRemote = null;
$idSection = null;
$message = '';
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
// Test admin password authentication
//
$dbh = new PDO($dbEditors);
$stmt = $dbh->prepare('SELECT pass FROM users WHERE user=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$_SESSION['username']]);
$row = $stmt->fetch();
$dbh = null;
if (password_verify($adminPassPost, $row['pass'])) {
    //
    // Button: Add / update newspaper name
    //
    if (isset($_POST['addUpdateName'])) {
        if (empty($newsNamePost)) {
            $message = 'A newspaper name is required.';
        } else {
            $dbh = new PDO($dbSettings);
            $stmt = $dbh->query('DELETE FROM names');
            $stmt = $dbh->prepare('INSERT INTO names (name, description) VALUES (?, ?)');
            $stmt->execute([$newsNamePost, $newsDescriptionPost]);
            $dbh = null;
            //
            // Update the remote databases
            //
            include $includesPath . '/syncSettings.php';
            //
            // Clear newspaper name variables for display
            //
            $newsDescriptionPost = null;
            $newsNamePost = null;
        }
    }
    //
    // Button: Delete newspaper name
    //
    if (isset($_POST['deleteName'])) {
        $dbh = new PDO($dbSettings);
        $stmt = $dbh->query('DELETE FROM names');
        $dbh = null;
        //
        // Update the remote databases
        //
        include $includesPath . '/syncSettings.php';
        //
        // Clear newspaper name variables for display
        //
        $newsDescriptionPost = null;
        $newsNamePost = null;
    }
    //
    // Button: Add / update newspaper section
    //
    if (isset($_POST['addUpdateSection'])) {
        if (empty($sectionPost)) {
            $message = 'A section name is required.';
        } elseif (empty($sortOrderSectionPost)) {
            $message = 'Section sort order is required.';
        } else {
            //
            // Determine insert or update
            //
            if (empty($_POST['existing'])) {
                $dbh = new PDO($dbSettings);
                $stmt = $dbh->prepare('INSERT INTO sections (idSection) VALUES (?)');
                $stmt->execute([null]);
                $idSection = $dbh->lastInsertId();
                $dbh = null;
            } else {
                $dbh = new PDO($dbSettings);
                $stmt = $dbh->prepare('SELECT idSection FROM sections WHERE idSection=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$idSectionPost]);
                $row = $stmt->fetch();
                $dbh = null;
                if (isset($row['idSection'])) {
                    extract($row);
                }
            }
            //
            // Update newspaper sections
            //
            if (isset($_POST['addUpdateSection']) and isset($_POST['sortOrderSection'])) {
                //
                // Establish the change in sort order, if any
                //
                $dbh = new PDO($dbSettings);
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
                //
                // Apply update
                //
                $stmt = $dbh->prepare('UPDATE sections SET section=?, sortOrderSection=?, sortPriority=? WHERE idSection=?');
                $stmt->execute([$sectionPost, $sortOrderSectionPost, 1, $idSection]);
                $dbh = null;
            }
        }
    }
    //
    // Button: Delete newspaper section
    //
    if (isset($_POST['deleteSection']) and isset($idSectionPost)) {
        $dbh = new PDO($dbSettings);
        $stmt = $dbh->prepare('DELETE FROM sections WHERE idSection=?');
        $stmt->execute([$idSectionPost]);
        $dbh = null;
    }
    //
    // Update newspaper section sort order
    //
    if (isset($_POST['addUpdateSection']) or isset($_POST['deleteSection'])) {
        $count = null;
        $dbh = new PDO($dbSettings);
        $stmt = $dbh->query('SELECT idSection, sortOrderSection FROM sections ORDER BY sortOrderSection, sortPriority');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            extract($row);
            $count++;
            $stmt = $dbh->prepare('UPDATE sections SET sortOrderSection=? WHERE idSection=?');
            $stmt->execute([$count, $idSection]);
        }
        $stmt = $dbh->prepare('UPDATE sections SET sortPriority=?');
        $stmt->execute([2]);
        $dbh = null;
        //
        // Update the remote databases
        //
        include $includesPath . '/syncSettings.php';
        //
        // Clear section variables for display
        //
        $sectionPost = null;
        $sortOrderSectionPost = null;
    }
    //
    // Button: Add / update registration information
    //
    if (isset($_POST['addUpdateRegistration'])) {
        if (empty($informationPost)) {
            $message = 'Registration information is required.';
        } else {
            $dbh = new PDO($dbSettings);
            $stmt = $dbh->query('DELETE FROM registration');
            $stmt = $dbh->prepare('INSERT INTO registration (information) VALUES (?)');
            $stmt->execute([$informationPost]);
            $dbh = null;
            //
            // Update the remote databases
            //
            include $includesPath . '/syncSettings.php';
            //
            // Clear registration information for display
            //
            $informationPost = null;
        }
    }
    //
    // Button: Add / update contact form information
    //
    if (isset($_POST['addUpdateContactForm'])) {
        if (empty($infoFormsPost)) {
            $message = 'Contact form information is required.';
        } else {
            $dbh = new PDO($dbSettings);
            $stmt = $dbh->query('DELETE FROM forms');
            $stmt = $dbh->prepare('INSERT INTO forms (infoForms) VALUES (?)');
            $stmt->execute([$infoFormsPost]);
            $dbh = null;
            //
            // Update the remote databases
            //
            include $includesPath . '/syncSettings.php';
            //
            // Clear contact form information for display
            //
            $infoFormsPost = null;
        }
    }
    //
    // Button: Add / Advertisements in article text
    //
    if (isset($_POST['addUpdateAdvertisements'])) {
        if (empty($adMinParagraphsPost) or empty($adMaxAdvertsPost)) {
            $message = 'Advertisement form information is required.';
        } else {
            $dbh = new PDO($dbSettings);
            $stmt = $dbh->query('DELETE FROM advertisements');
            $stmt = $dbh->prepare('INSERT INTO advertisements (adMinParagraphs, adMaxAdverts) VALUES (?, ?)');
            $stmt->execute([$adMinParagraphsPost, $adMaxAdvertsPost]);
            $dbh = null;
            //
            // Update the remote databases
            //
            include $includesPath . '/syncSettings.php';
            //
            // Clear contact form information for display
            //
            $infoFormsPost = null;
        }
    }
    //
    // Button: Add / update email alert for classifieds
    //
    if (isset($_POST['addUpdateEmailClassified'])) {
        if (empty($emailClassifiedPost)) {
            $message = 'Email is required.';
        } else {
            $dbh = new PDO($dbSettings);
            $stmt = $dbh->query('DELETE FROM alertClassified');
            $stmt = $dbh->prepare('INSERT INTO alertClassified (emailClassified) VALUES (?)');
            $stmt->execute([$emailClassifiedPost]);
            $dbh = null;
            //
            // Update the remote databases
            //
            include $includesPath . '/syncSettings.php';
            //
            // Clear email addreess for display
            //
            $emailClassifiedPost = null;
        }
    }
    //
    // Button: Delete email alert for classifieds
    //
    if (isset($_POST['deleteEmailClassified'])) {
        $dbh = new PDO($dbSettings);
        $stmt = $dbh->query('DELETE FROM alertClassified');
        $dbh = null;
        //
        // Update the remote databases
        //
        include $includesPath . '/syncSettings.php';
        //
        // Clear email addreess for display
        //
        $emailClassifiedPost = null;
    }
    //
    // Button: Add / update URI
    //
    if (isset($_POST['addUpdateURI'])) {
        //
        // Create ID for add, not if update
        //
        if (empty($_POST['existing'])) {
            $dbh = new PDO($dbRemote);
            $stmt = $dbh->prepare('INSERT INTO remotes (idRemote) VALUES (?)');
            $stmt->execute([null]);
            $idRemote = $dbh->lastInsertId();
            $dbh = null;
        } else {
            $dbh = new PDO($dbRemote);
            $stmt = $dbh->prepare('SELECT idRemote FROM remotes WHERE idRemote=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$idRemotePost]);
            $row = $stmt->fetch();
            $dbh = null;
            if (isset($row['idRemote'])) {
                extract($row);
            }
        }
        //
        // Apply update
        //
        if (isset($_POST['remote']) and isset($idRemote)) {
            $dbh = new PDO($dbRemote);
            $stmt = $dbh->prepare('UPDATE remotes SET remote=? WHERE idRemote=?');
            $stmt->execute([$remotePost, $idRemote]);
            $dbh = null;
            //
            // Clear URI variable for display
            //
            $remotePost = null;
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
            // Test the connection to the remote URI
            //
            $request = [];
            $response = [];
            $request['task'] = 'test';
            foreach ($remotes as $remote) {
                $response = soa($remote . 'z/', $request);
                if (empty($response['result'])) {
                    $response['result'] = null;
                }
                $message.= '  ' . $remote . "<br><br>\n";
                if ($response['result'] === strval('success')) {
                    $message.= "  Result: success<br>\n";
                    //
                    // Update the remote databases
                    //
                    include $includesPath . '/syncSettings.php';
                } else {
                    $message.= "  Result: failure<br>\n";
                }
            }
        } else {
            $message = 'No remote site was input.';
        }
    }
    //
    // Button: Delete URI
    //
    if (isset($_POST['deleteURI']) and isset($_POST['remote'])) {
        if (isset($_POST['remote'])) {
            $dbh = new PDO($dbRemote);
            $stmt = $dbh->prepare('SELECT idRemote FROM remotes WHERE remote=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$remotePost]);
            $row = $stmt->fetch();
            $dbh = null;
            if (isset($row['idRemote'])) {
                extract($row);
                $dbh = new PDO($dbRemote);
                $stmt = $dbh->prepare('DELETE FROM remotes WHERE idRemote=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$idRemote]);
                $dbh = null;
                //
                // Clear URI variable for display
                //
                $remotePost = null;
            } else {
                $message = 'The remote URI was not found.';
            }
        } else {
            $message = 'No remote URI was input.';
        }
        //
        $remotes = [];
        $dbh = new PDO($dbRemote);
        $stmt = $dbh->query('SELECT remote FROM remotes');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            $remotes[] = $row['remote'];
        }
        $dbh = null;
    }
    //
    // Button: Change admin password
    //
    if (isset($_POST['changeAdminPass']) and strval($_POST['changeAdminPass']) === strval('Change admin password')) {
        if ($newAdminPassOnePost !== $newAdminPassTwoPost) {
            $message = 'The passwords do not match.';
        } elseif (empty($newAdminPassOnePost) or empty($newAdminPassTwoPost)) {
            $message = 'Both password fields are required.';
        } else {
            $newPassword = password_hash($newAdminPassOnePost, PASSWORD_DEFAULT);
            $dbh = new PDO($dbEditors);
            $stmt = $dbh->prepare('UPDATE users SET pass=? WHERE user=?');
            $stmt->execute([$newPassword, 'admin']);
            $dbh = null;
            $message = 'The admin password was changed.';
        }
    }
    //
    // Test connections
    //
    if (isset($_POST['testConnections'])) {
        //
        $remotes = [];
        $dbh = new PDO($dbRemote);
        $stmt = $dbh->query('SELECT remote FROM remotes');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            $remotes[] = $row['remote'];
        }
        $dbh = null;
        $message.= "Test connection result:<br><br>\n";
        $request = [];
        $response = [];
        $request['task'] = 'test';
        foreach ($remotes as $remote) {
            $response = soa($remote . 'z/', $request);
            $message.= '  ' . $remote . "<br><br>\n";
            if ($response['result'] === strval('success')) {
                $message.= "  Result: success<br>\n";
            } else {
                $message.= "  Result: failure<br>\n";
            }
        }
    }
    //
    // Change password for remote sites
    //
    if (isset($_POST['changeRemotePass'])) {
        //
        $remotes = [];
        $dbh = new PDO($dbRemote);
        $stmt = $dbh->query('SELECT remote FROM remotes');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            $remotes[] = $row['remote'];
        }
        $dbh = null;
        $chars = '012345678901234567890123456789abcdefghijklmnopqrstuvwxyz';
        $newOnus = null;
        for ($i = 0; $i < 128; $i++) {
            $newOnus.= $chars[rand(0, 55)];
        }
        $gig = null;
        $notRandom = 'jntwzLW';
        for ($i = 0; $i < 22; $i++) {
            $position = mt_rand(0, strlen($notRandom) - 1);
            $character = $notRandom[$position];
            $gig.= $character;
        }
        $message.= "Change remote passwords result:<br><br>\n";
        $request = [];
        $response = [];
        $request['task'] = 'setCrypt';
        $request['hash'] = $newOnus;
        $request['newGig'] = $gig;
        foreach ($remotes as $remote) {
            $response = soa($remote . 'z/', $request);
            $message.= '  ' . $remote . "<br><br>\n";
            if ($response['result'] === strval('success')) {
                $passHash = password_hash($newOnus, PASSWORD_DEFAULT);
                $message.= "  Result: success<br>\n";
                $content = "<?php\n";
                $content.= '$onus = \'' . $newOnus . '\';' . "\n";
                $content.= '$hash = \'' . $passHash . '\';' . "\n";
                $content.= '$gig = \'' . $gig . '\';' . "\n";
                $content.= '?>' . "\n";
                file_put_contents($includesPath . '/crypt.php', $content);
            } else {
                $message.= "  Result: failure<br>\n";
            }
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
// Button: Edit
//
if (isset($editPost)) {
    if (isset($idNamePost)) {
        $dbh = new PDO($dbSettings);
        $stmt = $dbh->prepare('SELECT name, description FROM names WHERE idName=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idNamePost]);
        $row = $stmt->fetch();
        $dbh = null;
        if ($row) {
            extract($row);
            $newsDescriptionPost = $description;
            $newsNamePost = $name;
        }
    }
}
//
// HTML
//
require $includesPath . '/header1.inc';
echo "  <title>Settings maintenance</title>\n";
echo '  <script src="z/wait.js"></script>' . "\n";
require $includesPath . '/header2.inc';
?>

  <nav class="n">
    <h4 class="m"><a class="m" href="usersEditors.php">Editing users</a> <a class="m" href="usersSubscribers.php">Patron mgt users</a> <a class="m" href="usersAdvertising.php">Advertising users</a> <a class="m" href="usersClassified.php">Classified users</a> <a class="m" href="usersMenu.php">Menu users</a> <a class="s" href="settings.php">Settings</a> <a class="m" href="classifiedSections.php">Classifieds</a></h4>
  </nav>
<?php echoIfMessage($message); ?>

  <div class="flex">
    <main>
      <h1>Settings maintenance</h1>

      <form action="<?php echo $uri; ?>settings.php" method="post">
        <p>The admin password is required for all settings maintenance.</p>

        <p><label for="adminPass">Admin password</label><br>
        <input id="adminPass" name="adminPass" type="password" class="h" autofocus required></p>

        <h2>Newspaper name and description</h2>

        <p><label for="newsName">Name</label><br>
        <input id="newsName" name="newsName" class="h"<?php echoIfValue($newsNamePost); ?>></p>

        <p><label for="newsDescription">Description</label><br>
        <input id="newsDescription" name="newsDescription" class="h"<?php echoIfValue($newsDescriptionPost); ?>></p>

        <p><input type="submit" value="Add / update" name="addUpdateName" class="button"> <input type="submit" value="Delete" name="deleteName" class="button"><input type="hidden" name="existing"<?php echoIfValue($editPost); ?>></p>

        <h2>Newspaper sections</h2>

        <p><label for="section">Section name</label><br>
        <input id="section" name="section" class="h"<?php echoIfValue($sectionPost); ?>></p>

        <p><label for="sortOrderSection">Section sort order</label><br>
        <input id="sortOrderSection" name="sortOrderSection" class="h"<?php echoIfValue($sortOrderSectionPost); ?>></p>

        <p><input type="submit" value="Add / update" name="addUpdateSection" class="button"> <input type="submit" value="Delete" name="deleteSection" class="button"><input name="idSection" type="hidden"<?php echoIfValue($idSectionPost); ?>><input type="hidden" name="existing"<?php echoIfValue($editPost); ?>></p>

        <h2>Registration information</h2>

        <p><label for="information">Information (<a href="markdown.html" target="_blank">markdown syntax</a>)</label><br>
        <textarea id="information" name="information" class="h"><?php echoIfText($informationPost); ?></textarea></p>

        <p><input type="submit" value="Add / update" name="addUpdateRegistration" class="button"></p>

        <h2>Contact form information</h2>

        <p><label for="infoForms">Information (<a href="markdown.html" target="_blank">markdown syntax</a>)</label><br>
        <textarea id="infoForms" name="infoForms" class="h"><?php echoIfText($infoFormsPost); ?></textarea></p>

        <p><input type="submit" value="Add / update" name="addUpdateContactForm" class="button"></p>

        <h2>Email address for contact forms and alerts</h2>

        <p>Enter an email address to receive alerts when a classified ad requires review.</p>

        <p><label for="emailClassified">Email</label><br>
        <input id="emailClassified" name="emailClassified" type="email" class="h"<?php echoIfValue($emailClassifiedPost); ?>></p>

        <p><input type="submit" value="Add / update" name="addUpdateEmailClassified" class="button"> <input type="submit" value="Delete" name="deleteEmailClassified" class="button"><input name="idRemote" type="hidden" <?php echoIfValue($idRemotePost); ?>><input type="hidden" name="existing"<?php echoIfValue($editPost); ?>></p>

        <h2>Advertisements in article text</h2>

        <p><label for="adMaxAdverts">Maximum number of ads per article</label><br>
        <input id="adMaxAdverts" name="adMaxAdverts" class="h"<?php echoIfValue($adMaxAdvertsPost); ?>></p>

        <p><label for="adMinParagraphs">Minimum number of paragraphs between ads</label><br>
        <input id="adMinParagraphs" name="adMinParagraphs" class="h"<?php echoIfValue($adMinParagraphsPost); ?>></p>

        <p><input type="submit" value="Add / update" name="addUpdateAdvertisements" class="button"></p>

        <h2>Remote sites URIs</h2>

        <p>Enter the URIs of the remote sites with a trailing slash. For example: http://www.mysite.com/</p>

        <p><label for="remote">URI</label><br>
        <input id="remote" name="remote" type="url" class="h"<?php echoIfValue($remotePost); ?>></p>

        <p><input type="submit" value="Add / update" name="addUpdateURI" class="button"> <input type="submit" value="Delete" name="deleteURI" class="button"><input name="idRemote" type="hidden" <?php echoIfValue($idRemotePost); ?>><input type="hidden" name="existing"<?php echoIfValue($editPost); ?>></p>

        <h2>Test connections to remote sites</h2>

        <p><input type="submit" value="Test remote connections" name="testConnections" class="button"></p>

        <h2>Change the password for remote sites</h2>

        <p>As with the admin password, the password for remote sites must be changed while the system is being set up. The system will choose a set of random passwords (authentication requires more than one). There is no recommendation for changing the passwords after that. A password change failure will require manual intervention to correct. Because the passwords incorporate the date in order to change daily, they will not work around midnight when the clocks on the systems are a little out of sync.</p>

        <p><input type="submit" value="Change remote passwords" name="changeRemotePass" class="button"></p>

        <h2>Change the admin password</h2>

        <p>For security reasons, the admin password must be changed from the default during system set up.</p>

        <p><label for="newAdminPassOne">New password</label><br>
        <input id="newAdminPassOne" name="newAdminPassOne" type="password" class="h"></p>

        <p><label for="newAdminPassTwo">Verify new password</label><br>
        <input id="newAdminPassTwo" name="newAdminPassTwo" type="password" class="h"></p>

        <p><input type="submit" value="Change admin password" name="changeAdminPass" class="button"></p>
      </form>
    </main>

    <aside>
      <h2>Newspaper name and description</h2>

<?php
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT idName, name, description FROM names');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    echo '      <form action="' . $uri . 'settings.php" method="post">' . "\n";
    echo '        <p>' . $row['name'] . "<br>\n";
    echo '        ' . $row['description'] . "<br>\n";
    echo '        <input type="hidden" name="idName" value="' . $row['idName'] . '"><input type="submit" value="Edit" name="edit" class="button"></p>' . "\n";
    echo "      </form>\n\n";
}
?>
      <h2>Newspaper sections</h2>

<?php
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT idSection, section, sortOrderSection FROM sections ORDER BY sortOrderSection');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    echo '      <form action="' . $uri . 'settings.php" method="post">' . "\n";
    echo '        <p>' . $row['section'] . "<br>\n";
    echo '        <input type="hidden" name="idSection" value="' . $row['idSection'] . '"><input name="section" type="hidden" value="' . html($row['section']) . '"><input name="sortOrderSection" type="hidden" value="' . html($row['sortOrderSection']) . '"><input type="submit" value="Edit" name="edit" class="button"></p>' . "\n";
    echo "      </form>\n\n";
}
$dbh = null;
?>
      <h2>Registration information</h2>

<?php
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT idRegistration, information FROM registration');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$dbh = null;
echo '      <form action="' . $uri . 'settings.php" method="post">' . "\n";
echo '        <p>' . $row['information'] . "<br>\n";
echo '        <input type="hidden" name="idRegistration" value="' . $row['idRegistration'] . '"><input type="hidden" name="information" value="' . $row['information'] . '"><input type="submit" value="Edit" name="edit" class="button"></p>' . "\n";
echo "      </form>\n\n";
?>
      <h2>Contact form information</h2>

<?php
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT idForm, infoForms FROM forms');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$dbh = null;
echo '      <form action="' . $uri . 'settings.php" method="post">' . "\n";
echo '        <p>' . $row['infoForms'] . "<br>\n";
echo '        <input type="hidden" name="idForm" value="' . $row['idForm'] . '"><input type="hidden" name="infoForms" value="' . $row['infoForms'] . '"><input type="submit" value="Edit" name="edit" class="button"></p>' . "\n";
echo "      </form>\n\n";
?>
      <h2>Email address for contact forms and alerts</h2>

<?php
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT idClassified, emailClassified FROM alertClassified');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$dbh = null;
if ($row === false) {
    $row = [];
    $row['idClassified'] = '';
    $row['emailClassified'] = null;
}
echo '      <form action="' . $uri . 'settings.php" method="post">' . "\n";
echo '        <p>' . $row['emailClassified'] . "<br>\n";
echo '        <input type="hidden" name="idClassified" value="' . $row['idClassified'] . '"><input type="hidden" name="emailClassified" value="' . $row['emailClassified'] . '"><input type="submit" value="Edit" name="edit" class="button"></p>' . "\n";
echo "      </form>\n\n";
?>
      <h2>Advertisements in article text</h2>

<?php
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT adMinParagraphs, adMaxAdverts FROM advertisements');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$dbh = null;
if ($row === false) {
    $row = [];
    $row['adMinParagraphs'] = '';
    $row['adMaxAdverts'] = '';
}
echo '      <form action="' . $uri . 'settings.php" method="post">' . "\n";
echo '        <p>Maximum number of ads per article: ' . $row['adMaxAdverts'] . "<br>\n";
echo '        <p>Minimum number of paragraphs between ads: ' . $row['adMinParagraphs'] . "<br>\n";
echo '        <input type="hidden" name="adMaxAdverts" value="' . $row['adMaxAdverts'] . '"><input type="hidden" name="adMinParagraphs" value="' . $row['adMinParagraphs'] . '"><input type="submit" value="Edit" name="edit" class="button"></p>' . "\n";
echo "      </form>\n\n";
?>
      <h2>Remote URIs</h2>

<?php
$rowcount = 0;
$dbh = new PDO($dbRemote);
$stmt = $dbh->query('SELECT idRemote, remote FROM remotes ORDER BY remote');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    extract($row);
    $rowcount++;
    echo '      <form action="' . $uri . 'settings.php" method="post">' . "\n";
    echo '        <p class="bw">' . html($row['remote']) . "<br>\n";
    echo '        <input name="idRemote" type="hidden" value="' . html($row['idRemote']) . '"><input name="remote" type="hidden" value="' . html($row['remote']) . '"><input type="submit" value="Edit" name="edit" class="button"></span></p>' . "\n";
    echo "      </form>\n\n";
}
$dbh = null;
?>
    </aside>
  </div>
</body>
</html>
