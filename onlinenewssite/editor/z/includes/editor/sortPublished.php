<?php
/**
 * Sets the sort order for published articles
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
$dbh = new PDO($dbPublished);
$stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE idArticle=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$idArticle]);
$row = $stmt->fetch();
if ($row) {
    $stmt = $dbh->prepare('SELECT idSection, sortOrderArticle FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idArticle]);
    $row = $stmt->fetch();
    if ($row) {
        extract($row);
        //
        // Establish the change in sort order, if any
        //
        if (isset($_POST['up'])) {
            $sortOrderArticleNew = $sortOrderArticle > 1 ? $sortOrderArticle - 1 : $sortOrderArticle;
        } elseif (isset($_POST['down'])) {
            $sortOrderArticleNew = $sortOrderArticle + 2;
        } else {
            $sortOrderArticleNew = $sortOrderArticle;
        }
        $stmt = $dbh->prepare('UPDATE articles SET sortOrderArticle=?, sortPriority=? WHERE idArticle=?');
        $stmt->execute([$sortOrderArticleNew, '1', $idArticle]);
    }
    $stmt = $dbh->prepare('SELECT idArticle, sortOrderArticle FROM articles WHERE idSection=? ORDER BY sortOrderArticle, sortPriority');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idSection]);
    $count = null;
    foreach ($stmt as $row) {
        extract($row);
        $count++;
        $stmt = $dbh->prepare('UPDATE articles SET sortOrderArticle=? WHERE idArticle=?');
        $stmt->execute([$count, $idArticle]);
    }
    //
    // Restore presort settings
    //
    $stmt = $dbh->prepare('UPDATE articles SET sortPriority=? WHERE idSection=?');
    $stmt->execute([2, $idSection]);
}
$dbh = null;
?>
