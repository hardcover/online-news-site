<?php
/**
 * Search the archives and display the results
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2016 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *.@license   http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2016-10-16
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
@session_start();
if ($freeOrPaid != 'free') {
    include $includesPath . '/authorization.php';
} else {
    $uri = $uriScheme . '://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
}
//
// Variables
//
$bylinePost = inlinePost('byline');
$editorView = null;
$endDatePost = inlinePost('endDate');
$headlinePost = inlinePost('headline');
$idArticle = inlinePost('idArticle');
$startDatePost = inlinePost('startDate');
$textPost = inlinePost('text');
//
$archiveSync = 1;
$database = $dbArchive;
$database2 = $dbArchive2;
$editorView = 1;
$imagePath = 'imagea.php';
$imagePath2 = 'imagea2.php';
$use = '?m=archive';
//
if (isset($_SESSION['userId'])) {
    $logOutHtml = ' | <a class="n" href="' . $uri . 'logout.php">Log out</a>';
} else {
    $logOutHtml = null;
}
//
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT name FROM names');
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $title = html($row['name']) . ' Archives';
} else {
    $title = 'Archives';
}
//
// Build the SQL query
//
$sql1 = 'SELECT idArticle, publicationDate, byline, headline, summary FROM articles WHERE ';
$sql2 = null;
if (isset($bylinePost)) {
    $sql1.= 'byline MATCH ?';
    $sql2[] = $bylinePost;
}
if (isset($bylinePost, $headlinePost)) {
    $sql1.= ' AND ';
}
if (isset($headlinePost)) {
    $sql1.= 'headline MATCH ?';
    $sql2[] = $headlinePost;
}
if (isset($bylinePost, $textPost) or isset($headlinePost, $textPost)) {
    $sql1.= ' AND ';
}
if (isset($textPost)) {
    $sql1.= 'text MATCH ?';
    $sql2[] = $textPost;
}
if (!isset($endDatePost)) {
    $endDatePost = date("Y-m-d");
}
if (isset($bylinePost, $startDatePost) or isset($headlinePost, $startDatePost) or isset($headlinePost, $startDatePost)) {
    $sql1.= ' AND ';
}
if (isset($startDatePost, $endDatePost)) {
    $sql1.= '? <= publicationDate <= ?';
    $sql2[] = '%' . $startDatePost . '%';
    $sql2[] = '%' . $endDatePost . '%';
}
$sql1.= ' ORDER BY publicationDate DESC';
//
// HTML
//
echo '    <h1>' . $title . "</h1>\n";
//
// Article view displays an entire single article
//
$html = null;
if (isset($_GET['a'])) {
    include $includesPath . '/displayArticle.inc';
    //
    // Index view displays a list of all articles with a summary of each article
    //
} else {
    echo '
    <p>Search by any of the following criteria. Enter complete words or the beginning of words followed by an asterisk, for example, either <i>the</i> or <i>th*</i>.</p>

    <form method="post" action="' . $uri . '?m=archive">
      <p><label for="headline">Headline contains</label><br />
      <input id="headline" name="headline" type="text" class="w" autofocus /></p>

      <p><label for="startDate">Publication date range search</label><br />
      <input id="startDate" name="startDate" type="text" class="datepicker h" placeholder="Start date" /> <input name="endDate" type="text" class="datepicker h" placeholder="End date" /></p>

      <p><label for="byline">Byline contains</label><br />
      <input id="byline" name="byline" type="text" class="w" /></p>

      <p><label for="text">Article contains</label><br />
      <input id="text" name="text" type="text" class="w" /></p>

      <p><input type="submit" class="button" value="Search" name="search" />
    </form>' . "\n";
    if (isset($bylinePost) or isset($headlinePost) or isset($startDatePost) or isset($textPost)) {
        $html = null;
        $stopTime = 19 + time();
        $dbh = new PDO($dbArchive);
        $stmt = $dbh->prepare($sql1);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute($sql2);
        foreach ($stmt as $row) {
            extract($row);
            if (time() > $stopTime) {
                echoIfMessage('The query is taking too long. Please refine the search criteria to narrow the search results.');
                break;
            }
            $html.= "  <hr />\n\n";
            if (isset($headline)) {
                $html.= '  <h2><a class="n" href="' . $uri . $use . '&amp;a=' . $idArticle . '">' . html($headline) . "</a></h2>\n\n";
            }
            $bylineDateTime = isset($publicationDate) ? date("l, F j, Y", strtotime($publicationDate)) : null;
            if (isset($bylineDateTime)) {
                $html.= '  <p>' . html($bylineDateTime);
            }
            if (isset($byline) and isset($bylineDateTime)) {
                $html.= ', ';
            }
            if (isset($byline)) {
                $html.= 'by ' . html($byline);
            }
            if (isset($byline) or isset($bylineDateTime)) {
                $html.= "</p>\n\n";
            }
            if (isset($summary)) {
                $summary = str_replace('*', '', $summary);
                $html.= '  <p class="s">' . html($summary) . "</p>\n";
            }
        }
        $dbh = null;
    }
}
echo $html;
?>
