<?php
/**
 * Summary of articles in edit
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
//
// Variables
//
$html = null;
if ($use == 'edit') {
    $database = $dbEdit;
    $imagePath = 'imagee.php';
} elseif ($use == 'published') {
    $database = $dbPublished;
    $imagePath = 'imagep.php';
}
//
// Program
//
if (isset($_GET['a'])) {
    //
    // Article view displays an entire single article
    //
    include $includesPath . '/displayArticle.inc';
} else {
    //
    // Index view displays a list of all articles with a summary of each article
    //
    echo $publishedIndexAdminLinks;
    $dbhSection = new PDO($dbSettings);
    $stmt = $dbhSection->query('SELECT idSection, section FROM sections ORDER BY sortOrderSection');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        extract($row);
        $dbh = new PDO($database);
        $stmt = $dbh->prepare('SELECT idSection FROM articles WHERE idSection=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(array($idSection));
        $row = $stmt->fetch();
        if ($row) {
            $count = null;
            $html.= "\n" . '  <h4>' . $section . "</h4>\n\n";
            if (is_null($editorView)) {
                $stmt = $dbh->query('SELECT idArticle, headline, summary, thumbnailImageWidth, thumbnailImageHeight FROM articles WHERE idSection = ? AND publicationDate <= "' . $datePost . '" AND "' . $datePost . '" <= endDate ORDER BY sortOrderArticle');
            } else {
                $stmt = $dbh->query('SELECT idArticle, publicationDate, headline, summary, thumbnailImageWidth, thumbnailImageHeight FROM articles WHERE idSection = ? ORDER BY sortOrderArticle');
            }
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute(array($idSection));
            foreach ($stmt as $row) {
                extract($row);
                if ($count != null) {
                    $html.= "  <hr />\n\n";
                }
                $count++;
                $html.= '  <h2><a class="n" href="' . $use . '.php?a=' . $idArticle . '">' . html($headline) . "</a></h2>\n\n";
                $html.= '  <p class="s"><a href="' . $use . '.php?a=' . $idArticle . '">';
                if ($thumbnailImageWidth != null) {
                    $html.= '<img class="fr b" src="' . $imagePath . '?i=' . muddle($idArticle) . 't" width="' . $thumbnailImageWidth . '" height="' . $thumbnailImageHeight . '" alt="">';
                }
                $html.= '</a>' . html($summary) . "</p>\n";
                //
                // Modify for different uses, no include for subscribers
                //
                if (isset($editorView)) {
                    if ($use == 'edit') {
                        include $includesPath . '/displayEdit.php';
                    } elseif ($use == 'published') {
                        include $includesPath . '/displayPublished.php';
                    }
                }
            }
        }
        $dbh = null;
    }
    $dbhSection = null;
}
echo $html;
?>