<?php
/**
 * For the editing server to update the publishing servers
 *
 * PHP version 5
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2013-2015 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *.@license   http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version   GIT: 2015-07-21
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
//
// Check for configuration file, create it if not found
//
if (!file_exists('system/configuration.php')) {
    copy('system/configuration.inc', 'system/configuration.php');
}
require 'system/configuration.php';
$includesPath = ltrim($includesPath, 'z/');
require $includesPath . '/common.php';
require $includesPath . '/password_compat/password.php';
require $includesPath . '/createCrypt.php';
require $includesPath . '/crypt.php';
//
// Authorize
//
date_default_timezone_set('America/Los_Angeles');
if (!isset($_POST['gig'])
    or !isset($_POST['onus'])
    or !isset($_POST['task'])
    or (strval(base64_decode($_POST['gig'])) !== strval(date($gig)))
    or !password_verify(base64_decode($_POST['onus']), $hash)
) {
    if (substr(phpversion(), 0, 3) != '5.2') {
        header_remove();
    }
    if (substr(phpversion(), 0, 3) != '5.2' and substr(phpversion(), 0, 3) != '5.3') {
        http_response_code(404);
    } else {
        header(' ', true, 404);
    }
    exit;
}
//
// Create the databases on the first run
//
require $includesPath . '/createSubscriber1.php';
require $includesPath . '/createSubscriber2.php';
//
// Extract the post array, set variables
//
$response = array();
extract(array_map('base64_decode', array_map('secure', $_POST)));
if (isset($archive) and $archive == 'archive') {
    $db = $dbArchive;
    $db2 = $dbArchive2;
    $column = 'rowid';
} else {
    $db = $dbPublished;
    $db2 = $dbPublished2;
    $column = 'idArticle';
}
//
// Advertisements
//
if ($task == 'adDelete') {
    $dbh = new PDO($dbAdvertising);
    $stmt = $dbh->prepare('DELETE FROM advertisements WHERE idAd=?');
    $stmt->execute(array($idAd));
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'adInsert') {
    $dbh = new PDO($dbAdvertising);
    $stmt = $dbh->prepare('SELECT idAd FROM advertisements WHERE idAd=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idAd));
    $row = $stmt->fetch();
    if ($row) {
        $stmt = $dbh->prepare('DELETE from advertisements WHERE idAd=?');
        $stmt->execute(array($idAd));
    }
    $stmt = $dbh->prepare('INSERT INTO advertisements (idAd, startDateAd, endDateAd, sortOrderAd, link, linkAlt, image) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute(array($idAd, $startDateAd, $endDateAd, $sortOrderAd, $link, $linkAlt, $image));
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'adOrder') {
    $sortOrder = json_decode($sortOrder, true);
    $dbh = new PDO($dbAdvertising);
    $stmt = $dbh->query('DELETE FROM maxAd');
    if (isset($maxAds)) {
        $stmt = $dbh->prepare('INSERT INTO maxAd (maxAds) VALUES (?)');
        $stmt->execute(array($maxAds));
    }
    foreach ($sortOrder as $idAd => $key) {
        $stmt = $dbh->prepare('UPDATE advertisements SET sortOrderAd=? WHERE idAd=?');
        $stmt->execute($key);
    }
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'adSync') {
    $articles = array();
    $dbh = new PDO($dbAdvertising);
    $stmt = $dbh->query('SELECT idAd FROM advertisements');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        $ads[] = $row['idAd'];
    }
    $dbh = null;
    $response['remoteAds'] = json_encode($ads);
    $response['result'] = 'success';
}
//
// Archives
//
if ($task == 'archiveDelete') {
    $dbh = new PDO($dbArchive);
    $stmt = $dbh->prepare('DELETE FROM articles WHERE rowid=?');
    $stmt->execute(array($idArticle));
    $dbh = null;
    $dbh = new PDO($dbArchive2);
    $stmt = $dbh->prepare('DELETE FROM imageSecondary WHERE idArticle=?');
    $stmt->execute(array($idArticle));
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'archiveNull') {
    $dbh = new PDO($dbArchive);
    $stmt = $dbh->prepare('UPDATE articles SET publicationDate=?, endDate=?, idSection=?, sortOrderArticle=?, byline=?, headline=?, standfirst=?, text=?, summary=?, photoCredit=?, photoCaption=?, originalImageWidth=?, originalImageHeight=?, thumbnailImage=?, thumbnailImageWidth=?, thumbnailImageHeight=?, hdImage=?, hdImageWidth=?, hdImageHeight=? WHERE rowid=?');
    $stmt->execute(array(null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, $idArticle));
    $dbh = null;
    $dbh = new PDO($dbArchive2);
    $stmt = $dbh->prepare('DELETE FROM imageSecondary WHERE idArticle=?');
    $stmt->execute(array($idArticle));
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'archiveSearch') {
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->query('DELETE FROM archiveAccess');
    $stmt = $dbh->prepare('INSERT INTO archiveAccess (access) VALUES (?)');
    $stmt->execute(array($access));
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'archiveSync') {
    $articles = array();
    $dbh = new PDO($dbArchive);
    $stmt = $dbh->query('SELECT idArticle FROM articles WHERE endDate IS NULL');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        $articles[] = $row['idArticle'];
    }
    $dbh = null;
    $response['remoteArticles'] = json_encode($articles);
    $response['result'] = 'success';
}
if ($task == 'archiveSync2') {
    $photos = array();
    $dbh = new PDO($dbArchive2);
    $stmt = $dbh->prepare('SELECT count(*) FROM imageSecondary WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idArticle));
    $row = $stmt->fetch();
    $dbh = null;
    $response['remotePhotos'] = $row['count(*)'];
    $response['result'] = 'success';
}
//
// Articles
//
if ($task == 'publishedDeletePhoto') {
    $dbh = new PDO($dbPublished);
    $stmt = $dbh->prepare('UPDATE articles SET photoCredit=?, photoCaption=?, originalImageWidth=?, originalImageHeight=?, thumbnailImage=?, thumbnailImageWidth=?, thumbnailImageHeight=?, hdImage=?, hdImageWidth=?, hdImageHeight=? WHERE idArticle=?');
    $stmt->execute(array(null, null, null, null, null, null, null, null, null, null, $idArticle));
    $dbh = null;
    $dbh = new PDO($dbPublished2);
    $stmt = $dbh->prepare('DELETE FROM imageSecondary WHERE idArticle=?');
    $stmt->execute(array($idArticle));
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'publishedDelete') {
    $dbh = new PDO($dbPublished);
    $stmt = $dbh->prepare('DELETE FROM articles WHERE idArticle=?');
    $stmt->execute(array($idArticle));
    $dbh = null;
    $dbh = new PDO($dbPublished2);
    $stmt = $dbh->prepare('DELETE FROM imageSecondary WHERE idArticle=?');
    $stmt->execute(array($idArticle));
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'publishedOrder') {
    $sortOrder = json_decode($sortOrder, true);
    $dbh = new PDO($dbPublished);
    foreach ($sortOrder as $idArticle => $key) {
        $stmt = $dbh->prepare('UPDATE articles SET publicationDate=?, endDate=?, sortOrderArticle=? WHERE idArticle=?');
        $stmt->execute($key);
    }
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'publishedSync') {
    $articles = array();
    $dbh = new PDO($dbPublished);
    $stmt = $dbh->query('SELECT idArticle FROM articles');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        $articles[] = $row['idArticle'];
    }
    $dbh = null;
    $response['remoteArticles'] = json_encode($articles);
    $response['result'] = 'success';
}
if ($task == 'publishedSync2') {
    $photos = array();
    $dbh = new PDO($dbPublished2);
    $stmt = $dbh->prepare('SELECT count(*) FROM imageSecondary WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idArticle));
    $row = $stmt->fetch();
    $dbh = null;
    $response['remotePhotos'] = $row['count(*)'];
    $response['result'] = 'success';
}
if ($task == 'updateInsert1') {
    $dbh = new PDO($db);
    $stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idArticle));
    $row = $stmt->fetch();
    if ($row) {
        $stmt = $dbh->prepare('UPDATE articles SET publicationDate=?, endDate=?, idSection=?, sortOrderArticle=?, byline=?, headline=?, standfirst=?, text=?, summary=?, photoCredit=?, photoCaption=?, originalImageWidth=?, originalImageHeight=?, thumbnailImage=?, thumbnailImageWidth=?, thumbnailImageHeight=?, hdImage=?, hdImageWidth=?, hdImageHeight=? WHERE ' . $column . '=?');
        $stmt->execute(array(null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, $idArticle));
    } else {
        $stmt = $dbh->prepare('INSERT INTO articles (idArticle) VALUES (?)');
        $stmt->execute(array($idArticle));
    }
    $stmt = $dbh->prepare('UPDATE articles SET publicationDate=?, endDate=?, idSection=?, sortOrderArticle=?, byline=?, headline=?, standfirst=?, text=?, summary=?, photoCredit=?, photoCaption=? WHERE ' . $column . '=?');
    $stmt->execute(array($publicationDate, $endDate, $idSection, $sortOrderArticle, $byline, $headline, $standfirst, $text, $summary, $photoCredit, $photoCaption, $idArticle));
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'updateInsert2') {
    $dbh = new PDO($db);
    $stmt = $dbh->prepare('UPDATE articles SET thumbnailImage=?, thumbnailImageWidth=?, thumbnailImageHeight=?, hdImageWidth=?, hdImageHeight=? WHERE ' . $column . '=?');
    $stmt->execute(array($thumbnailImage, $thumbnailImageWidth, $thumbnailImageHeight, $hdImageWidth, $hdImageHeight, $idArticle));
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'updateInsert3') {
    $dbh = new PDO($db);
    $stmt = $dbh->prepare('UPDATE articles SET hdImage=? WHERE ' . $column . '=?');
    $stmt->execute(array($hdImage, $idArticle));
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'updateInsert4') {
    $dbh = new PDO($db2);
    $stmt = $dbh->prepare('INSERT INTO imageSecondary (idArticle, image, photoCredit, photoCaption, time) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute(array($idArticle, $image, $photoCredit, $photoCaption, time()));
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'downloadContributionIDs') {
    $IDs = null;
    $dbh = new PDO($dbEdit);
    $stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE sortOrderArticle < ?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array(time())); // sortOrderArticle here stores the submit time
    foreach ($stmt as $row) {
        $IDs[] = $row['idArticle'];
    }
    $dbh = null;
    if ($IDs != null) {
        $response['IDs'] = json_encode($IDs);
    }
    $response['result'] = 'success';
}
if ($task == 'downloadContribution1') {
    $dbh = new PDO($dbEdit);
    $stmt = $dbh->prepare('SELECT idArticle, idSection, byline, headline, standfirst, text, summary, photoCredit, photoCaption, thumbnailImageWidth FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idArticle));
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        $response = $row;
    }
    $response['result'] = 'success';
}
if ($task == 'downloadContribution2') {
    $dbh = new PDO($dbEdit);
    $stmt = $dbh->prepare('SELECT thumbnailImage, thumbnailImageWidth, thumbnailImageHeight, hdImageWidth, hdImageHeight FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idArticle));
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        $response = $row;
    }
    $response['result'] = 'success';
}
if ($task == 'downloadContribution3') {
    $dbh = new PDO($dbEdit);
    $stmt = $dbh->prepare('SELECT hdImage FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idArticle));
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        $response = $row;
    }
    $response['result'] = 'success';
}
if ($task == 'downloadContribution4a') {
    $idPhotos = array();
    $dbh = new PDO($dbEdit2);
    $stmt = $dbh->prepare('SELECT idPhoto FROM imageSecondary WHERE idArticle=? ORDER BY time');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idArticle));
    foreach ($stmt as $row) {
        $idPhotos[] = $row['idPhoto'];
    }
    $dbh = null;
    $response['idPhotos'] = json_encode($idPhotos);
    $response['result'] = 'success';
}
if ($task == 'downloadContribution4b') {
    $idPhotos = array();
    $dbh = new PDO($dbEdit2);
    $stmt = $dbh->prepare('SELECT image, photoCredit, photoCaption FROM imageSecondary WHERE idPhoto=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idPhoto));
    $row = $stmt->fetch();
    if ($row) {
        $response['hdImage'] = $row['image'];
        $response['photoCredit'] = $row['photoCredit'];
        $response['photoCaption'] = $row['photoCaption'];
    }
    $response['result'] = 'success';
}
if ($task == 'downloadContributionDelete') {
    $dbh = new PDO($dbEdit);
    $stmt = $dbh->prepare('DELETE FROM articles WHERE idArticle=?');
    $stmt->execute(array($idArticle));
    $dbh = null;
    $dbh = new PDO($dbEdit2);
    $stmt = $dbh->prepare('DELETE FROM imageSecondary WHERE idArticle=?');
    $stmt->execute(array($idArticle));
    $dbh = null;
    $response['result'] = 'success';
}
//
// Classifieds
//
if ($task == 'classifiedsDelete') {
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('DELETE FROM ads WHERE idAd=?');
    $stmt->execute(array($idAd));
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'classifiedsEarlyRemoval') {
    $classifieds = array();
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->query('SELECT idAd FROM ads WHERE duration IS NULL ORDER BY idAd');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        $classifieds[] = $row['idAd'];
    }
    $dbh = null;
    $response['remoteClassifieds'] = json_encode($classifieds);
    $response['result'] = 'success';
}
if ($task == 'classifiedsNewCleanUp') {
    $dbh = new PDO($dbClassifiedsNew);
    $stmt = $dbh->prepare('DELETE FROM ads WHERE idAd=?');
    $stmt->execute(array($idAd));
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'classifiedsNewDownload') {
    $dbh = new PDO($dbClassifiedsNew);
    $stmt = $dbh->prepare('SELECT email, title, description, categoryId FROM ads WHERE idAd=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idAd));
    $row = $stmt->fetch();
    if ($row) {
        $response = $row;
    }
    $photosOrdered = array(1, 2, 3, 4, 5, 6, 7);
    foreach ($photosOrdered as $photo) {
        $stmt = $dbh->prepare('SELECT photo' . $photo . ' FROM ads WHERE idAd=?');
        $stmt->setFetchMode(PDO::FETCH_NUM);
        $stmt->execute(array($idAd));
        $row = $stmt->fetch();
        if ($row['0'] == null) {
            $num[] = 0;
        } else {
            $num[] = 1;
        }
    }
    $dbh = null;
    $response['photos'] = json_encode($num);
    $response['result'] = 'success';
}
if ($task == 'classifiedsNewDownloadPhoto') {
    $dbh = new PDO($dbClassifiedsNew);
    $stmt = $dbh->prepare('SELECT photo' . $photo. ' FROM ads WHERE idAd=?');
    $stmt->setFetchMode(PDO::FETCH_NUM);
    $stmt->execute(array($idAd));
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        $response['photo'] = $row['0'];
    }
    $response['result'] = 'success';
}
if ($task == 'classifiedsSync') {
    $classifieds = array();
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->query('SELECT idAd FROM ads');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        $classifieds[] = $row['idAd'];
    }
    $dbh = null;
    $response['remoteClassifieds'] = json_encode($classifieds);
    $response['result'] = 'success';
}
if ($task == 'classifiedsSyncNew') {
    $fifteenMinutesAgo = time() - 900;
    $classifieds = array();
    $dbh = new PDO($dbClassifiedsNew);
    $stmt = $dbh->prepare('SELECT idAd FROM ads WHERE review < ?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($fifteenMinutesAgo));
    foreach ($stmt as $row) {
        $classifieds[] = $row['idAd'];
    }
    $dbh = null;
    $response['remoteClassifieds'] = json_encode($classifieds);
    $response['result'] = 'success';
}
if ($task == 'classifiedsSyncSections') {
    $sections = json_decode($sections, true);
    $dbh = new PDO($dbClassifieds);
    $dbh->beginTransaction();
    $stmt = $dbh->query('DELETE FROM sections');
    $stmt = $dbh->query('DELETE FROM subsections');
    foreach ($sections as $section) {
        $section = json_decode($section, true);
        $key = array_keys($section)['0'];
        extract($section);
        if ($key === 'idSection') {
            $stmt = $dbh->prepare('INSERT INTO sections (idSection, section, sortOrderSection) VALUES (?, ?, ?)');
            $stmt->execute(array($idSection, $section, $sortOrderSection));
        } else {
            $stmt = $dbh->prepare('INSERT INTO subsections (idSubsection, subsection, parentId, sortOrderSubsection) VALUES (?, ?, ?, ?)');
            $stmt->execute(array($idSubsection, $subsection, $parentId, $sortOrderSubsection));
        }
    }
    $dbh->commit();
    $dbh = null;
}
if ($task == 'classifiedsUpdateInsert1') {
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('SELECT idAd FROM ads WHERE idAd=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idAd));
    $row = $stmt->fetch();
    if ($row) {
        $stmt = $dbh->prepare('DELETE FROM ads WHERE idAd=?');
        $stmt->execute(array($idAd));
    }
    $stmt = $dbh->prepare('INSERT INTO ads (idAd, email, title, description, categoryId, review, startDate, duration, photos) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute(array($idAd, $email, $title, $description, $categoryId, $review, $startDate, $duration, $photos));
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'classifiedsUpdateInsert2') {
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('UPDATE ads SET photo' . $photoNumber . '=? WHERE idAd=?');
    $stmt->execute(array($photo, $idAd));
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'classifiedsUpload') {
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('INSERT INTO users (idUser, email, pass, payStatus) VALUES (?, ?, ?, ?)');
    $stmt->execute(array($idUser, $email, $pass, $payStatus));
    $dbh = null;
    $dbh = new PDO($dbClassifiedsNew);
    $stmt = $dbh->prepare('DELETE FROM users WHERE email=?');
    $stmt->execute(array($email));
    $dbh = null;
    $response['result'] = 'success';
}
//
// Menu
//
if ($task == 'menuDelete') {
    $dbh = new PDO($dbMenu);
    $stmt = $dbh->prepare('DELETE FROM menu WHERE idMenu=?');
    $stmt->execute(array($idMenu));
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'menuInsert') {
    $dbh = new PDO($dbMenu);
    $stmt = $dbh->prepare('SELECT idMenu FROM menu WHERE idMenu=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idMenu));
    $row = $stmt->fetch();
    if ($row) {
        $stmt = $dbh->prepare('DELETE FROM menu WHERE idMenu');
        $stmt->execute(array($idMenu));
    }
    $stmt = $dbh->prepare('INSERT INTO menu (idMenu, menuName, menuSortOrder, menuPath, menuContent, menuAuthorization) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute(array($idMenu, $menuName, $menuSortOrder, $menuPath, $menuContent, $menuAuthorization));
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'menuOrder') {
    $sortOrder = json_decode($sortOrder, true);
    $dbh = new PDO($dbMenu);
    foreach ($sortOrder as $row) {
        $stmt = $dbh->prepare('UPDATE menu SET menuSortOrder=? WHERE idMenu=?');
        $stmt->execute(array($row['1'], $row['0']));
    }
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'menuSync') {
    $menu = array();
    $dbh = new PDO($dbMenu);
    $stmt = $dbh->query('SELECT idMenu FROM menu');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        $menu[] = $row['idMenu'];
    }
    $dbh = null;
    $response['remoteMenu'] = json_encode($menu);
    $response['result'] = 'success';
}
//
// Settings
//
if ($task == 'settingsUpdate') {
    $archiveAccess = isset($archiveAccess) ? json_decode($archiveAccess, true) : null;
    $name = isset($name) ? json_decode($name, true) : null;
    $sortOrder = isset($sortOrder) ? json_decode($sortOrder, true) : null;
    //
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->query('DELETE from archiveAccess');
    if (is_array($archiveAccess)) {
        $stmt = $dbh->prepare('INSERT INTO archiveAccess (idAccess, access) VALUES (?, ?)');
        $stmt->execute($archiveAccess);
    }
    //
    $stmt = $dbh->query('DELETE from names');
    if (is_array($name)) {
        $stmt = $dbh->prepare('INSERT INTO names (idName, name, description) VALUES (?, ?, ?)');
        $stmt->execute($name);
    }
    //
    $stmt = $dbh->query('DELETE from sections');
    if (is_array($sortOrder)) {
        $dbh->beginTransaction();
        foreach ($sortOrder as $idArticle => $key) {
            $stmt = $dbh->prepare('INSERT INTO sections (idSection, section, sortOrderSection) VALUES (?, ?, ?)');
            $stmt->execute($key);
        }
        $dbh->commit();
        $response['result'] = 'success';
    }
    $dbh = null;
}
if ($task == 'setCrypt') {
    $content = "<?php\n";
    $content.= '$hash = \'' . password_hash($hash, PASSWORD_DEFAULT) . '\';' . "\n";
    $content.= '$gig = \'' . $newGig . '\';' . "\n";
    $content.= '?>' . "\n";
    file_put_contents($includesPath . '/crypt.php', $content);
    $response['result'] = 'success';
}
if ($task == 'dbTest') {
    if (file_exists('dbTest.sqlite')) {
        unlink('dbTest.sqlite');
    }
    $dbh = new PDO('sqlite:dbTest.sqlite');
    $stmt = $dbh->query('CREATE TABLE "count" ("id" INTEGER PRIMARY KEY, "time" INTEGER);');
    $dbh = null;
    $startTime = microtime(true);
    $endTime = 1 + $startTime;
    while (microtime(true) < $endTime) {
        $dbh = new PDO('sqlite:dbTest.sqlite');
        $stmt = $dbh->prepare('INSERT INTO count (time) VALUES (?)');
        $stmt->execute(array(microtime(true)));
        $dbh = null;
    }
    $elapsedTime = microtime(true) - $startTime;
    $dbh = new PDO('sqlite:dbTest.sqlite');
    $stmt = $dbh->query('SELECT count(*) FROM count');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $row = $stmt->fetch();
    $dbh = null;
    $dbh = new PDO('sqlite::memory:');
    $stmt = $dbh->query('CREATE TABLE "a" ("b")');
    $dbh = null;
    unlink('dbTest.sqlite');
    $response['tps'] = number_format(intval($row['count(*)'] / $elapsedTime));
    $response['result'] = 'success';
}
//
// Sitemap.xml
//
if ($task == 'sitemap') {
    /**
     * Function to write a <url><loc> line </loc></url> to the sitemap.xml file
     *
     * @param string $uri  The site URI
     * @param array  $page The article ID and headline
     *
     * @return Nothing
     */
    function urlLoc($uri, $page)
    {
        global $fp;
        $h = preg_replace('/\%/', ' percentage', $page['1']);
        $h = preg_replace('/\@/', ' at ', $h);
        $h = preg_replace('/\&/', ' and ', $h);
        $h = preg_replace('/\s[\s]+/', '-', $h);
        $h = preg_replace('/[\s\W]+/', '-', $h);
        $h = preg_replace('/^[\-]+/', '', $h);
        $h = preg_replace('/[\-]+$/', '', $h);
        $headline = strtolower($h);
        fwrite($fp, utf8_encode('  <url><loc>' . $uri . '?a=' . $page['0'] . '+' . $headline . '</loc></url>' . "\n"));
    }
    //
    // Variables
    //
    $uri = $uriScheme . '://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
    $uri = str_replace('z/', '', $uri);
    array_map("unlink", glob('../*xml*'));
    //
    // Determine the number of needed sitemap files
    //
    $dbh = new PDO($dbArchive);
    $stmt = $dbh->query('SELECT count(*) FROM articles');
    $stmt->setFetchMode(PDO::FETCH_NUM);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row['0'] > 49996) {
        //
        // Write when there are multiple sitemaps
        //
        $numOfSiteMaps = intval(($row['0'] + 25000) / 49996);
        $fp = fopen('../sitemap_index.xml', 'w');
        fwrite($fp, pack("CCC", 0xef, 0xbb, 0xbf));
        fwrite($fp, utf8_encode('<?xml version="1.0" encoding="UTF-8"?>' . "\n"));
        fwrite($fp, utf8_encode('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"));
        $mapCount = null;
        while ($mapCount < $numOfSiteMaps) {
            $mapCount++;
            fwrite($fp, utf8_encode('  <sitemap><loc>' . $uri . 'sitemap' . sprintf('%02d', $mapCount) . '.xml</loc><lastmod>' . $today . '</lastmod></sitemap>' . "\n"));
        }
        fwrite($fp, utf8_encode('</sitemapindex>' . "\n"));
        fclose($fp);
        $fp = null;
        $mapCount = 1;
        $lineCount = 2;
        $fp = fopen('../sitemap' . sprintf('%02d', $mapCount) . '.xml', 'w');
        fwrite($fp, utf8_encode('<?xml version="1.0" encoding="UTF-8"?>' . "\n"));
        fwrite($fp, utf8_encode('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"));
        fwrite($fp, utf8_encode('  <url><loc>' . $uri . '</loc><priority>1.0</priority></url>' . "\n"));
        $dbh = new PDO($dbPublished);
        $stmt = $dbh->query('SELECT idArticle, headline FROM articles ORDER BY idArticle DESC');
        $stmt->setFetchMode(PDO::FETCH_NUM);
        foreach ($stmt as $row) {
            $lineCount++;
            urlLoc($uri, $row);
        }
        $dbh = null;
        $dbh = new PDO($dbArchive);
        $stmt = $dbh->query('SELECT idArticle, headline FROM articles WHERE headline IS NOT NULL ORDER BY rowid DESC');
        $stmt->setFetchMode(PDO::FETCH_NUM);
        foreach ($stmt as $row) {
            $lineCount++;
            urlLoc($uri, $row);
            if (is_int($lineCount / 49996)) {
                $mapCount++;
                fwrite($fp, utf8_encode('</urlset>' . "\n"));
                fclose($fp);
                $fp = null;
                $fp = fopen('../sitemap' . sprintf('%02d', $mapCount) . '.xml', 'w');
                fwrite($fp, utf8_encode('<?xml version="1.0" encoding="UTF-8"?>' . "\n"));
                fwrite($fp, utf8_encode('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"));
            }
        }
        $dbh = null;
        fwrite($fp, utf8_encode('</urlset>' . "\n"));
        fclose($fp);
        $fp = null;
    } else {
        //
        // Write when there is only one sitemap
        //
        echo '<pre>';
        $fp = fopen('../sitemap.xml', 'w');
        fwrite($fp, utf8_encode('<?xml version="1.0" encoding="UTF-8"?>' . "\n"));
        fwrite($fp, utf8_encode('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"));
        fwrite($fp, utf8_encode('  <url><loc>' . $uri . '</loc></url>' . "\n"));
        $dbh = new PDO($dbPublished);
        $stmt = $dbh->query('SELECT idArticle, headline FROM articles ORDER BY idArticle DESC');
        $stmt->setFetchMode(PDO::FETCH_NUM);
        foreach ($stmt as $row) {
            urlLoc($uri, $row);
        }
        $dbh = null;
        $dbh = new PDO($dbArchive);
        $stmt = $dbh->query('SELECT idArticle, headline FROM articles WHERE headline IS NOT NULL ORDER BY rowid DESC');
        $stmt->setFetchMode(PDO::FETCH_NUM);
        foreach ($stmt as $row) {
            urlLoc($uri, $row);
        }
        $dbh = null;
        fwrite($fp, utf8_encode('</urlset>' . "\n"));
        fclose($fp);
        $fp = null;
    }
    $response['result'] = 'success';
}
//
// Subscribers
//
if ($task == 'subscriberDelete') {
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->prepare('DELETE FROM users WHERE idUser=?');
    $stmt->execute(array($idUser));
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'subscribersNewCleanUp') {
    $dbh = new PDO($dbSubscribersNew);
    $stmt = $dbh->prepare('DELETE FROM users WHERE soa=?');
    $stmt->execute(array(1));
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'subscribersDownload') {
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->prepare('SELECT email, ipAddress, verified, pass, payStatus, note, contributor, classifiedOnly, deliver, deliveryAddress, dCityRegionPostal, billingAddress, bCityRegionPostal, evolve, expand, extend FROM users WHERE idUser = ?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idUser));
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        $response = $row;
    }
    $response['result'] = 'success';
}
if ($task == 'subscribersNewDownload') {
    $dbh = new PDO($dbSubscribersNew);
    $stmt = $dbh->prepare('SELECT idUser FROM users WHERE verified = ?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array(1));
    $row = $stmt->fetch();
    $dbRows = array();
    if ($row) {
        $stmt = $dbh->prepare('UPDATE users SET soa=?');
        $stmt->execute(array(1));
        $stmt = $dbh->prepare('SELECT  email, ipAddress, verified, pass, payStatus, note, contributor, classifiedOnly, deliver, deliveryAddress, dCityRegionPostal, billingAddress, bCityRegionPostal, evolve, expand, extend FROM users WHERE soa=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(array(1));
        foreach ($stmt as $row) {
            extract($row);
            $dbRows[] = array(
                'email' => $email,
                'ipAddress' => $ipAddress,
                'verified' => $verified,
                'pass' => $pass,
                'payStatus' => $payStatus,
                'note' => $note,
                'contributor' => $contributor,
                'classifiedOnly' => $classifiedOnly,
                'deliver' => $deliver,
                'deliveryAddress' => $deliveryAddress,
                'dCityRegionPostal' => $dCityRegionPostal,
                'billingAddress' => $billingAddress,
                'bCityRegionPostal' => $bCityRegionPostal,
                'evolve' => $evolve,
                'expand' => $expand,
                'extend' => $extend
            );
        }
    }
    $dbh = null;
    $response['dbRows'] = json_encode($dbRows);
    $response['result'] = 'success';
}
if ($task == 'subscribersSoaUnflag') {
    $subscribers = array();
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->query('SELECT idUser FROM users WHERE idUser=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idUser));
    $row = $stmt->fetch();
    print_r($row);
    if ($row) {
        $stmt = $dbh->prepare('UPDATE users SET soa=? WHERE idUser=?');
        $stmt->execute(array(null, $idUser));
    }
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'subscribersSync') {
    $subscribers = array();
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->query('SELECT idUser FROM users');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        $subscribers[] = $row['idUser'];
    }
    $dbh = null;
    $response['remoteSubscribers'] = json_encode($subscribers);
    $response['result'] = 'success';
}
if ($task == 'subscribersSyncSoaFlagged') {
    $subscribers = array();
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->query('SELECT idUser FROM users WHERE soa IS NOT NULL');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        $subscribers[] = $row['idUser'];
    }
    $dbh = null;
    $response['remoteSubscribers'] = json_encode($subscribers);
    $response['result'] = 'success';
}
if ($task == 'subscribersUpdate') {
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->prepare('SELECT idUser FROM users WHERE idUser=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idUser));
    $row = $stmt->fetch();
    if ($row) {
        $stmt = $dbh->prepare('UPDATE users SET email=?, ipAddress=?, verified=?, pass=?, payStatus=?, note=?, contributor=?, classifiedOnly=?, deliver=?, deliveryAddress=?, dCityRegionPostal=?, billingAddress=?, bCityRegionPostal=?, evolve=?, expand=?, extend=? WHERE idUser=?');
        $stmt->execute(array($email, $ipAddress, $verified, $pass, $payStatus, $note, $contributor, $classifiedOnly, $deliver, $deliveryAddress, $dCityRegionPostal, $billingAddress, $bCityRegionPostal, $evolve, $expand, $extend, $idUser));
    }
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'subscribersUpload') {
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->prepare('INSERT INTO users (idUser, email, pass, payStatus) VALUES (?, ?, ?, ?)');
    $stmt->execute(array($idUser, $email, $pass, $payStatus));
    $dbh = null;
    $dbh = new PDO($dbSubscribersNew);
    $stmt = $dbh->prepare('DELETE FROM users WHERE email=?');
    $stmt->execute(array($email));
    $dbh = null;
    $response['result'] = 'success';
}
//
// Test
//
if ($task == 'test') {
    $response['result'] = 'success';
}
//
// Check for error logs
//
if (($task == 'adDelete'
    or $task == 'adInsert'
    or $task == 'adOrder'
    or $task == 'adSync'
    or $task == 'archiveDelete'
    or $task == 'archiveNull'
    or $task == 'archiveSearch'
    or $task == 'archiveSync'
    or $task == 'archiveSync2'
    or $task == 'classifiedsDelete'
    or $task == 'classifiedsEarlyRemoval'
    or $task == 'classifiedsNewCleanUp'
    or $task == 'classifiedsNewDownload'
    or $task == 'classifiedsNewDownloadPhoto'
    or $task == 'classifiedsSync'
    or $task == 'classifiedsSyncNew'
    or $task == 'classifiedsSyncSections'
    or $task == 'classifiedsUpdateInsert1'
    or $task == 'classifiedsUpdateInsert2'
    or $task == 'classifiedsUpload'
    or $task == 'dbTest'
    or $task == 'downloadContribution1'
    or $task == 'downloadContribution2'
    or $task == 'downloadContribution3'
    or $task == 'downloadContribution4a'
    or $task == 'downloadContribution4b'
    or $task == 'downloadContributionDelete'
    or $task == 'downloadContributionIDs'
    or $task == 'menuDelete'
    or $task == 'menuInsert'
    or $task == 'menuOrder'
    or $task == 'menuSync'
    or $task == 'publishedDelete'
    or $task == 'publishedDeletePhoto'
    or $task == 'publishedOrder'
    or $task == 'publishedSync'
    or $task == 'publishedSync2'
    or $task == 'setCrypt'
    or $task == 'settingsUpdate'
    or $task == 'sitemap'
    or $task == 'subscriberDelete'
    or $task == 'subscribersDownload'
    or $task == 'subscribersNewCleanUp'
    or $task == 'subscribersNewDownload'
    or $task == 'subscribersSoaUnflag'
    or $task == 'subscribersSync'
    or $task == 'subscribersSyncSoaFlagged'
    or $task == 'subscribersUpdate'
    or $task == 'subscribersUpload'
    or $task == 'test'
    or $task == 'updateInsert1'
    or $task == 'updateInsert2'
    or $task == 'updateInsert3')
    or $task == 'updateInsert4'
    and isset($response['result'])
) {
    $errorLog = null;
    if (file_exists('error_log')) {
        $errorLog.= file_get_contents('error_log');
        unlink('error_log');
    }
    if (file_exists('../error_log')) {
        $errorLog.= "\n" . file_get_contents('../error_log');
        unlink('../error_log');
    }
    if ($errorLog != null) {
        $response['errorLog'] = $errorLog;
    }
    //
    // Respond
    //
    echo json_encode(array_map('base64_encode', $response));
}
?>
