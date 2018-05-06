<?php
/**
 * For the editing server to update the publishing servers
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2018 05 06
 * @link      https://hardcoverwebdesign.com/
 * @link      https://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
//
// Check for configuration file, create it if not found
//
if (!file_exists('system/configuration.php')) {
    copy('system/configuration.inc', 'system/configuration.php');
}
require 'system/configuration.php';
if ($includesPath == 'z/includes') {
    $includesPath = ltrim($includesPath, 'z/');
} else {
    $includesPath = '../' . $includesPath;
}
require $includesPath . '/common.php';
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
    or (!password_verify(base64_decode($_POST['onus']), $hash))
) {
    header_remove();
    if (substr(phpversion(), 0, 3) < '5.4') {
        header(' ', true, 404);
    } else {
        http_response_code(404);
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
$response = [];
extract(array_map('base64_decode', array_map('secure', $_POST)));
if (isset($archive) and $archive == 'archive') {
    //
    // When the archive database is the destination
    //
    $db = $dbArchive;
    $db2 = $dbArchive2;
    $column = 'rowid';
} else {
    //
    // When the published database is the destination
    //
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
    $stmt->execute([$idAd]);
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'adInsert') {
    $dbh = new PDO($dbAdvertising);
    $stmt = $dbh->prepare('SELECT idAd FROM advertisements WHERE idAd=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idAd]);
    $row = $stmt->fetch();
    if ($row) {
        $stmt = $dbh->prepare('DELETE from advertisements WHERE idAd=?');
        $stmt->execute([$idAd]);
    }
    $stmt = $dbh->prepare('INSERT INTO advertisements (idAd, startDateAd, endDateAd, sortOrderAd, link, linkAlt, image) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$idAd, $startDateAd, $endDateAd, $sortOrderAd, $link, $linkAlt, $image]);
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'adOrder') {
    $sortOrder = json_decode($sortOrder, true);
    $dbh = new PDO($dbAdvertising);
    $stmt = $dbh->query('DELETE FROM maxAd');
    if (isset($maxAds)) {
        $stmt = $dbh->prepare('INSERT INTO maxAd (maxAds) VALUES (?)');
        $stmt->execute([$maxAds]);
    }
    foreach ($sortOrder as $idAd => $key) {
        $stmt = $dbh->prepare('UPDATE advertisements SET sortOrderAd=? WHERE idAd=?');
        $stmt->execute($key);
    }
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'adSync') {
    $articles = [];
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
    $stmt->execute([$idArticle]);
    $dbh = null;
    $dbh = new PDO($dbArchive2);
    $stmt = $dbh->prepare('DELETE FROM imageSecondary WHERE idArticle=?');
    $stmt->execute([$idArticle]);
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'archiveSearch') {
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->query('DELETE FROM archiveAccess');
    $stmt = $dbh->prepare('INSERT INTO archiveAccess (access) VALUES (?)');
    $stmt->execute([$access]);
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'archiveSync') {
    $articles = [];
    $dbh = new PDO($dbArchive);
    $stmt = $dbh->query('SELECT idArticle FROM articles');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        $articles[] = $row['idArticle'];
    }
    $dbh = null;
    $response['remoteArticles'] = json_encode($articles);
    $response['result'] = 'success';
}
if ($task == 'archiveSync2') {
    $photos = [];
    $dbh = new PDO($dbArchive2);
    $stmt = $dbh->prepare('SELECT count(*) FROM imageSecondary WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idArticle]);
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
    $stmt = $dbh->prepare('UPDATE articles SET photoName=?, photoCredit=?, photoCaption=?, originalImageWidth=?, originalImageHeight=?, thumbnailImage=?, thumbnailImageWidth=?, thumbnailImageHeight=?, hdImage=?, hdImageWidth=?, hdImageHeight=? WHERE idArticle=?');
    $stmt->execute([null, null, null, null, null, null, null, null, null, null, null, $idArticle]);
    $dbh = null;
    $dbh = new PDO($dbPublished2);
    $stmt = $dbh->prepare('DELETE FROM imageSecondary WHERE idArticle=?');
    $stmt->execute([$idArticle]);
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'publishedDelete') {
    $dbh = new PDO($dbPublished);
    $stmt = $dbh->prepare('DELETE FROM articles WHERE idArticle=?');
    $stmt->execute([$idArticle]);
    $dbh = null;
    $dbh = new PDO($dbPublished2);
    $stmt = $dbh->prepare('DELETE FROM imageSecondary WHERE idArticle=?');
    $stmt->execute([$idArticle]);
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
    $articles = [];
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
    $photos = [];
    $dbh = new PDO($dbPublished2);
    $stmt = $dbh->prepare('SELECT count(*) FROM imageSecondary WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idArticle]);
    $row = $stmt->fetch();
    $dbh = null;
    $response['remotePhotos'] = $row['count(*)'];
    $response['result'] = 'success';
}
if ($task == 'updateInsert1') {
    $dbh = new PDO($db);
    $stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idArticle]);
    $row = $stmt->fetch();
    if ($row) {
        $stmt = $dbh->prepare('UPDATE articles SET publicationDate=?, publicationTime=?, endDate=?, survey=?, genre=?, keywords=?, idSection=?, sortOrderArticle=?, byline=?, headline=?, standfirst=?, text=?, summary=?, evolve=?, expand=?, extend=?, photoName=?, photoCredit=?, photoCaption=?, originalImageWidth=?, originalImageHeight=?, thumbnailImage=?, thumbnailImageWidth=?, thumbnailImageHeight=?, hdImage=?, hdImageWidth=?, hdImageHeight=? WHERE ' . $column . '=?');
        $stmt->execute([null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, $idArticle]);
    } else {
        if ($column == 'idArticle') {
            $stmt = $dbh->prepare('INSERT INTO articles (idArticle) VALUES (?)');
            $stmt->execute([$idArticle]);
        } else {
            $stmt = $dbh->prepare('INSERT INTO articles (rowid, idArticle) VALUES (?, ?)');
            $stmt->execute([$idArticle, $idArticle]);
        }
    }
    $stmt = $dbh->prepare('UPDATE articles SET publicationDate=?, publicationTime=?, endDate=?, survey=?, genre=?, keywords=?, idSection=?, sortOrderArticle=?, byline=?, headline=?, standfirst=?, text=?, summary=?, evolve=?, expand=?, extend=?, photoName=?, photoCredit=?, photoCaption=? WHERE ' . $column . '=?');
    $stmt->execute([$publicationDate, $publicationTime, $endDate, $survey, $genre, $keywords, $idSection, $sortOrderArticle, $byline, $headline, $standfirst, $text, $summary, $evolve, $expand, $extend, $photoName, $photoCredit, $photoCaption, $idArticle]);
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'updateInsert2') {
    $dbh = new PDO($db);
    $stmt = $dbh->prepare('UPDATE articles SET thumbnailImage=?, thumbnailImageWidth=?, thumbnailImageHeight=?, hdImageWidth=?, hdImageHeight=? WHERE ' . $column . '=?');
    $stmt->execute([$thumbnailImage, $thumbnailImageWidth, $thumbnailImageHeight, $hdImageWidth, $hdImageHeight, $idArticle]);
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'updateInsert3') {
    $dbh = new PDO($db);
    $stmt = $dbh->prepare('UPDATE articles SET hdImage=? WHERE ' . $column . '=?');
    $stmt->execute([$hdImage, $idArticle]);
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'updateInsert4') {
    $dbh = new PDO($db2);
    $stmt = $dbh->prepare('INSERT INTO imageSecondary (idArticle, image, photoName, photoCredit, photoCaption, time) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$idArticle, $image, $photoName, $photoCredit, $photoCaption, time()]);
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'downloadContributionIDs') {
    $IDs = null;
    $dbh = new PDO($dbEdit);
    $stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE sortOrderArticle < ?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([time()]); // sortOrderArticle here stores the submit time
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
    $stmt = $dbh->prepare('SELECT idArticle, idSection, byline, headline, standfirst, text, summary, evolve, expand, extend, photoName, photoCredit, photoCaption, thumbnailImageWidth FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idArticle]);
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
    $stmt->execute([$idArticle]);
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
    $stmt->execute([$idArticle]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        $response = $row;
    }
    $response['result'] = 'success';
}
if ($task == 'downloadContribution4a') {
    $idPhotos = [];
    $dbh = new PDO($dbEdit2);
    $stmt = $dbh->prepare('SELECT idPhoto FROM imageSecondary WHERE idArticle=? ORDER BY time');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idArticle]);
    foreach ($stmt as $row) {
        $idPhotos[] = $row['idPhoto'];
    }
    $dbh = null;
    $response['idPhotos'] = json_encode($idPhotos);
    $response['result'] = 'success';
}
if ($task == 'downloadContribution4b') {
    $idPhotos = [];
    $dbh = new PDO($dbEdit2);
    $stmt = $dbh->prepare('SELECT image, photoName, photoCredit, photoCaption FROM imageSecondary WHERE idPhoto=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idPhoto]);
    $row = $stmt->fetch();
    if ($row) {
        $response['hdImage'] = $row['image'];
        $response['photoName'] = $row['photoName'];
        $response['photoCredit'] = $row['photoCredit'];
        $response['photoCaption'] = $row['photoCaption'];
    }
    $response['result'] = 'success';
}
if ($task == 'downloadContributionDelete') {
    $dbh = new PDO($dbEdit);
    $stmt = $dbh->prepare('DELETE FROM articles WHERE idArticle=?');
    $stmt->execute([$idArticle]);
    $dbh = null;
    $dbh = new PDO($dbEdit2);
    $stmt = $dbh->prepare('DELETE FROM imageSecondary WHERE idArticle=?');
    $stmt->execute([$idArticle]);
    $dbh = null;
    $response['result'] = 'success';
}
//
// Calendar
//
if ($task == 'calendarSync') {
    $annual = json_decode($annual, true);
    $annualDayOfWeek = json_decode($annualDayOfWeek, true);
    $monthlyDayOfWeek = json_decode($monthlyDayOfWeek, true);
    $weeklyDayOfWeek = json_decode($weeklyDayOfWeek, true);
    $oneTimeEvent = json_decode($oneTimeEvent, true);
    $dbh = new PDO($dbCalendar);
    $dbh->beginTransaction();
    $stmt = $dbh->query('DELETE FROM annual');
    foreach ($annual as $row) {
        $stmt = $dbh->prepare('INSERT INTO annual (idAnnual, date, description) VALUES (?, ?, ?)');
        $stmt->execute(json_decode($row, true));
    }
    $stmt = $dbh->query('DELETE FROM annualDayOfWeek');
    foreach ($annualDayOfWeek as $row) {
        $stmt = $dbh->prepare('INSERT INTO annualDayOfWeek (idAnnualDayOfWeek, date, description) VALUES (?, ?, ?)');
        $stmt->execute(json_decode($row, true));
    }
    $stmt = $dbh->query('DELETE FROM monthlyDayOfWeek');
    foreach ($monthlyDayOfWeek as $row) {
        $stmt = $dbh->prepare('INSERT INTO monthlyDayOfWeek (idMonthlyDayOfWeek, date, description) VALUES (?, ?, ?)');
        $stmt->execute(json_decode($row, true));
    }
    $stmt = $dbh->query('DELETE FROM weeklyDayOfWeek');
    foreach ($weeklyDayOfWeek as $row) {
        $stmt = $dbh->prepare('INSERT INTO weeklyDayOfWeek (idWeeklyDayOfWeek, date, description) VALUES (?, ?, ?)');
        $stmt->execute(json_decode($row, true));
    }
    $stmt = $dbh->query('DELETE FROM oneTimeEvent');
    foreach ($oneTimeEvent as $row) {
        $stmt = $dbh->prepare('INSERT INTO oneTimeEvent (idOneTimeEvent, date, description) VALUES (?, ?, ?)');
        $stmt->execute(json_decode($row, true));
    }
    $stmt = $dbh->query('DELETE FROM note');
    if (isset($note)) {
        $stmt = $dbh->prepare('INSERT INTO note (description) VALUES (?)');
        $stmt->execute([$note]);
    }
    $dbh->commit();
    $dbh = null;
    $response['result'] = 'success';
}
//
// Classifieds
//
if ($task == 'classifiedsDelete') {
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('DELETE FROM ads WHERE idAd=?');
    $stmt->execute([$idAd]);
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'classifiedsEarlyRemoval') {
    $classifieds = [];
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
    $stmt->execute([$idAd]);
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'classifiedsNewDownload') {
    $dbh = new PDO($dbClassifiedsNew);
    $stmt = $dbh->prepare('SELECT email, title, description, categoryId FROM ads WHERE idAd=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idAd]);
    $row = $stmt->fetch();
    if ($row) {
        $response = $row;
    }
    $photosOrdered = [1, 2, 3, 4, 5, 6, 7];
    foreach ($photosOrdered as $photo) {
        $stmt = $dbh->prepare('SELECT photo' . $photo . ' FROM ads WHERE idAd=?');
        $stmt->setFetchMode(PDO::FETCH_NUM);
        $stmt->execute([$idAd]);
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
    $stmt->execute([$idAd]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        $response['photo'] = $row['0'];
    }
    $response['result'] = 'success';
}
if ($task == 'classifiedsSync') {
    $classifieds = [];
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
    $classifieds = [];
    $dbh = new PDO($dbClassifiedsNew);
    $stmt = $dbh->prepare('SELECT idAd FROM ads WHERE review < ?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$fifteenMinutesAgo]);
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
        $keys = array_keys($section);
        $key = $keys['0'];
        extract($section);
        if ($key === 'idSection') {
            $stmt = $dbh->prepare('INSERT INTO sections (idSection, section, sortOrderSection) VALUES (?, ?, ?)');
            $stmt->execute([$idSection, $section, $sortOrderSection]);
        } else {
            $stmt = $dbh->prepare('INSERT INTO subsections (idSubsection, subsection, parentId, sortOrderSubsection) VALUES (?, ?, ?, ?)');
            $stmt->execute([$idSubsection, $subsection, $parentId, $sortOrderSubsection]);
        }
    }
    $dbh->commit();
    $dbh = null;
}
if ($task == 'classifiedsUpdateInsert1') {
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('SELECT idAd FROM ads WHERE idAd=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idAd]);
    $row = $stmt->fetch();
    if ($row) {
        $stmt = $dbh->prepare('DELETE FROM ads WHERE idAd=?');
        $stmt->execute([$idAd]);
    }
    $stmt = $dbh->prepare('INSERT INTO ads (idAd, email, title, description, categoryId, review, startDate, duration, photos) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$idAd, $email, $title, $description, $categoryId, $review, $startDate, $duration, $photos]);
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'classifiedsUpdateInsert2') {
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('UPDATE ads SET photo' . $photoNumber . '=? WHERE idAd=?');
    $stmt->execute([$photo, $idAd]);
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'classifiedsUpload') {
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('INSERT INTO users (idUser, email, pass, payStatus) VALUES (?, ?, ?, ?)');
    $stmt->execute([$idUser, $email, $pass, $payStatus]);
    $dbh = null;
    $dbh = new PDO($dbClassifiedsNew);
    $stmt = $dbh->prepare('DELETE FROM users WHERE email=?');
    $stmt->execute([$email]);
    $dbh = null;
    $response['result'] = 'success';
}
//
// Menu
//
if ($task == 'menuDelete') {
    $dbh = new PDO($dbMenu);
    $stmt = $dbh->prepare('DELETE FROM menu WHERE idMenu=?');
    $stmt->execute([$idMenu]);
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'menuInsert') {
    $dbh = new PDO($dbMenu);
    $stmt = $dbh->prepare('SELECT idMenu FROM menu WHERE idMenu=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idMenu]);
    $row = $stmt->fetch();
    if ($row) {
        $stmt = $dbh->prepare('DELETE FROM menu WHERE idMenu');
        $stmt->execute([$idMenu]);
    }
    $stmt = $dbh->prepare('INSERT INTO menu (idMenu, menuName, menuSortOrder, menuPath, menuContent, menuAuthorization) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$idMenu, $menuName, $menuSortOrder, $menuPath, $menuContent, $menuAuthorization]);
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'menuOrder') {
    $sortOrder = json_decode($sortOrder, true);
    $dbh = new PDO($dbMenu);
    foreach ($sortOrder as $row) {
        $stmt = $dbh->prepare('UPDATE menu SET menuSortOrder=? WHERE idMenu=?');
        $stmt->execute([$row['1'], $row['0']]);
    }
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'menuSync') {
    $menu = [];
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
    $alertClassified = isset($alertClassified) ? json_decode($alertClassified, true) : null;
    $archiveAccess = isset($archiveAccess) ? json_decode($archiveAccess, true) : null;
    $calendarAccess = isset($calendarAccess) ? json_decode($calendarAccess, true) : null;
    $information = isset($information) ? json_decode($information, true) : null;
    $name = isset($name) ? json_decode($name, true) : null;
    $sortOrder = isset($sortOrder) ? json_decode($sortOrder, true) : null;
    //
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->query('DELETE from alertClassified');
    if (is_array($alertClassified)) {
        $stmt = $dbh->prepare('INSERT INTO alertClassified (idClassified, emailClassified) VALUES (?, ?)');
        $stmt->execute($alertClassified);
    }
    $dbh = null;
    //
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->query('DELETE from archiveAccess');
    if (is_array($archiveAccess)) {
        $stmt = $dbh->prepare('INSERT INTO archiveAccess (idAccess, access) VALUES (?, ?)');
        $stmt->execute($archiveAccess);
    }
    $dbh = null;
    //
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->query('DELETE from calendarAccess');
    if (is_array($calendarAccess)) {
        $stmt = $dbh->prepare('INSERT INTO calendarAccess (idCalendarAccess, access) VALUES (?, ?)');
        $stmt->execute($calendarAccess);
    }
    $dbh = null;
    //
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->query('DELETE from names');
    if (is_array($name)) {
        $stmt = $dbh->prepare('INSERT INTO names (idName, name, description) VALUES (?, ?, ?)');
        $stmt->execute($name);
    }
    $dbh = null;
    //
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->query('DELETE from registration');
    if (is_array($information)) {
        $stmt = $dbh->prepare('INSERT INTO registration (idRegistration, information) VALUES (?, ?)');
        $stmt->execute($information);
    }
    $dbh = null;
    //
    $dbh = new PDO($dbSettings);
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
    $hash = password_hash($hash, PASSWORD_DEFAULT);
    $content = "<?php\n";
    $content.= '$hash = \'' . $hash . '\';' . "\n";
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
        $stmt->execute([microtime(true)]);
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
// sitemap.xml, sitemap-news.xml, rss.xml
//
if ($task == 'sitemap') {
    //
    // sitemap.xml
    //
    $uri = $uriScheme . '://' . $_SERVER["HTTP_HOST"] . '/';
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->prepare('SELECT name, description FROM names WHERE idName=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([1]);
    $row = $stmt->fetch();
    $dbh = null;
    //
    if ($row) {
        extract($row);
    } else {
        $name = null;
        $description = null;
    }
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
        $h = preg_replace('/\%/', ' percentage', $page['2']);
        $h = preg_replace('/\@/', ' at ', $h);
        $h = preg_replace('/\&/', ' and ', $h);
        $h = preg_replace('/\s[\s]+/', '-', $h);
        $h = preg_replace('/[\s\W]+/', '-', $h);
        $h = preg_replace('/^[\-]+/', '', $h);
        $h = preg_replace('/[\-]+$/', '', $h);
        $headline = strtolower($h);
        fwrite($fp, utf8_encode('  <url><loc>' . $uri . '?a=' . $page['0'] . '+' . $headline . '</loc><lastmod>' . $page[1] . '</lastmod></url>' . "\n"));
    }
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
        $stmt = $dbh->query('SELECT idArticle, publicationDate, headline FROM articles ORDER BY publicationDate DESC, idArticle DESC');
        $stmt->setFetchMode(PDO::FETCH_NUM);
        foreach ($stmt as $row) {
            $lineCount++;
            urlLoc($uri, $row);
        }
        $dbh = null;
        $dbh = new PDO($dbArchive);
        $stmt = $dbh->query('SELECT idArticle, publicationDate, headline FROM articles WHERE headline IS NOT NULL ORDER BY publicationDate DESC, rowid DESC');
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
        $fp = fopen('../sitemap.xml', 'w');
        fwrite($fp, utf8_encode('<?xml version="1.0" encoding="UTF-8"?>' . "\n"));
        fwrite($fp, utf8_encode('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"));
        fwrite($fp, utf8_encode('  <url><loc>' . $uri . '</loc></url>' . "\n"));
        $dbh = new PDO($dbPublished);
        $stmt = $dbh->query('SELECT idArticle, publicationDate, headline FROM articles ORDER BY publicationDate DESC, idArticle DESC');
        $stmt->setFetchMode(PDO::FETCH_NUM);
        foreach ($stmt as $row) {
            urlLoc($uri, $row);
        }
        $dbh = null;
        $dbh = new PDO($dbArchive);
        $stmt = $dbh->query('SELECT idArticle, publicationDate, headline FROM articles WHERE headline IS NOT NULL ORDER BY publicationDate DESC, rowid DESC');
        $stmt->setFetchMode(PDO::FETCH_NUM);
        foreach ($stmt as $row) {
            urlLoc($uri, $row);
        }
        $dbh = null;
        fwrite($fp, utf8_encode('</urlset>' . "\n"));
        fclose($fp);
        $fp = null;
    }
    //
    // sitemap-news.xml
    //
    $twoDaysAgo = date("Y-m-d", time() - 172800);
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->prepare('SELECT name FROM names WHERE idName=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([1]);
    $row = $stmt->fetch();
    $dbh = null;
    extract($row);
    //
    // Begin the sitemap-news.xml file
    //
    $fp = fopen('../sitemap-news.xml', 'w');
    fwrite($fp, utf8_encode('<?xml version="1.0" encoding="UTF-8"?>' . "\n"));
    fwrite($fp, utf8_encode('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n"));
    fwrite($fp, utf8_encode('        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n"));
    //
    // Add articles
    //
    $dbhSection = new PDO($dbSettings);
    $stmt = $dbhSection->query('SELECT idSection, section, sortOrderSection FROM sections ORDER BY sortOrderSection');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        extract($row);
        $dbh = new PDO($dbPublished);
        $stmt = $dbh->prepare('SELECT idSection FROM articles WHERE idSection=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idSection]);
        $row = $stmt->fetch();
        if ($row) {
            $stmt = $dbh->prepare('SELECT idArticle, publicationTime, survey, keywords, headline, text FROM articles WHERE idSection = ? AND publicationDate <= "' . $today . '" AND publicationDate >= "' . $twoDaysAgo . '" ORDER BY sortOrderArticle');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$idSection]);
            foreach ($stmt as $row) {
                extract($row);
                if ($survey !== strval(1) and !empty($text) and strpos($text, '<') === false) {
                    $h = preg_replace('/\%/', ' percentage', $headline);
                    $h = preg_replace('/\@/', ' at ', $h);
                    $h = preg_replace('/\&/', ' and ', $h);
                    $h = preg_replace('/\s[\s]+/', '-', $h);
                    $h = preg_replace('/[\s\W]+/', '-', $h);
                    $h = preg_replace('/^[\-]+/', '', $h);
                    $h = preg_replace('/[\-]+$/', '', $h);
                    $headlineSEO = strtolower($h);
                    //
                    $str = str_replace('&', '&amp;', $headline);
                    $str = str_replace("\'", '&apos;', $str);
                    $str = str_replace('"', '&quot;', $str);
                    $str = str_replace('>', '&gt;', $str);
                    $headline = str_replace('<', '&lt;', $str);
                    //
                    fwrite($fp, utf8_encode('  <url>' . "\n"));
                    fwrite($fp, utf8_encode('    <loc>' . $uriScheme . '://' . $_SERVER["HTTP_HOST"] . '/?a=' . $idArticle . '+' . $headlineSEO . '</loc>' . "\n"));
                    fwrite($fp, utf8_encode('    <news:news>' . "\n"));
                    fwrite($fp, utf8_encode('      <news:publication>' . "\n"));
                    fwrite($fp, utf8_encode('        <news:name>' . $name . '</news:name>' . "\n"));
                    fwrite($fp, utf8_encode('        <news:language>en</news:language>' . "\n"));
                    fwrite($fp, utf8_encode('      </news:publication>' . "\n"));
                    if (!empty($genre)) {
                        fwrite($fp, utf8_encode('      <news:genres>' . $genre . '</news:genres>' . "\n"));
                    }
                    fwrite($fp, utf8_encode('      <news:publication_date>' . date(DATE_W3C, $publicationTime) . '</news:publication_date>' . "\n"));
                    fwrite($fp, utf8_encode('      <news:title>' . $headline . '</news:title>' . "\n"));
                    if (!empty($keywords)) {
                        fwrite($fp, utf8_encode('      <news:keywords>' . $keywords . '</news:keywords>' . "\n"));
                    }
                    fwrite($fp, utf8_encode('    </news:news>' . "\n"));
                    fwrite($fp, utf8_encode('  </url>' . "\n"));
                }
            }
        }
        $dbh = null;
    }
    $dbhSection = null;
    fwrite($fp, utf8_encode('</urlset>' . "\n"));
    fclose($fp);
    $fp = null;
    //
    // rss.xml
    //
    if (file_exists($includesPath . '/custom/programs/rss.php')) {
        include $includesPath . '/custom/programs/rss.php';
    }
    $response['result'] = 'success';
}
//
// Subscribers
//
if ($task == 'subscriberDelete') {
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->prepare('DELETE FROM users WHERE idUser=?');
    $stmt->execute([$idUser]);
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'subscribersNewCleanUp') {
    $dbh = new PDO($dbSubscribersNew);
    $stmt = $dbh->prepare('DELETE FROM users WHERE soa=?');
    $stmt->execute([1]);
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'subscribersDownload') {
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->prepare('SELECT email, payerEmail, payerFirstName, payerLastName, ipAddress, verify, verified, time, pass, payStatus, paid, paymentDate, note, contributor, classifiedOnly, deliver, deliver2, deliveryAddress, dCityRegionPostal, billingAddress, bCityRegionPostal, soa, evolve, expand, extend FROM users WHERE idUser = ?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idUser]);
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
    $stmt->execute([1]);
    $row = $stmt->fetch();
    $dbRows = [];
    if ($row) {
        $stmt = $dbh->prepare('UPDATE users SET soa=?');
        $stmt->execute([1]);
        $stmt = $dbh->prepare('SELECT email, payerEmail, payerFirstName, payerLastName, ipAddress, verify, verified, time, pass, payStatus, paid, paymentDate, note, contributor, classifiedOnly, deliver, deliver2, deliveryAddress, dCityRegionPostal, billingAddress, bCityRegionPostal, soa, evolve, expand, extend FROM users WHERE soa=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([1]);
        foreach ($stmt as $row) {
            extract($row);
            $dbRows[] = [
                'email' => $email,
                'payerEmail' => $payerEmail,
                'payerFirstName' => $payerFirstName,
                'payerLastName' => $payerLastName,
                'ipAddress' => $ipAddress,
                'verify' => $verify,
                'verified' => $verified,
                'time' => $time,
                'pass' => $pass,
                'payStatus' => $payStatus,
                'paid' => $paid,
                'paymentDate' => $paymentDate,
                'note' => $note,
                'contributor' => $contributor,
                'classifiedOnly' => $classifiedOnly,
                'deliver' => $deliver,
                'deliver2' => $deliver2,
                'deliveryAddress' => $deliveryAddress,
                'dCityRegionPostal' => $dCityRegionPostal,
                'billingAddress' => $billingAddress,
                'bCityRegionPostal' => $bCityRegionPostal,
                'soa' => $soa,
                'evolve' => $evolve,
                'expand' => $expand,
                'extend' => $extend
            ];
        }
    }
    $dbh = null;
    $response['dbRows'] = json_encode($dbRows);
    $response['result'] = 'success';
}
if ($task == 'subscribersSoaUnflag') {
    $subscribers = [];
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->query('SELECT idUser FROM users WHERE idUser=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idUser]);
    $row = $stmt->fetch();
    if ($row) {
        $stmt = $dbh->prepare('UPDATE users SET soa=? WHERE idUser=?');
        $stmt->execute([null, $idUser]);
    }
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'subscribersSync') {
    $subscribers = [];
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
    $subscribers = [];
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
    $stmt->execute([$idUser]);
    $row = $stmt->fetch();
    if ($row) {
        $stmt = $dbh->prepare('UPDATE users SET email=?, payerEmail=?, payerFirstName=?, payerLastName=?, ipAddress=?, verify=?, verified=?, time=?, pass=?, payStatus=?, paid=?, paymentDate=?, note=?, contributor=?, classifiedOnly=?, deliver=?, deliver2=?, deliveryAddress=?, dCityRegionPostal=?, billingAddress=?, bCityRegionPostal=?, soa=?, evolve=?, expand=?, extend=? WHERE idUser=?');
        $stmt->execute([$email, $payerEmail, $payerFirstName, $payerLastName, $ipAddress, $verify, $verified, $time, $pass, $payStatus, $paid, $paymentDate, $note, $contributor, $classifiedOnly, $deliver, $deliver2, $deliveryAddress, $dCityRegionPostal, $billingAddress, $bCityRegionPostal, $soa, $evolve, $expand, $extend, $idUser]);
    }
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'subscribersUpload') {
    $dbh = new PDO($dbSubscribers);
    $stmt = $dbh->prepare('INSERT INTO users (idUser, email, payerEmail, payerFirstName, payerLastName, ipAddress, verify, verified, time, pass, payStatus, paid, paymentDate, note, contributor, classifiedOnly, deliver, deliver2, deliveryAddress, dCityRegionPostal, billingAddress, bCityRegionPostal, soa, evolve, expand, extend) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$idUser, $email, $payerEmail, $payerFirstName, $payerLastName, $ipAddress, $verify, $verified, $time, $pass, $payStatus, $paid, $paymentDate, $note, $contributor, $classifiedOnly, $deliver, $deliver2, $deliveryAddress, $dCityRegionPostal, $billingAddress, $bCityRegionPostal, $soa, $evolve, $expand, $extend]);
    $dbh = null;
    $dbh = new PDO($dbSubscribersNew);
    $stmt = $dbh->prepare('DELETE FROM users WHERE email=?');
    $stmt->execute([$email]);
    $dbh = null;
    $response['result'] = 'success';
}
//
// Surveys
//
if ($task == 'surveySync') {
    $answers = json_decode($answers, true);
    $dbh = new PDO($dbSurvey);
    $stmt = $dbh->query('DELETE FROM answers');
    $dbh->beginTransaction();
    foreach ($answers as $answer) {
        $answer = json_decode($answer, true);
        $stmt = $dbh->prepare('INSERT INTO answers (idAnswer, idArticle, sortOrder, answer) VALUES (?, ?, ?, ?)');
        $stmt->execute($answer);
    }
    $dbh->commit();
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'surveyUpdate') {
    $answers = json_decode($answers, true);
    $dbh = new PDO($dbSurvey);
    $stmt = $dbh->prepare('DELETE FROM answers WHERE idArticle=?');
    $stmt->execute([$idArticle]);
    $dbh->beginTransaction();
    foreach ($answers as $answer) {
        $answer = json_decode($answer, true);
        $stmt = $dbh->prepare('INSERT INTO answers (idAnswer, idArticle, sortOrder, answer) VALUES (?, ?, ?, ?)');
        $stmt->execute($answer);
    }
    $dbh->commit();
    $dbh = null;
    $response['result'] = 'success';
}
if ($task == 'surveyVotesDownload') {
    $votes = [];
    $dbh = new PDO($dbSurvey);
    $stmt = $dbh->prepare('SELECT * FROM tally WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_NUM);
    $stmt->execute([$idArticle]);
    foreach ($stmt as $row) {
        $votes[] = json_encode($row);
    }
    $dbh = null;
    $response['remoteVotes'] = json_encode($votes);
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
    or $task == 'archiveSearch'
    or $task == 'archiveSync'
    or $task == 'archiveSync2'
    or $task == 'calendarSync'
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
    or $task == 'surveySync'
    or $task == 'surveyUpdate'
    or $task == 'surveyVotesDownload'
    or $task == 'test'
    or $task == 'updateInsert1'
    or $task == 'updateInsert2'
    or $task == 'updateInsert3'
    or $task == 'updateInsert4')
    and isset($response['result'])
) {
    //
    // Respond
    //
    echo json_encode(array_map('base64_encode', $response));
}
?>
