<?php
/**
 * Cron daily after business hours to update the sitemap files
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Online News <useTheContactForm@onlinenewssite.com>
 * @copyright 2025 Online News
 * @license   https://onlinenewssite.com/license.html
 * @version   2025 05 12
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/onlinenewsllc/online-news-site
 */
//
// Variables
//
$paths = file('sitemapsPath');
$sitemapsPath = trim($paths['0']);
$uri = trim($paths['1']);
require $sitemapsPath . '/editor/z/system/configuration.php';
require '../editor/common.php';
$dbArchive = 'sqlite:../databases/archive.sqlite';
$dbPublished =  'sqlite:../databases/published.sqlite';
$dbSettings =  'sqlite:../databases/settings.sqlite';
//
// sitemap.xml
//
$dbh = new PDO($dbSettings);
$stmt = $dbh->prepare('SELECT name, description FROM names WHERE idName=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([1]);
$row = $stmt->fetch();
$dbh = null;
//
if ($row) {
    extract($row);
} else {
    $name = null;
    $description = null;
}
//
// Determine the number of needed sitemap files
//
$dbh = new PDO($dbArchive);
$stmt = $dbh->query('SELECT count(*) FROM articles');
$stmt->setFetchMode(PDO::FETCH_NUM);
$row = $stmt->fetch();
$dbh = null;
if ($row['0'] > 49996) {
    //
    // Write when there are multiple sitemaps
    //
    $numOfSiteMaps = intval(($row['0'] + 25000) / 49996);
    $fp = fopen($sitemapsPath . '/sitemap_index.xml', 'w');
    fwrite($fp, utf8('<?xml version="1.0" encoding="UTF-8"?>' . "\n"));
    fwrite($fp, utf8('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"));
    $mapCount = null;
    while ($mapCount < $numOfSiteMaps) {
        $mapCount++;
        fwrite($fp, utf8('  <sitemap><loc>' . $uri . 'sitemap' . sprintf('%02d', $mapCount) . '.xml</loc><lastmod>' . $today . '</lastmod></sitemap>' . "\n"));
    }
    fwrite($fp, utf8('</sitemapindex>' . "\n"));
    fclose($fp);
    $fp = null;
    $mapCount = 1;
    $lineCount = 2;
    $fp = fopen($sitemapsPath . '/sitemap' . sprintf('%02d', $mapCount) . '.xml', 'w');
    fwrite($fp, utf8('<?xml version="1.0" encoding="UTF-8"?>' . "\n"));
    fwrite($fp, utf8('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"));
    fwrite($fp, utf8('  <url><loc>' . $uri . '</loc><priority>1.0</priority></url>' . "\n"));
    $dbh = new PDO($dbPublished);
    $stmt = $dbh->query('SELECT idArticle, publicationDate, headline FROM articles ORDER BY publicationDate DESC, idArticle DESC');
    $stmt->setFetchMode(PDO::FETCH_NUM);
    foreach ($stmt as $row) {
        $lineCount++;
        urlLoc($uri, $row);
    }
    $dbh = null;
    $dbh = new PDO($dbArchive);
    $stmt = $dbh->query('SELECT idArticle, publicationDate, headline FROM articles WHERE headline IS NOT NULL ORDER BY publicationDate DESC, rowid DESC');
    $stmt->setFetchMode(PDO::FETCH_NUM);
    foreach ($stmt as $row) {
        $lineCount++;
        urlLoc($uri, $row);
        if (is_int($lineCount / 49996)) {
            $mapCount++;
            fwrite($fp, utf8('</urlset>' . "\n"));
            fclose($fp);
            $fp = null;
            $fp = fopen($sitemapsPath . '/sitemap' . sprintf('%02d', $mapCount) . '.xml', 'w');
            fwrite($fp, utf8('<?xml version="1.0" encoding="UTF-8"?>' . "\n"));
            fwrite($fp, utf8('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"));
        }
    }
    $dbh = null;
    fwrite($fp, utf8('</urlset>' . "\n"));
    fclose($fp);
    $fp = null;
} else {
    //
    // Write when there is only one sitemap
    //
    $fp = fopen($sitemapsPath . '/sitemap.xml', 'w');
    fwrite($fp, utf8('<?xml version="1.0" encoding="UTF-8"?>' . "\n"));
    fwrite($fp, utf8('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"));
    fwrite($fp, utf8('  <url><loc>' . $uri . '</loc></url>' . "\n"));
    $dbh = new PDO($dbPublished);
    $stmt = $dbh->query('SELECT idArticle, publicationDate, headline FROM articles ORDER BY publicationDate DESC, idArticle DESC');
    $stmt->setFetchMode(PDO::FETCH_NUM);
    foreach ($stmt as $row) {
        urlLoc($uri, $row);
    }
    $dbh = null;
    $dbh = new PDO($dbArchive);
    $stmt = $dbh->query('SELECT idArticle, publicationDate, headline FROM articles WHERE headline IS NOT NULL ORDER BY publicationDate DESC, rowid DESC');
    $stmt->setFetchMode(PDO::FETCH_NUM);
    foreach ($stmt as $row) {
        urlLoc($uri, $row);
    }
    $dbh = null;
    fwrite($fp, utf8('</urlset>' . "\n"));
    fclose($fp);
    $fp = null;
}
//
// sitemap-news.xml
//
$twoDaysAgo = date("Y-m-d", time() - 172800);
$dbh = new PDO($dbSettings);
$stmt = $dbh->prepare('SELECT name FROM names WHERE idName=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([1]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    extract($row);
} else {
    $name = '';
}
//
// Begin the sitemap-news.xml file
//
$fp = fopen($sitemapsPath . '/sitemap-news.xml', 'w');
fwrite($fp, utf8('<?xml version="1.0" encoding="UTF-8"?>' . "\n"));
fwrite($fp, utf8('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n"));
fwrite($fp, utf8('        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n"));
//
// Add articles
//
$dbhSection = new PDO($dbSettings);
$stmt = $dbhSection->query('SELECT idSection, section, sortOrderSection FROM sections ORDER BY sortOrderSection');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    extract($row);
    $dbh = new PDO($dbPublished);
    $stmt = $dbh->prepare('SELECT idSection FROM articles WHERE idSection=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idSection]);
    $row = $stmt->fetch();
    if ($row) {
        $stmt = $dbh->prepare('SELECT idArticle, publicationTime, survey, keywords, headline, text FROM articles WHERE idSection = ? AND publicationDate <= "' . $today . '" AND publicationDate >= "' . $twoDaysAgo . '" ORDER BY sortOrderArticle');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idSection]);
        foreach ($stmt as $row) {
            extract($row);
            if ($survey !== strval(1) and !empty($text) and strpos($text, '<') === false) {
                $h = preg_replace('/\%/', ' percentage', $headline);
                $h = preg_replace('/\@/', ' at ', $h);
                $h = preg_replace('/\&/', ' and ', $h);
                $h = preg_replace('/\s[\s]+/', '-', $h);
                $h = preg_replace('/[\s\W]+/', '-', $h);
                $h = preg_replace('/^[\-]+/', '', $h);
                $h = preg_replace('/[\-]+$/', '', $h);
                $headlineSEO = mb_strtolower($h);
                //
                $str = str_replace('&', '&amp;', $headline);
                $str = str_replace("\'", '&apos;', $str);
                $str = str_replace('"', '&quot;', $str);
                $str = str_replace('>', '&gt;', $str);
                $headline = str_replace('<', '&lt;', $str);
                //
                fwrite($fp, utf8('  <url>' . "\n"));
                fwrite($fp, utf8('    <loc>' . $uri . '?a=' . $idArticle . '+' . $headlineSEO . '</loc>' . "\n"));
                fwrite($fp, utf8('    <news:news>' . "\n"));
                fwrite($fp, utf8('      <news:publication>' . "\n"));
                fwrite($fp, utf8('        <news:name>' . $name . '</news:name>' . "\n"));
                fwrite($fp, utf8('        <news:language>en</news:language>' . "\n"));
                fwrite($fp, utf8('      </news:publication>' . "\n"));
                if (!empty($genre)) {
                    fwrite($fp, utf8('      <news:genres>' . $genre . '</news:genres>' . "\n"));
                }
                fwrite($fp, utf8('      <news:publication_date>' . date(DATE_W3C, $publicationTime) . '</news:publication_date>' . "\n"));
                fwrite($fp, utf8('      <news:title>' . $headline . '</news:title>' . "\n"));
                if (!empty($keywords)) {
                    fwrite($fp, utf8('      <news:keywords>' . $keywords . '</news:keywords>' . "\n"));
                }
                fwrite($fp, utf8('    </news:news>' . "\n"));
                fwrite($fp, utf8('  </url>' . "\n"));
            }
        }
    }
    $dbh = null;
}
$dbhSection = null;
fwrite($fp, utf8('</urlset>' . "\n"));
fclose($fp);
$fp = null;
/**
 * Function to write a <url><loc> line </loc></url> to the sitemap.xml file
 *
 * @param string $uri  The site URI
 * @param array  $page The article ID and headline
 *
 * @return Nothing
 */
function urlLoc($uri, $page)
{
    global $fp;
    $h = preg_replace('/\%/', ' percentage', $page['2']);
    $h = preg_replace('/\@/', ' at ', $h);
    $h = preg_replace('/\&/', ' and ', $h);
    $h = preg_replace('/\s[\s]+/', '-', $h);
    $h = preg_replace('/[\s\W]+/', '-', $h);
    $h = preg_replace('/^[\-]+/', '', $h);
    $h = preg_replace('/[\-]+$/', '', $h);
    $headline = mb_strtolower($h);
    fwrite($fp, utf8('  <url><loc>' . $uri . '?a=' . $page['0'] . '+' . $headline . '</loc><lastmod>' . $page[1] . '</lastmod></url>' . "\n"));
}
?>
