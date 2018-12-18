<?php
/**
 * Create the subscriber databases on the first run
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2018 12 18
 * @link      https://hardcoverwebdesign.com/
 * @link      https://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
$dbh = new PDO($dbSubscribersNew);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "users" ("idUser" INTEGER PRIMARY KEY, "email", "payerEmail", "payerFirstName", "payerLastName", "ipAddress", "verify", "verified" INTEGER, "time" INTEGER, "pass", "payStatus" INTEGER, "paid", "paymentDate", "note", "contributor" INTEGER, "classifiedOnly" INTEGER, "deliver" INTEGER, "deliver2" INTEGER, "deliveryAddress", "dCityRegionPostal", "billingAddress", "bCityRegionPostal", "soa" INTEGER, "evolve", "expand", "extend")');
$dbh = null;
?>
