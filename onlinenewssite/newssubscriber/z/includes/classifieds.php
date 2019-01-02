<?php
/**
 * Classifieds on the subscriber site
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2019 01 02
 * @link      https://hardcoverwebdesign.com/
 * @link      https://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
echo '      <h1><a href="' . $uri . '?m=classified-ads">Classified ads</a></h1>' . "\n\n";
echo '      <p><a href="' . $uri . '?m=place-classified">Place a classified ad</a>.</p>' . "\n\n";
echo "      <hr />\n\n";
if (isset($_GET['s'])) {
    //
    // List the ads for the selected subsection
    //
    $idSubsection = filter_var($_GET['s'], FILTER_VALIDATE_INT);
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('SELECT subsection, parentID FROM subsections WHERE idSubsection=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idSubsection]);
    $row = $stmt->fetch();
    extract($row);
    $stmt = $dbh->prepare('SELECT section FROM sections WHERE idSection=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$parentId]);
    $row = $stmt->fetch();
    extract($row);
    echo '      <h3>' . $section . ', ' . $subsection . "</h3>\n\n";
    $stmt = $dbh->prepare('SELECT idAd, title FROM ads WHERE categoryId=? ORDER BY title');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idSubsection]);
    foreach ($stmt as $row) {
        extract($row);
        echo '      <p><a href="' . $uri . '?m=classified-ads&amp;c=' . $idAd . '">' . $title . "</a></p>\n\n";
    }
    $dbh = null;
} elseif (!isset($_GET['c'])) {
    //
    // List all ads when there are less than 99
    //
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->query('SELECT count(*) FROM ads');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row['count(*)'] < 99) {
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->prepare('DELETE FROM ads WHERE review < ?');
        $stmt->execute([$today]);
        $categoryIdPrior = null;
        $stmt = $dbh->query('SELECT idAd, title, categoryId FROM ads ORDER BY categoryId, title');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            extract($row);
            if ($categoryId != $categoryIdPrior) {
                $stmt = $dbh->prepare('SELECT parentId, subsection FROM subsections WHERE idSubsection=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$categoryId]);
                $row = $stmt->fetch();
                extract($row);
                $stmt = $dbh->prepare('SELECT section FROM sections WHERE idSection=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$parentId]);
                $row = $stmt->fetch();
                extract($row);
                echo '      <h3>' . $section . ', ' . $subsection . "</h3>\n\n";
                $categoryIdPrior = $categoryId;
            }
            echo '      <p><a href="' . $uri . '?m=classified-ads&amp;c=' . $idAd . '">' . $title . "</a></p>\n\n";
        }
        $dbh = null;
    } else {
        //
        // List the sections when there are more than 99 ads
        //
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->query('SELECT idSection, section FROM sections ORDER BY sortOrderSection');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            extract($row);
            echo '      <p><br />' . "\n    " . html($section) . "</p>\n\n";
            $stmt = $dbh->prepare('SELECT idSubsection, subsection FROM subsections WHERE parentId=? ORDER BY sortPrioritySubSection');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$idSection]);
            foreach ($stmt as $row) {
                extract($row);
                $stmt = $dbh->prepare('SELECT count(*) FROM ads WHERE categoryId=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$idSubsection]);
                $row = $stmt->fetch();
                if ($row['count(*)'] > 0) {
                    $count = ' (' . $row['count(*)'] . ')';
                } else {
                    $count = null;
                }
                $dbRowCount = $row['count(*)'];
                echo '      <blockquote><a href="' . $uri . '?m=classified-ads&amp;s=' . $idSubsection . '">'. html($subsection) . $count . "</a></blockquote>\n";
            }
            echo "\n";
        }
        $dbh = null;
    }
} else {
    //
    // List the specified ad
    //
    $idAd = filter_var($_GET['c'], FILTER_VALIDATE_INT);
    $dbh = new PDO($dbClassifieds);
    $stmt = $dbh->prepare('SELECT email, title, description, categoryId, review, startDate, duration, photos FROM ads WHERE idAd=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idAd]);
    $row = $stmt->fetch();
    if ($row) {
        extract($row);
        $photos = json_decode($photos, true);
        $stmt = $dbh->prepare('SELECT parentId, subsection FROM subsections WHERE idSubsection=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$categoryId]);
        $row = $stmt->fetch();
        extract($row);
        $stmt = $dbh->prepare('SELECT section FROM sections WHERE idSection=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$parentId]);
        $row = $stmt->fetch();
        extract($row);
        echo '      <h5>' . $section . ', <a href="' . $uri . '?m=classified-ads&amp;s=' . $categoryId . '">'. html($subsection) . "</a></h5>\n\n";
        echo '      <h2>' . $title . "</h2>\n\n";
        echo "      <p><br />\n    " . $description . "</p>\n\n";
        $i = null;
        foreach ($photos as $photo) {
            $i++;
            if ($photo == 1) {
                echo '      <p><img class="w b" src="imagec.php?i=' . muddle($idAd) . $i . '" alt="" /></p>' . "\n";
            }
        }
    }
    $dbh = null;
}
?>