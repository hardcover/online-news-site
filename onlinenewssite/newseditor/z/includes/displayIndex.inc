<?php
/**
 * Summary of articles in edit
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
//
// Variables
//
$html = null;
if ($use === 'edit') {
    $database = $dbEdit;
    $database2 = $dbEdit2;
    $imagePath = 'imagee.php';
    $imagePath2 = 'imagee2.php';
} elseif ($use === 'published') {
    $database = $dbPublished;
    $database2 = $dbPublished2;
    $imagePath = 'imagep.php';
    $imagePath2 = 'imagep2.php';
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
        $stmt->execute([$idSection]);
        $row = $stmt->fetch();
        if ($row) {
            $count = null;
            $html.= "\n" . '  <h4>' . $section . "</h4>\n\n";
            if (is_null($editorView)) {
                $stmt = $dbh->prepare('SELECT idArticle, survey, headline, summary, thumbnailImageWidth, thumbnailImageHeight FROM articles WHERE idSection = ? AND publicationDate <= "' . $datePost . '" AND "' . $datePost . '" <= endDate ORDER BY sortOrderArticle');
            } else {
                $stmt = $dbh->prepare('SELECT idArticle, survey, publicationDate, headline, summary, thumbnailImageWidth, thumbnailImageHeight FROM articles WHERE idSection = ? ORDER BY sortOrderArticle');
            }
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$idSection]);
            foreach ($stmt as $row) {
                extract($row);
                if (isset($count)) {
                    $html.= "  <hr />\n\n";
                }
                $count++;
                $html.= '  <h2><a class="n" href="' . $use . '.php?a=' . $idArticle . '">' . html($headline) . "</a></h2>\n\n";
                if (!empty($summary)) {
                    $html.= '  <p class="s"><a href="' . $use . '.php?a=' . $idArticle . '">';
                    if (!empty($thumbnailImageWidth)) {
                        $html.= '<img class="fr b" src="' . $imagePath . '?i=' . muddle($idArticle) . 't" width="' . $thumbnailImageWidth . '" height="' . $thumbnailImageHeight . '" alt="">';
                    }
                    $summary = str_replace('*', '', $summary);
                    $html.= '</a>' . html($summary) . "</p>\n";
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
        }
        $dbh = null;
    }
    $dbhSection = null;
}
echo $html;
?>