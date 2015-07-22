<?php
/**
 * Display a single article
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
// Check for article by ID
//
$dbh = new PDO($database);
$stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE idArticle=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array(secure($_GET['a'])));
$row = $stmt->fetch();
$dbh = null;
if ($row['idArticle']) {
    $dbh = new PDO($database);
    $stmt = $dbh->prepare('SELECT * FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array(secure($_GET['a'])));
    $row = $stmt->fetch();
    extract($row);
    //
    // Set previous and next links
    //
    $stmt = $dbh->prepare('SELECT idArticle, sortOrderArticle FROM articles WHERE idSection=? AND sortOrderArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idSection, $sortOrderArticle - 1));
    $row = $stmt->fetch();
    if ($row) {
        $previous = '<a class="n" href="' . $uri . $use . '.php?a=' . $row['idArticle'] . '">Previous</a> | ';
    } else {
        $previous = null;
    }
    $stmt = $dbh->prepare('SELECT idArticle, sortOrderArticle FROM articles WHERE idSection=? AND sortOrderArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idSection, $sortOrderArticle + 1));
    $row = $stmt->fetch();
    if ($row) {
        $next = '<a class="n" href="' . $uri . $use . '.php?a=' . $row['idArticle'] . '">Next</a>';
        $pipe = ' | ';
    } else {
        $next = null;
        $pipe = null;
    }
    //
    // Set paper and section name
    //
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->query('SELECT name FROM names');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $row = $stmt->fetch();
    if ($row) {
        extract($row);
    } else {
        $name = null;
    }
    $stmt = $dbh->prepare('SELECT section FROM sections WHERE idSection=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idSection));
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        extract($row);
    } else {
        $section = null;
    }
    //
    // HTML
    //
    $bylineDateTime = isset($publicationDate) ? date("l, F j, Y", strtotime($publicationDate)) : null;
    if ($database != $dbArchive) {
        $html.= '  <p><span class="al">' . $previous . '<a class="n" href="' . $uri . '">Index</a>' . $pipe . $next;
        $html.= $logOutHtml . "</span><br /></p>\n\n";
        $html.= '  <h2>' . $name . "</h2>\n\n";
        $html.= '  <p><b>' . $section . "</b></p>\n\n";
    }
    $html.= '  <h1>' . html($headline) . "</h1>\n\n";
    if (isset($standfirst)) {
        $html.= '  <h3>' . html($standfirst) . "</h3>\n\n";
    }
    if (isset($hdImage)) {
        $html.= '  <p><img src="' . $imagePath . '?i=' . muddle($idArticle) . 'h" class="w b" alt=""></p>' . "\n\n";
    }
    if ($photoCaption != '' and $photoCredit != '') {
        $html.= '  <h6>' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
    } elseif (isset($photoCaption)) {
        $html.= '  <h6>' . html($photoCaption) . "</h6>\n\n";
    } elseif (isset($photoCredit)) {
        $html.= '  <h6 class="r">' . $photoCredit . "</h6>\n\n";
    }
    if (isset($byline)) {
        $html.= '  <h5>By ' . $byline;
    }
    if (isset($byline) and isset($bylineDateTime)) {
        $html.= ', ';
    }
    if (isset($bylineDateTime)) {
        $html.= html($bylineDateTime);
    }
    if (isset($byline) or isset($bylineDateTime)) {
        $html.= "</h5>\n\n";
    }
    $html.= Parsedown::instance()->parse($text);
    //$html.= '  <p>' . $text . "</p>\n\n";
    $dbh = new PDO($database2);
    $stmt = $dbh->prepare('SELECT idPhoto, photoCredit, photoCaption FROM imageSecondary WHERE idArticle=? ORDER BY time');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idArticle));
    foreach ($stmt as $row) {
        extract($row);
        $html.= '  <p><img src="' . $imagePath2 . '?i=' . muddle($idPhoto) . 'h" class="w b" alt=""></p>' . "\n\n";
        if ($photoCaption != '' and $photoCredit != '') {
            $html.= '  <h6>' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
        } elseif (isset($photoCaption)) {
            $html.= '  <h6>' . html($photoCaption) . "</h6>\n\n";
        } elseif (isset($photoCredit)) {
            $html.= '  <h6 class="r">' . $photoCredit . "</h6>\n\n";
        }
    }
    $dbh = null;
    if ($database != $dbArchive) {
        $html.= '  <p><br /><span class="al">' . $previous . '<a class="n" href="' . $uri . '">Index</a>' . $pipe . $next;
        $html.= $logOutHtml . "</span><br /></p>\n";
    }
} else {
    header('Location: ' . $uri);
}
?>