<?php
/**
 * For authorized article contributions
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2016 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *.@license   http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2016-10-16
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
echo "    <h1>Calendar for the next month</h1>\n\n";
//
// Select events for the next 31 days from now
//
$selectTime = time();
for ($i = 0; $i < 31; $i++) {
    $description = null;
    $selectDate = date("Y-m-d", $selectTime);
    $dayOfTheMonthSelect = date("j", strtotime($selectDate));
    $dayOfTheWeekSelect = date("l", strtotime($selectDate));
    $monthSelect = date("F", strtotime($selectDate));
    $yearSelect = date("Y", strtotime($selectDate));
    $firstSelect = date("Y-m-d", strtotime('first ' . $dayOfTheWeekSelect . ' of ' . $monthSelect . ' ' . $yearSelect));
    $secondSelect = date("Y-m-d", strtotime('second ' . $dayOfTheWeekSelect . ' of ' . $monthSelect . ' ' . $yearSelect));
    $thirdSelect = date("Y-m-d", strtotime('third ' . $dayOfTheWeekSelect . ' of ' . $monthSelect . ' ' . $yearSelect));
    $fourthSelect = date("Y-m-d", strtotime('fourth ' . $dayOfTheWeekSelect . ' of ' . $monthSelect . ' ' . $yearSelect));
    if ($firstSelect == $selectDate) {
        $weekSelect = 'first';
    } elseif ($secondSelect == $selectDate) {
        $weekSelect = 'second';
    } elseif ($thirdSelect == $selectDate) {
        $weekSelect = 'third';
    } elseif ($fourthSelect == $selectDate) {
        $weekSelect = 'fourth';
    } else {
        $weekSelect = null;
    }
    $dbh = new PDO($dbCalendar);
    $stmt = $dbh->prepare('SELECT description FROM oneTimeEvent WHERE date=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($selectDate));
    $row = $stmt->fetch();
    if (isset($row['description'])) {
        $description.= $row['description'] . ' ';
    }
    $stmt = $dbh->prepare('SELECT description FROM monthlyDayOfWeek WHERE date=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($weekSelect . ' ' . $dayOfTheWeekSelect));
    $row = $stmt->fetch();
    if (isset($row['description'])) {
        $description.= $row['description'] . ' ';
    }
    $stmt = $dbh->prepare('SELECT description FROM annual WHERE date=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array(substr($selectDate, -5)));
    $row = $stmt->fetch();
    if (isset($row['description'])) {
        $description.= $row['description'] . ' ';
    }
    $stmt = $dbh->prepare('SELECT description FROM annualDayOfWeek WHERE date=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($weekSelect . ' ' . $dayOfTheWeekSelect . ' of ' . $monthSelect));
    $row = $stmt->fetch();
    if (isset($row['description'])) {
        $description.= $row['description'] . ' ';
    }
    $dbh = null;
    if (isset($description)) {
        echo '    <p><br />' . "\n";
        echo '    <span>' . $dayOfTheWeekSelect . ', ' . $monthSelect . ' ' . $dayOfTheMonthSelect . ', ' . $yearSelect . "</span><br />\n";
        echo '    ' . Parsedown::instance()->parse($description) . "</p>\n\n";
    }
    $selectTime = $selectTime + 86400;
}
$dbh = new PDO($dbCalendar);
$stmt = $dbh->query('SELECT description FROM note');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$dbh = null;
if (isset($row['description'])) {
    echo '    <p><br />' . "\n";
    echo '    <span>Notes' . "</span><br />\n";
    echo '    ' . Parsedown::instance()->parse($row['description']) . "</p>\n\n";
}
?>
