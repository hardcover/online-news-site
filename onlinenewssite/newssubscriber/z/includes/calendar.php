<?php
/**
 * For authorized article contributions
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2018 09 28
 * @link      https://hardcoverwebdesign.com/
 * @link      https://online-news-site.com/
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
    $stmt->execute([$selectDate]);
    $row = $stmt->fetch();
    if (isset($row['description'])) {
        $description.= '    ' . Parsedown::instance()->parse($row['description']) . "\n";
    }
    $stmt = $dbh->prepare('SELECT description FROM annualDayOfWeek WHERE date=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$weekSelect . ' ' . $dayOfTheWeekSelect . ' of ' . $monthSelect]);
    $row = $stmt->fetch();
    if (isset($row['description'])) {
        $description.= '    ' . Parsedown::instance()->parse($row['description']) . "\n";
    }
    $stmt = $dbh->prepare('SELECT description FROM annual WHERE date=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([substr($selectDate, -5)]);
    $row = $stmt->fetch();
    if (isset($row['description'])) {
        $description.= '    ' . Parsedown::instance()->parse($row['description']) . "\n";
    }
    $stmt = $dbh->prepare('SELECT description FROM monthlyDayOfWeek WHERE date=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$weekSelect . ' ' . $dayOfTheWeekSelect]);
    $row = $stmt->fetch();
    if (isset($row['description'])) {
        $description.= '    ' . Parsedown::instance()->parse($row['description']) . "\n";
    }
    $stmt = $dbh->prepare('SELECT description FROM weeklyDayOfWeek WHERE date=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$dayOfTheWeekSelect]);
    $row = $stmt->fetch();
    if (isset($row['description'])) {
        $description.= '    ' . Parsedown::instance()->parse($row['description']) . "\n";
    }
    $dbh = null;
    if (isset($description)) {
        echo '    <h3><br />' . "\n";
        echo '    ' . $dayOfTheWeekSelect . ', ' . $monthSelect . ' ' . $dayOfTheMonthSelect . ', ' . $yearSelect . "</h3>\n";
        echo $description . "\n";
    }
    $selectTime = $selectTime + 86400;
}
$dbh = new PDO($dbCalendar);
$stmt = $dbh->query('SELECT description FROM note');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$dbh = null;
if (!empty($row['description'])) {
    echo '    <p><br />' . "\n";
    echo '    <span>Notes' . "</span><br />\n";
    echo '    ' . Parsedown::instance()->parse($row['description']) . "</p>\n\n";
}
?>
