<?php
/**
 * Create the subscriber databases on the first run
 * Identical in newseditor and newssubscriber
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
if (!file_exists($includesPath . '/databases')) {
    mkdir($includesPath . '/databases', 0755);
}
//
$dbh = new PDO($dbAdvertising);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "maxAd" ("idMaxAds" INTEGER PRIMARY KEY, "maxAds" INTEGER)');
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "advertisements" ("idAd" INTEGER PRIMARY KEY, "startDateAd", "endDateAd", "sortOrderAd" INTEGER, "sortPriority" INTEGER NOT NULL DEFAULT (2), "organization", "payStatus" INTEGER, "link", "linkAlt", "enteredBy", "note", "originalImage", "originalImageWidth" INTEGER, "originalImageHeight" INTEGER, "image", "imageWidth" INTEGER, "imageHeight" INTEGER)');
$dbh = null;
//
$dbh = new PDO($dbArchive);
$stmt = $dbh->query('CREATE VIRTUAL TABLE IF NOT EXISTS "articles" USING fts4 ("idArticle", "publicationDate", "publicationTime", "endDate", "survey", "genre", "keywords", "idSection", "sortOrderArticle", "sortPriority", "byline", "headline", "standfirst", "text", "summary", "evolve", "expand", "extend", "photoName", "photoCredit", "photoCaption", "originalImageWidth", "originalImageHeight", "thumbnailImage", "thumbnailImageWidth", "thumbnailImageHeight", "hdImage", "hdImageWidth", "hdImageHeight")');
$dbh = null;
//
$dbh = new PDO($dbArchive2);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "imageSecondary" ("idPhoto" INTEGER UNIQUE, "idArticle" INTEGER, "image", "photoName", "photoCredit", "photoCaption", "time" INTEGER)');
$stmt = $dbh->query('CREATE INDEX IF NOT EXISTS "main"."imageSecondaryIndex" ON "imageSecondary" ("idPhoto" ASC);');
$dbh = null;
//
$dbh = new PDO($dbCalendar);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "annual" ("idAnnual" INTEGER PRIMARY KEY, "date", "description")');
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "annualDayOfWeek" ("idAnnualDayOfWeek" INTEGER PRIMARY KEY, "date", "description")');
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "monthlyDayOfWeek" ("idMonthlyDayOfWeek" INTEGER PRIMARY KEY, "date", "description")');
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "note" ("idNote" INTEGER PRIMARY KEY, "description")');
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "oneTimeEvent" ("idOneTimeEvent" INTEGER PRIMARY KEY, "date", "description")');
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "weeklyDayOfWeek" ("idWeeklyDayOfWeek" INTEGER PRIMARY KEY, "date", "description")');
//
$dbh = new PDO($dbClassifieds);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "sections" ("idSection" INTEGER PRIMARY KEY, "section", "sortOrderSection" INTEGER, "sortPriority" INTEGER NOT NULL DEFAULT (2))');
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "subsections" ("idSubsection" INTEGER PRIMARY KEY, "subsection", "parentId" INTEGER, "sortOrderSubsection" INTEGER, "sortPrioritySubSection" INTEGER NOT NULL DEFAULT (2))');
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "ads" ("idAd" INTEGER PRIMARY KEY, "email", "title", "description", "categoryId" INTEGER, "review", "startDate", "duration" INTEGER, "invoice" INTEGER, "invoiced" INTEGER, "payment", "paymentDate", "paymentAmount", "photos", "photo1", "photo2", "photo3", "photo4", "photo5", "photo6", "photo7")');
$stmt = $dbh->query('SELECT idSection FROM sections');
$row = $stmt->fetch();
if ($row === false) {
    include $includesPath . '/classifiedsCategories.php';
    $i = null;
    $dbh->beginTransaction();
    foreach ($classifiedCategories as $section => $subsections) {
        $i++;
        $stmt = $dbh->prepare('INSERT INTO sections (section, sortOrderSection) VALUES (?, ?)');
        $stmt->execute([$section, $i]);
        $stmt = $dbh->prepare('SELECT idSection FROM sections WHERE section=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$section]);
        $row = $stmt->fetch();
        extract($row);
        $ii = null;
        foreach ($subsections as $subsection) {
            $ii++;
            $stmt = $dbh->prepare('INSERT INTO subsections (subsection, parentId, sortOrderSubsection) VALUES (?, ?, ?)');
            $stmt->execute([$subsection, $idSection, $ii]);
        }
    }
    $dbh->commit();
}
$dbh = null;
//
$dbh = new PDO($dbClassifiedsNew);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "ads" ("idAd" INTEGER PRIMARY KEY, "email", "title", "description", "categoryId" INTEGER, "review", "startDate", "duration" INTEGER, "invoice" INTEGER, "invoiced" INTEGER, "payment", "paymentDate", "paymentAmount", "photos", "photo1", "photo2", "photo3", "photo4", "photo5", "photo6", "photo7")');
$dbh = null;
//
$dbh = new PDO($dbEdit);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "articles" ("idArticle" INTEGER, "userId" INTEGER, "publicationDate", "publicationTime" INTEGER, "endDate", "survey" INTEGER, "genre", "keywords", "idSection" INTEGER, "sortOrderArticle" INTEGER, "sortPriority" INTEGER NOT NULL DEFAULT (2), "byline", "headline", "standfirst", "text", "summary", "evolve", "expand", "extend", "photoName", "photoCredit", "photoCaption", "originalImageWidth" INTEGER, "originalImageHeight" INTEGER, "thumbnailImage", "thumbnailImageWidth" INTEGER, "thumbnailImageHeight" INTEGER, "hdImage", "hdImageWidth" INTEGER, "hdImageHeight" INTEGER)');
$dbh = null;
//
$dbh = new PDO($dbEdit2);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "imageSecondary" ("idPhoto" INTEGER UNIQUE, "idArticle" INTEGER, "image", "photoName", "photoCredit", "photoCaption", "time" INTEGER)');
$dbh = null;
//
$dbh = new PDO($dbMenu);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "menu" ("idMenu" INTEGER PRIMARY KEY, "menuName", "menuSortOrder" INTEGER, "sortPriority" INTEGER NOT NULL DEFAULT (2), "menuPath", "menuContent", "menuAuthorization" INTEGER)');
$dbh = null;
//
$dbh = new PDO($dbPublished);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "articles" ("idArticle" INTEGER, "publicationDate", "publicationTime" INTEGER, "endDate", "survey" INTEGER, "genre", "keywords", "idSection" INTEGER, "sortOrderArticle" INTEGER, "sortPriority" INTEGER NOT NULL DEFAULT (2), "byline", "headline", "standfirst", "text", "summary", "evolve", "expand", "extend", "photoName", "photoCredit", "photoCaption", "originalImageWidth" INTEGER, "originalImageHeight" INTEGER, "thumbnailImage", "thumbnailImageWidth" INTEGER, "thumbnailImageHeight" INTEGER, "hdImage", "hdImageWidth" INTEGER, "hdImageHeight" INTEGER)');
$dbh = null;
//
$dbh = new PDO($dbPublished2);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "imageSecondary" ("idPhoto" INTEGER UNIQUE, "idArticle" INTEGER, "image", "photoName", "photoCredit", "photoCaption", "time" INTEGER)');
$dbh = null;
//
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "alertClassified" ("idClassified" INTEGER PRIMARY KEY, "emailClassified")');
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "archiveAccess" ("idAccess" INTEGER PRIMARY KEY, "access" INTEGER)');
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "calendarAccess" ("idCalendarAccess" INTEGER PRIMARY KEY, "access" INTEGER)');
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "sections" ("idSection" INTEGER PRIMARY KEY, "section", "sortOrderSection" INTEGER, "sortPriority" INTEGER NOT NULL DEFAULT (2))');
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "names" ("idName" INTEGER PRIMARY KEY, "name", "description")');
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "registration" ("idRegistration" INTEGER PRIMARY KEY, "information")');
$stmt = $dbh->query('SELECT idRegistration FROM registration');
$row = $stmt->fetch();
if ($row === false) {
    $stmt = $dbh->prepare('INSERT INTO registration (information) VALUES (?)');
    $stmt->execute(['A free registration is required to place classified ads and to submit other site forms. A paid registration might be required to access some site content. All registrations begin with an email and password. The email must be verified before the registration can be used to log in. Instructions to verify the email address will follow after the information below is sent.']);
}
$dbh = null;
//
$dbh = new PDO($dbSubscribers);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "users" ("idUser" INTEGER PRIMARY KEY, "email", "payerEmail", "payerFirstName", "payerLastName", "ipAddress", "verify", "verified" INTEGER, "time" INTEGER, "pass", "payStatus" INTEGER, "paid", "paymentDate", "note", "contributor" INTEGER, "classifiedOnly" INTEGER, "deliver" INTEGER, "deliver2" INTEGER, "deliveryAddress", "dCityRegionPostal", "billingAddress", "bCityRegionPostal", "soa" INTEGER, "evolve", "expand", "extend")');
$dbh = null;
//
$dbh = new PDO($dbSurvey);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "answers" ("idAnswer" INTEGER PRIMARY KEY, "idArticle" INTEGER, "sortOrder" INTEGER, "answer")');
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "tally" ("idTally" INTEGER PRIMARY KEY, "idArticle" INTEGER, "idAnswer" INTEGER, "ipAddress")');
$dbh = null;
?>
