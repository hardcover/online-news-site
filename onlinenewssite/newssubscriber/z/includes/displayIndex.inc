<?php
/**
 * Displays the article index
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
$html = null;
if (isset($_GET['a'])) {
    //
    // Article view displays an entire single article
    //
    include $includesPath . '/displayArticle.inc';
    //
    // Index view displays a list of all articles with a summary of each article
    //
} else {
    $dbhSection = new PDO($dbSettings);
    $stmt = $dbhSection->query('SELECT idSection, section, sortOrderSection FROM sections ORDER BY sortOrderSection');
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
            //
            // Omit the first section divider for subscribers
            //
            if ($sortOrderSection == 1 and is_null($editorView)) {
                // Do nothing $html.= '    <p><br /></p>' . "\n\n";
            } else {
                $html.= "\n" . '    <h4>' . $section . "</h4>\n\n";
            }
            //
            // Pull all articles in editing, by date range in published
            //
            if (is_null($editorView)) {
                $stmt = $dbh->query('SELECT idArticle, sortOrderArticle, headline, summary, thumbnailImageWidth, thumbnailImageHeight FROM articles WHERE idSection = ? AND publicationDate <= "' . $datePost . '" AND "' . $datePost . '" <= endDate ORDER BY sortOrderArticle');
            } else {
                $stmt = $dbh->query('SELECT idArticle, publicationDate, headline, summary, thumbnailImageWidth, thumbnailImageHeight FROM articles WHERE idSection = ? ORDER BY sortOrderArticle');
            }
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute(array($idSection));
            foreach ($stmt as $row) {
                extract($row);
                if ($count != null) {
                    $html.= "    <hr />\n\n";
                }
                $count++;
                $html.= '    <h2><a class="n" href="' . $use . '.php?a=' . $idArticle . '">' . html($headline) . "</a></h2>\n\n";
                //
                // Format the photograph for the lead article differently for subscribers
                //
                $summary = str_replace('*', '', $summary);
                if ($sortOrderSection == 1 and $count == 1) {
                    if ($thumbnailImageWidth != null) {
                        $html.= '    <p>' . "\n" . '    <a href="' . $use . '.php?a=' . $idArticle . '">';
                        $html.= '<img class="lead b" src="' . $imagePath . '?i=' . muddle($idArticle) . 'h" alt="">';
                        $html.= '</a>' . html($summary) . "</p>\n";
                    } else {
                        if ($summary != '') {
                            $html.= '    <p>' . "\n" . html($summary) . "</p>\n";
                        }
                    }
                } else {
                    if ($thumbnailImageWidth != null) {
                        $html.= '    <p class="s"><a href="' . $use . '.php?a=' . $idArticle . '">';
                        $html.= '<img class="fr b" src="' . $imagePath . '?i=' . muddle($idArticle) . 't" width="' . $thumbnailImageWidth . '" height="' . $thumbnailImageHeight . '" alt="">';
                        $html.= '</a>' . html($summary) . "</p>\n";
                    } else {
                        if ($summary != '') {
                            $html.= '    <p class="s">' . html($summary) . "</p>\n";
                        }
                    }
                }
            }
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
        $dbh = null;
    }
    $dbhSection = null;
}
echo $html;
?>