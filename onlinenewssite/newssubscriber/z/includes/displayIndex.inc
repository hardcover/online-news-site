<?php
/**
 * Displays the article index
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2021 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2021 5 17
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
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
        $stmt->execute([$idSection]);
        $row = $stmt->fetch();
        if ($row) {
            $count = null;
            //
            // Omit the first section divider for subscribers
            //
            if ($sortOrderSection === '1' and is_null($editorView)) {
                // Do nothing
            } else {
                $html.= "\n" . '      <h4>' . $section . "</h4>\n\n";
            }
            //
            // Pull all articles in editing, by date range in published
            //
            if (is_null($editorView)) {
                $stmt = $dbh->prepare('SELECT idArticle, sortOrderArticle, headline, standfirst, summary, thumbnailImageWidth, photoName FROM articles WHERE idSection = ? AND publicationDate <= "' . $datePost . '" AND "' . $datePost . '" <= endDate ORDER BY sortOrderArticle');
            } else {
                $stmt = $dbh->prepare('SELECT idArticle, publicationDate, headline, summary, thumbnailImageWidth, thumbnailImageHeight, photoName FROM articles WHERE idSection = ? ORDER BY sortOrderArticle');
            }
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$idSection]);
            foreach ($stmt as $row) {
                extract($row);
                if (isset($count)) {
                    $html.= "      <hr />\n\n";
                }
                $count++;
                $html.= '      <h2><a class="n" href="' . $use . '.php?a=' . $idArticle . '">' . html($headline) . "</a></h2>\n\n";
                if (!empty($standfirst)) {
                    $html.= '      <h5>' . html($standfirst) . "</h5>\n\n";
                }
                if (!empty($thumbnailImageWidth)) {
                    $html.= '      <p><a href="' . $use . '.php?a=' . $idArticle . '">';
                    if ($photoName === 'third') {
                        $html.= '<img class="t b mb" src="' . $imagePath . '?i=' . muddle($idArticle) . 'h" alt="">';
                    } else {
                        $html.= '<img class="lead l b" src="' . $imagePath . '?i=' . muddle($idArticle) . 'h" alt="">';
                    }
                    $html.= '</a>' . html($summary) . "</p>\n";
                } else {
                    if (!empty($summary)) {
                        $html.= '      <p>' . html($summary) . "</p>\n";
                    }
                }
            }
            //
            // Modify for different uses, no include for subscribers
            //
            if (isset($editorView)) {
                if ($use === 'edit') {
                    include $includesPath . '/displayEdit.php';
                } elseif ($use === 'published') {
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