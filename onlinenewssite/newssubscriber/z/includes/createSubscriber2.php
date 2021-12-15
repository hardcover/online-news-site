<?php
/**
 * Create the subscriber databases on the first run
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2021 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2021 12 15
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
$dbh = new PDO($dbSubscribersNew);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "users" ("idUser" INTEGER PRIMARY KEY, "email", "payerEmail", "payerFirstName", "payerLastName", "ipAddress", "verify", "verified" INTEGER, "time" INTEGER, "pass", "payStatus" INTEGER, "paid", "paymentDate", "note", "contributor" INTEGER, "classifiedOnly" INTEGER, "deliver" INTEGER, "deliver2" INTEGER, "deliveryAddress", "dCityRegionPostal", "billingAddress", "bCityRegionPostal", "soa" INTEGER, "evolve", "expand", "extend")');
$dbh = null;
?>
