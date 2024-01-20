<?php
/**
 * Displays the article index
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2024 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2024 01 19
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
$html = null;
if (isset($_GET['a'])) {
    //
    // Article view displays an entire single article
    //
    include $includesPath . '/subscriber/displayArticle.inc';
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
            //
            // Omit the first section divider for subscribers
            //
            if (strval($sortOrderSection) === '1' and is_null($editorView)) {
                //
            } else {
                $html.= "\n" . '    <hr>' . "\n";
                $html.= '    <h2 class="section">' . $section . "</h2>\n\n";
            }
            //
            // Pull all articles in editing, by date range in published
            //
            $i = 0;
            if (is_null($editorView)) {
                $stmt = $dbh->prepare('SELECT idArticle, sortOrderArticle, headline, standfirst, summary, thumbnailImageWidth, photoName FROM articles WHERE idSection = ? AND publicationDate <= "' . $datePost . '" AND "' . $datePost . '" <= endDate ORDER BY sortOrderArticle');
            } else {
                $stmt = $dbh->prepare('SELECT idArticle, publicationDate, headline, summary, thumbnailImageWidth, thumbnailImageHeight, photoName FROM articles WHERE idSection = ? ORDER BY sortOrderArticle');
            }
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$idSection]);
            foreach ($stmt as $row) {
                extract($row);
                $i++;
                if ($i === 1) {
                    $html.= '    <div class="row">' . "\n";
                    $html.= '      <div class="left">' . "\n\n";
                } else {
                    $html.= "\n" . '      <div class="right">' . "\n";
                }
                $html.= '        <h2><a class="n" href="' . $use . '.php?a=' . $idArticle . '">' . html($headline) . "</a></h2>\n\n";
                if (!empty($standfirst)) {
                    $html.= '        <h5>' . html($standfirst) . "</h5>\n\n";
                }
                if (!empty($thumbnailImageWidth)) {
                    if ($photoName === 'third') {
                        $html.= '        <p><a href="' . $use . '.php?a=' . $idArticle . '"><img class="third border mb" src="' . $imagePath . '?i=' . muddle($idArticle) . 'h" alt=""></a></p>' . "\n";
                    } else {
                        $html.= '        <p><a href="' . $use . '.php?a=' . $idArticle . '"><img class="lead b mb" src="' . $imagePath . '?i=' . muddle($idArticle) . 'h" alt=""></a></p>' . "\n";
                    }
                    $html.= '        <p>' . html($summary) . "</p>\n";
                } else {
                    if (!empty($summary)) {
                        $html.= '        <p>' . html($summary) . "</p>\n";
                    }
                }
                $html.= '      </div>' . "\n";
                $j = $i;
                if ($i === 2) {
                    $html.= '    </div>' . "\n\n";
                    $i = 0;
                }
            }
            if ($j === 1) {
                $html.= '    </div>' . "\n";
            }
            //
            // Modify for different uses, no include for subscribers
            //
            if (isset($editorView)) {
                if ($use === 'edit') {
                    include $includesPath . '/subscriber/displayEdit.php';
                } elseif ($use === 'published') {
                    include $includesPath . '/subscriber/displayPublished.php';
                }
            }
        }
        $dbh = null;
    }
    $dbhSection = null;
    //$html.= '    </section>' . "\n";
}
echo $html;
?>
