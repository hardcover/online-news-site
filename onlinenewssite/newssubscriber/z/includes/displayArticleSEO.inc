<?php
/**
 * Display a single article as linked in sitemap.xml
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
// Look for the article in the published and archive databases
//
$dbh = new PDO($dbPublished);
$stmt = $dbh->prepare('SELECT * FROM articles WHERE idArticle=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array($aGet));
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    extract($row);
    $database2 = $dbPublished2;
    $imagePath = 'imagep.php';
    $imagePath2 = 'imagep2.php';
} else {
    $dbh = new PDO($dbArchive);
    $stmt = $dbh->prepare('SELECT * FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($aGet));
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        extract($row);
        $database2 = $dbArchive2;
        $imagePath = 'imagea.php';
        $imagePath2 = 'imagea2.php';
    }
}
if (empty($idArticle)) {
    include $includesPath . '/displayIndex.inc';
    exit;
}
$dbh = new PDO($dbSettings);
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
$html = '  <p><b>' . $section . "</b></p>\n\n";
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
if (isset($byline) or isset($bylineDateTime)) {
    $html.= '  <h5>';
}
if (isset($byline)) {
    $html.= 'By ' . $byline;
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
echo $html;
?>