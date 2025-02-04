<?php
/**
 * Predefined menu item: Archive search
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
@session_start();
if ($freeOrPaid !== 'free') {
    include $includesPath . '/subscriber/authorization.php';
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
$database = $dbArchive;
$database2 = $dbArchive2;
$editorView = 1;
$imagePath = 'imagea.php';
$imagePath2 = 'imagea2.php';
$use = '?m=archive-search';
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
$sql1 = 'SELECT idArticle, publicationDate, byline, headline, summary, link FROM articles WHERE ';
$sql2 = null;
if (!empty($bylinePost)) {
    $sql1.= 'byline MATCH ?';
    $sql2[] = $bylinePost;
}
if (!empty($bylinePost) and !empty($headlinePost)) {
    $sql1.= ' INTERSECT SELECT idArticle, publicationDate, byline, headline, summary FROM articles WHERE';
}
if (!empty($headlinePost)) {
    $sql1.= ' headline MATCH ?';
    $sql2[] = $headlinePost;
}
if (!empty($bylinePost) and !empty($textPost) or !empty($headlinePost) and !empty($textPost)) {
    $sql1.= ' INTERSECT SELECT idArticle, publicationDate, byline, headline, summary FROM articles WHERE';
}
if (!empty($textPost)) {
    $sql1.= ' text MATCH ?';
    $sql2[] = $textPost;
}
if (!empty($bylinePost) and !empty($startDatePost)
    or !empty($headlinePost) and !empty($startDatePost)
    or !empty($textPost) and !empty($startDatePost)
    or !empty($bylinePost) and !empty($endDatePost)
    or !empty($headlinePost) and !empty($endDatePost)
    or !empty($textPost) and !empty($endDatePost)
) {
    $sql1.= ' INTERSECT SELECT idArticle, publicationDate, byline, headline, summary FROM articles WHERE';
}
if (empty($startDatePost) and !empty($endDatePost)) {
    $startDatePost = '1970-01-01';
}
if (empty($endDatePost)) {
    $endDatePost = date("Y-m-d");
}
if (!empty($startDatePost) and !empty($endDatePost)) {
    $sql1.= ' ? <= publicationDate AND publicationDate <= ?';
    $sql2[] = $startDatePost;
    $sql2[] = $endDatePost;
}
$sql1.= ' ORDER BY publicationDate DESC';
//
// HTML
//
echo '    <div class="main">' . "\n";
echo '      <h1>' . $title . "</h1>\n";
//
// Article view displays an entire single article
//
$html = null;
if (isset($_GET['a'])) {
    include $includesPath . '/subscriber/displayArticle.inc';
    //
    // Index view displays a list of all articles with a summary of each article
    //
} else {
    //
    // Search fields and results view
    //
    echo '
      <p>Search by any of the following criteria. Enter complete words or the beginning of words followed by an asterisk, for example, either <i>the</i> or <i>th*</i>.</p>

      <form method="post" action="' . $uri . '?m=archive-search">
        <p><label for="headline">Headline contains</label><br>
        <input type="search" id="headline" name="headline" class="h"></p>

        <p><label for="startDate">Publication date range search, start date to end date</label><br>
        <input type="date" id="startDate" name="startDate" class="date"> <input type="date" name="endDate" class="date"></p>

        <p><label for="byline">Byline contains</label><br>
        <input type="search" id="byline" name="byline" class="h"></p>

        <p><label for="text">Article contains</label><br>
        <input type="search" id="text" name="text" class="h"></p>

        <p><input type="submit" class="button" value="Search" name="search">
      </form>' . "\n";
    if (!empty($bylinePost) or !empty($headlinePost) or !empty($startDatePost) or !empty($textPost)) {
        $html = null;
        $stopTime = 19 + time();
        $dbNumber = 0;
        while ($dbNumber !== -1) {
            $db = str_replace('archive', 'archive-' . $dbNumber, $dbArchive);
            if ($dbNumber === 0
                or file_exists(str_replace('sqlite:', '', $db))
            ) {
                if ($dbNumber === 0) {
                    $database = $dbArchive;
                } else {
                    $database = $db;
                }
                $dbNumber++;
            } else {
                $dbNumber = -1;
                $database = null;
            }
            if (!empty($database)) {
                $dbh = new PDO($database);
                $stmt = $dbh->prepare($sql1);
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute($sql2);
                foreach ($stmt as $row) {
                    extract($row);
                    if (time() > $stopTime) {
                        echoIfMessage('The query is taking too long. Please refine the search criteria to narrow the search results.');
                        break;
                    }
                    $html.= "      <hr>\n\n";
                    if (isset($headline)) {
                        if (empty($link)) {
                            //
                            // Database archive
                            //
                            $html.= '    <h2><a class="n" href="' . $uri . $use . '&a=' . $idArticle . '">' . html($headline) . "</a></h2>\n\n";
                        } else {
                            //
                            // HTML and PDF archives
                            //
                            if (substr($link, -5) === '.html'
                                or substr($link, -4) === '.pdf'
                            ) {
                                $html.= '    <h2><a class="n" href="' . $uri . $link . '" target="_blank">' . html($headline) . "</a></h2>\n\n";
                            }
                        }
                    }
                    $bylineDateTime = isset($publicationDate) ? date("l, F j, Y", strtotime($publicationDate)) : null;
                    if (!empty($bylineDateTime)) {
                        $html.= '      <p>' . html($bylineDateTime);
                    }
                    if (!empty($byline) and isset($bylineDateTime)) {
                        $html.= ', ';
                    }
                    if (!empty($byline)) {
                        $html.= 'by ' . html($byline);
                    }
                    if (!empty($byline) or isset($bylineDateTime)) {
                        $html.= "</p>\n\n";
                    }
                    if (!empty($summary)) {
                        $summary = str_replace('*', '', $summary);
                        $html.= '      <p class="summary">' . html($summary) . "</p>\n";
                    }
                }
                $dbh = null;
            }
        }
    }
}
echo $html;
echo '    </div>' . "\n";
?>
