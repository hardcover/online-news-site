<?php
/**
 * Search the archives and display the results
 *
 * PHP version 5
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2013-2015 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *.@license   http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version   GIT: 2015-05-31
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
session_start();
require 'z/system/configuration.php';
if ($freeOrPaid != 'free') {
    include $includesPath . '/authorization.php';
} else {
    $uri = $uriScheme . '://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
}
require $includesPath . '/common.php';
//
// Variables
//
$editorView = null;
$bylinePost = inlinePost('byline');
$endDatePost = inlinePost('endDate');
$headlinePost = inlinePost('headline');
$idArticle = inlinePost('idArticle');
$startDatePost = inlinePost('startDate');
$textPost = inlinePost('text');
//
$archiveSync = 1;
$database = $dbArchive;
$editorView = 1;
$imagePath = 'imagea.php';
$menu = "\n" . '  <h4 class="m"><a class="m" href="edit.php">&nbsp;Edit&nbsp;</a><a class="m" href="published.php">&nbsp;Published&nbsp;</a><a class="m" href="preview.php">&nbsp;Preview&nbsp;</a><a class="s" href="archive.php">&nbsp;Archives&nbsp;</a></h4>' . "\n";
$use = 'archive';
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
require 'z/includes/header1.inc';
echo '  <title>' . $title . "</title>\n";
?>
  <link rel="icon" type="image/png" href="images/favicon.png" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" type="text/css" href="z/jquery-ui.theme.css" />
  <link rel="stylesheet" type="text/css" href="z/jquery-ui.structure.css" />
  <link rel="stylesheet" type="text/css" href="z/base.css" />
  <link rel="stylesheet" type="text/css" media="(max-width: 768px)" href="z/small.css" />
  <link rel="stylesheet" type="text/css" media="(min-width: 768px)" href="z/large.css" />
  <script type="text/javascript" src="z/jquery.js"></script>
  <script type="text/javascript" src="z/jquery-ui.js"></script>
  <script type="text/javascript" src="z/datepicker.js"></script>
</head>


<body>
  <p><span class="al"><a class="n" href="<?php echo $uri; ?>archive.php">Archives</a> | <a class="n" href="<?php echo $uri; ?>">Index</a><?php echo $logOutHtml; ?></span><p>

<?php
echo '  <h1>' . $title . "</h1>\n";
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
  <p>Search by any of the following criteria.</p>

  <form method="post" action="' . $uri . 'archive.php">
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
                $html.= '  <h2><a class="n" href="' . $uri . $use . '.php?a=' . $idArticle . '">' . html($headline) . "</a></h2>\n\n";
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
                $html.= '  <p class="s">' . html($summary) . "</p>\n";
            }
        }
        $dbh = null;
    }
}
echo $html;
?>

  <p><span class="al"><a class="n" href="<?php echo $uri; ?>archive.php">Archives</a> | <a class="n" href="<?php echo $uri; ?>">Index</a><?php echo $logOutHtml; ?></span><br /><p>
</body>
</html>