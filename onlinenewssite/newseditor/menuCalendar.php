<?php
/**
 * Calendar maintenance
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2018 05 06
 * @link      https://hardcoverwebdesign.com/
 * @link      https://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
session_start();
require 'z/system/configuration.php';
require $includesPath . '/authorization.php';
require $includesPath . '/common.php';
//
// User-group authorization
//
$dbh = new PDO($dbEditors);
$stmt = $dbh->prepare('SELECT userType FROM users WHERE idUser=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$_SESSION['userId']]);
$row = $stmt->fetch();
$dbh = null;
if (empty($row['userType']) or $row['userType'] != 5) {
    include 'logout.php';
    exit;
}
//
// Variables
//
$annualDayOfWeekEdit = null;
$annualDayOfWeekPost = securePost('annualDayOfWeek');
$annualEventEdit = null;
$annualEventPost = securePost('annualEvent');
$datePost = inlinePost('date');
$idAnnualDayOfWeekEdit = null;
$idAnnualEdit = inlinePost('idAnnual');
$idMonthlyDayOfWeekEdit = null;
$idOneTimeEvent = null;
$idOneTimeEventEdit = null;
$idOneTimeEventPost = inlinePost('idOneTimeEvent');
$idWeeklyDayOfWeekEdit = null;
$message = null;
$monthlyDayOfWeekEdit = null;
$monthlyDayOfWeekPost = securePost('monthlyDayOfWeek');
$noteEdit = null;
$notePost = securePost('note');
$oneTimeEventEdit = null;
$oneTimeEventPost = securePost('oneTimeEvent');
$weeklyDayOfWeekEdit = null;
$weeklyDayOfWeekPost = securePost('weeklyDayOfWeek');
//
$remotes = [];
$dbh = new PDO($dbRemote);
$stmt = $dbh->query('SELECT remote FROM remotes');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    $remotes[] = $row['remote'];
}
$dbh = null;
//
// Variables that depend upon $datePost
//
$dayOfTheMonth = date("j", strtotime($datePost));
$dayOfTheWeek = date("l", strtotime($datePost));
$month = date("F", strtotime($datePost));
$year = date("Y", strtotime($datePost));
$first = date("Y-m-d", strtotime('first ' . $dayOfTheWeek . ' of ' . $month . ' ' . $year));
$second = date("Y-m-d", strtotime('second ' . $dayOfTheWeek . ' of ' . $month . ' ' . $year));
$third = date("Y-m-d", strtotime('third ' . $dayOfTheWeek . ' of ' . $month . ' ' . $year));
$fourth = date("Y-m-d", strtotime('fourth ' . $dayOfTheWeek . ' of ' . $month . ' ' . $year));
if ($first == $datePost) {
    $week = 'first';
} elseif ($second == $datePost) {
    $week = 'second';
} elseif ($third == $datePost) {
    $week = 'third';
} elseif ($fourth == $datePost) {
    $week = 'fourth';
} else {
    $week = null;
}
//
// Edit variables
//
$dbh = new PDO($dbCalendar);
$stmt = $dbh->prepare('SELECT idOneTimeEvent, description FROM oneTimeEvent WHERE date=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$datePost]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $idOneTimeEventEdit = $row['idOneTimeEvent'];
    $oneTimeEventEdit = $row['description'];
}
$dbh = new PDO($dbCalendar);
$stmt = $dbh->prepare('SELECT idWeeklyDayOfWeek, description FROM weeklyDayOfWeek WHERE date=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$dayOfTheWeek]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $idWeeklyDayOfWeekEdit = $row['idWeeklyDayOfWeek'];
    $weeklyDayOfWeekEdit = $row['description'];
}
$dbh = new PDO($dbCalendar);
$stmt = $dbh->prepare('SELECT idMonthlyDayOfWeek, description FROM monthlyDayOfWeek WHERE date=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$week . ' ' . $dayOfTheWeek]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $idMonthlyDayOfWeekEdit = $row['idMonthlyDayOfWeek'];
    $monthlyDayOfWeekEdit = $row['description'];
}
$dbh = new PDO($dbCalendar);
$stmt = $dbh->prepare('SELECT idAnnual, description FROM annual WHERE date=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([substr($datePost, -5)]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $idAnnualEdit = $row['idAnnual'];
    $annualEventEdit = $row['description'];
}
$dbh = new PDO($dbCalendar);
$stmt = $dbh->prepare('SELECT idAnnualDayOfWeek, description FROM annualDayOfWeek WHERE date=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$week . ' ' . $dayOfTheWeek . ' of ' . $month]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $idAnnualDayOfWeekEdit = $row['idAnnualDayOfWeek'];
    $annualDayOfWeekEdit = $row['description'];
}
$dbh = new PDO($dbCalendar);
$stmt = $dbh->query('SELECT description FROM note');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $noteEdit = $row['description'];
}
//
// Button: Update
//
if (isset($_POST['update']) and isset($datePost)) {
    //
    // One-time event
    //
    if (isset($idOneTimeEventEdit)) {
        if (empty($oneTimeEventPost)) {
            $dbh = new PDO($dbCalendar);
            $stmt = $dbh->prepare('DELETE FROM oneTimeEvent WHERE idOneTimeEvent=?');
            $stmt->execute([$idOneTimeEventEdit]);
            $dbh = null;
        } else {
            $dbh = new PDO($dbCalendar);
            $stmt = $dbh->prepare('UPDATE oneTimeEvent SET description=? WHERE idOneTimeEvent=?');
            $stmt->execute([$oneTimeEventPost, $idOneTimeEventEdit]);
            $dbh = null;
        }
    } else {
        if (isset($oneTimeEventPost)) {
            $dbh = new PDO($dbCalendar);
            $stmt = $dbh->prepare('INSERT INTO oneTimeEvent (date, description) VALUES (?, ?)');
            $stmt->execute([$datePost, $oneTimeEventPost]);
            $dbh = null;
        }
    }
    $oneTimeEventEdit = $oneTimeEventPost;
    //
    // Weekly event on the same day of the week
    //
    $dateDescription = $dayOfTheWeek;
    if (isset($idWeeklyDayOfWeekEdit)) {
        if (empty($weeklyDayOfWeekPost)) {
            $dbh = new PDO($dbCalendar);
            $stmt = $dbh->prepare('DELETE FROM weeklyDayOfWeek WHERE idWeeklyDayOfWeek=?');
            $stmt->execute([$idWeeklyDayOfWeekEdit]);
            $dbh = null;
        } else {
            $dbh = new PDO($dbCalendar);
            $stmt = $dbh->prepare('UPDATE weeklyDayOfWeek SET description=? WHERE idWeeklyDayOfWeek=?');
            $stmt->execute([$weeklyDayOfWeekPost, $idWeeklyDayOfWeekEdit]);
            $dbh = null;
        }
    } else {
        if (isset($weeklyDayOfWeekPost)) {
            $dbh = new PDO($dbCalendar);
            $stmt = $dbh->prepare('INSERT INTO weeklyDayOfWeek (date, description) VALUES (?, ?)');
            $stmt->execute([$dateDescription, $weeklyDayOfWeekPost]);
            $dbh = null;
        }
    }
    $weeklyDayOfWeekEdit = $weeklyDayOfWeekPost;
    //
    // Monthly event on the same day of the same week
    //
    $dateDescription = $week . ' ' . $dayOfTheWeek;
    if (isset($idMonthlyDayOfWeekEdit)) {
        if (empty($monthlyDayOfWeekPost)) {
            $dbh = new PDO($dbCalendar);
            $stmt = $dbh->prepare('DELETE FROM monthlyDayOfWeek WHERE idMonthlyDayOfWeek=?');
            $stmt->execute([$idMonthlyDayOfWeekEdit]);
            $dbh = null;
        } else {
            $dbh = new PDO($dbCalendar);
            $stmt = $dbh->prepare('UPDATE monthlyDayOfWeek SET description=? WHERE idMonthlyDayOfWeek=?');
            $stmt->execute([$monthlyDayOfWeekPost, $idMonthlyDayOfWeekEdit]);
            $dbh = null;
        }
    } else {
        if (isset($monthlyDayOfWeekPost)) {
            $dbh = new PDO($dbCalendar);
            $stmt = $dbh->prepare('INSERT INTO monthlyDayOfWeek (date, description) VALUES (?, ?)');
            $stmt->execute([$dateDescription, $monthlyDayOfWeekPost]);
            $dbh = null;
        }
    }
    $monthlyDayOfWeekEdit = $monthlyDayOfWeekPost;
    //
    // Annual event on the same date each year
    //
    if (isset($idAnnualEdit)) {
        if (empty($annualEventPost)) {
            $dbh = new PDO($dbCalendar);
            $stmt = $dbh->prepare('DELETE FROM annual WHERE idAnnual=?');
            $stmt->execute([$idAnnualEdit]);
            $dbh = null;
        } else {
            $dbh = new PDO($dbCalendar);
            $stmt = $dbh->prepare('UPDATE annual SET description=? WHERE idAnnual=?');
            $stmt->execute([$annualEventPost, $idAnnualEdit]);
            $dbh = null;
        }
    } else {
        if (isset($annualEventPost)) {
            $dbh = new PDO($dbCalendar);
            $stmt = $dbh->prepare('INSERT INTO annual (date, description) VALUES (?, ?)');
            $stmt->execute([substr($datePost, -5), $annualEventPost]);
            $dbh = null;
        }
    }
    $annualEventEdit = $annualEventPost;
    //
    // Annual event on the same month, week and day of the week
    //
    $dateDescription = $week . ' ' . $dayOfTheWeek . ' of ' . $month;
    if (isset($idAnnualDayOfWeekEdit)) {
        if (empty($annualDayOfWeekPost)) {
            $dbh = new PDO($dbCalendar);
            $stmt = $dbh->prepare('DELETE FROM annualDayOfWeek WHERE idAnnualDayOfWeek=?');
            $stmt->execute([$idAnnualDayOfWeekEdit]);
            $dbh = null;
        } else {
            $dbh = new PDO($dbCalendar);
            $stmt = $dbh->prepare('UPDATE annualDayOfWeek SET description=? WHERE idAnnualDayOfWeek=?');
            $stmt->execute([$annualDayOfWeekPost, $idAnnualDayOfWeekEdit]);
            $dbh = null;
        }
    } else {
        if (isset($annualDayOfWeekPost)) {
            $dbh = new PDO($dbCalendar);
            $stmt = $dbh->prepare('INSERT INTO annualDayOfWeek (date, description) VALUES (?, ?)');
            $stmt->execute([$dateDescription, $annualDayOfWeekPost]);
            $dbh = null;
        }
    }
    $annualDayOfWeekEdit = $annualDayOfWeekPost;
    //
    // Note
    //
    $dbh = new PDO($dbCalendar);
    $stmt = $dbh->query('DELETE FROM note');
    $stmt = $dbh->prepare('INSERT INTO note (description) VALUES (?)');
    $stmt->execute([$notePost]);
    $dbh = null;
    $noteEdit = $notePost;
    //
    // Sync the main database to the remote databases
    //
    $annual = [];
    $annualDayOfWeek = [];
    $monthlyDayOfWeek = [];
    $oneTimeEvent = [];
    $dbh = new PDO($dbCalendar);
    $stmt = $dbh->query('SELECT * FROM annual ORDER BY idAnnual');
    $stmt->setFetchMode(PDO::FETCH_NUM);
    foreach ($stmt as $row) {
        $annual[] = json_encode($row);
    }
    $stmt = $dbh->query('SELECT * FROM annualDayOfWeek ORDER BY idAnnualDayOfWeek');
    $stmt->setFetchMode(PDO::FETCH_NUM);
    foreach ($stmt as $row) {
        $annualDayOfWeek[] = json_encode($row);
    }
    $stmt = $dbh->query('SELECT * FROM weeklyDayOfWeek ORDER BY idWeeklyDayOfWeek');
    $stmt->setFetchMode(PDO::FETCH_NUM);
    foreach ($stmt as $row) {
        $weeklyDayOfWeek[] = json_encode($row);
    }
    $stmt = $dbh->query('SELECT * FROM monthlyDayOfWeek ORDER BY idMonthlyDayOfWeek');
    $stmt->setFetchMode(PDO::FETCH_NUM);
    foreach ($stmt as $row) {
        $monthlyDayOfWeek[] = json_encode($row);
    }
    $stmt = $dbh->query('SELECT * FROM oneTimeEvent ORDER BY idOneTimeEvent');
    $stmt->setFetchMode(PDO::FETCH_NUM);
    foreach ($stmt as $row) {
        $oneTimeEvent[] = json_encode($row);
    }
    $stmt = $dbh->query('SELECT description FROM note');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $row = $stmt->fetch();
    $dbh = null;
    $request = null;
    $response = null;
    $request['task'] = 'calendarSync';
    $request['annual'] = json_encode($annual);
    $request['annualDayOfWeek'] = json_encode($annualDayOfWeek);
    $request['monthlyDayOfWeek'] = json_encode($monthlyDayOfWeek);
    $request['weeklyDayOfWeek'] = json_encode($weeklyDayOfWeek);
    $request['oneTimeEvent'] = json_encode($oneTimeEvent);
    if ($row) {
        $request['note'] = $row['description'];
    }
    foreach ($remotes as $remote) {
        $response = soa($remote . 'z/', $request);
    }
}
//
// Button: Reset
//
if (isset($_POST['reset'])) {
    header('Location: ' . $uri . 'menuCalendar.php');
    exit;
}
//
// HTML
//
require $includesPath . '/header1.inc';
?>
  <title>Calendar maintenance</title>
  <link rel="icon" type="image/png" href="images/favicon.png" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" type="text/css" href="z/jquery-ui.theme.css" />
  <link rel="stylesheet" type="text/css" href="z/jquery-ui.structure.css" />
  <link rel="stylesheet" type="text/css" href="z/base.css" />
  <link rel="stylesheet" type="text/css" media="(max-width: 768px)" href="z/small.css" />
  <link rel="stylesheet" type="text/css" media="(min-width: 768px)" href="z/large.css" />
  <script src="z/jquery.min.js"></script>
  <script src="z/jquery-ui.min.js"></script>
  <script src="z/datepicker.js"></script>
</head>

<?php require $includesPath . '/body.inc'; ?>

  <h4 class="m"><a class="m" href="menu.php">&nbsp;Menu&nbsp;</a><a class="s" href="menuCalendar.php">&nbsp;Calendar&nbsp;</a><a class="m" href="menuPredefine.php">&nbsp;Predefined&nbsp;</a></h4>
<?php echoIfMessage($message); ?>

  <h1 id="waiting">Please wait.</h1>

  <h1><span class="h">Calendar, 53 weeks</span></h1>

<?php
$selectTime = time();
for ($i = 0; $i < 371; $i++) {
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
        $description.= $row['description'] . ' ';
    }
    $stmt = $dbh->prepare('SELECT description FROM weeklyDayOfWeek WHERE date=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$dayOfTheWeekSelect]);
    $row = $stmt->fetch();
    if (isset($row['description'])) {
        $description.= $row['description'] . ' ';
    }
    $stmt = $dbh->prepare('SELECT description FROM monthlyDayOfWeek WHERE date=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$weekSelect . ' ' . $dayOfTheWeekSelect]);
    $row = $stmt->fetch();
    if (isset($row['description'])) {
        $description.= $row['description'] . ' ';
    }
    $stmt = $dbh->prepare('SELECT description FROM annual WHERE date=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([substr($selectDate, -5)]);
    $row = $stmt->fetch();
    if (isset($row['description'])) {
        $description.= $row['description'] . ' ';
    }
    $stmt = $dbh->prepare('SELECT description FROM annualDayOfWeek WHERE date=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$weekSelect . ' ' . $dayOfTheWeekSelect . ' of ' . $monthSelect]);
    $row = $stmt->fetch();
    if (isset($row['description'])) {
        $description.= $row['description'] . ' ';
    }
    $dbh = null;
    if (isset($description)) {
        echo '  <p><span class="p"><span>' . $dayOfTheWeekSelect . ', ' . $monthSelect . ' ' . $dayOfTheMonthSelect . ', ' . $yearSelect . "</span><br />\n";
        echo '  ' . $description . "</span></p>\n\n";
    }
    $selectTime = $selectTime + 86400;
}
$dbh = new PDO($dbCalendar);
$stmt = $dbh->query('SELECT description FROM note');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$row = $stmt->fetch();
$dbh = null;
if (isset($row['description'])) {
    echo '  <p><span class="p"><span>Notes' . "</span><br />\n";
    echo '  ' . $row['description'] . "</span></p>\n\n";
}
?>
  <h1>Calendar maintenance</h1>

  <form class="wait" action="<?php echo $uri; ?>menuCalendar.php" method="post">
<?php
if (empty($datePost)) {
    echo '    <p><label for="date">Date</label><br />' . "\n";
    echo '    <input id="date" name="date" type="text" class="datepicker h" required /></p>' . "\n\n";
    echo '    <p class="b"><input type="submit" value="Select" name="select" class="button" /></p>' . "\n";
} else {
    echo '    <input id="idOneTimeEvent" name="idOneTimeEvent" type="hidden" value="' . $idOneTimeEventEdit . '" /><input id="date" name="date" type="hidden" value="' . $datePost . '" />' . "\n\n";
    echo '    <p><label for="oneTimeEvent">One-time event ' . $dayOfTheWeek . ', ' . $month . ' ' . $dayOfTheMonth . ', ' . $year . ".</label><br />\n";
    echo '    <span class="hl"><textarea id="oneTimeEvent" name="oneTimeEvent" class="h">' . $oneTimeEventEdit . "</textarea></span></p>\n\n";
    //
    echo '    <input id="idWeeklyDayOfWeek" name="idWeeklyDayOfWeek" type="hidden" value="' . $idWeeklyDayOfWeekEdit . '" />' . "\n\n";
    echo '    <p><label for="weeklyDayOfWeek">Weekly event each ' . $dayOfTheWeek . ".</label><br />\n";
    echo '    <span class="hl"><textarea id="weeklyDayOfWeek" name="weeklyDayOfWeek" class="h">' . $weeklyDayOfWeekEdit . "</textarea></span></p>\n\n";
    //
    echo '    <input id="idMonthlyDayOfWeek" name="idMonthlyDayOfWeek" type="hidden" value="' . $idMonthlyDayOfWeekEdit . '" />' . "\n\n";
    echo '    <p><label for="monthlyDayOfWeek">Monthly event each ' . $week . ' ' . $dayOfTheWeek . ".</label><br />\n";
    echo '    <span class="hl"><textarea id="monthlyDayOfWeek" name="monthlyDayOfWeek" class="h">' . $monthlyDayOfWeekEdit . "</textarea></span></p>\n\n";
    //
    echo '    <input id="idAnnual" name="idAnnual" type="hidden" value="' . $idAnnualEdit . '" />' . "\n\n";
    echo '    <p><label for="annualEvent">Annual event each ' . $month . ' ' . $dayOfTheMonth . ".</label><br />\n";
    echo '    <span class="hl"><textarea id="annualEvent" name="annualEvent" class="h">' . $annualEventEdit . "</textarea></span></p>\n\n";
    //
    echo '    <input id="idAnnualDayOfWeek" name="idAnnualDayOfWeek" type="hidden" value="' . $idAnnualDayOfWeekEdit . '" />' . "\n\n";
    echo '    <p><label for="annualDayOfWeek">Annual event each ' . $week . ' ' . $dayOfTheWeek . ' of  ' . $month . ".</label><br />\n";
    echo '    <span class="hl"><textarea id="annualDayOfWeek" name="annualDayOfWeek" class="h">' . $annualDayOfWeekEdit . "</textarea></span></p>\n\n";
    //
    echo '    <p><label for="note">Calendar-bottom note</label><br />' . "\n";
    echo '    <span class="hl"><textarea id="note" name="note" class="h">' . $noteEdit . "</textarea></span></p>\n\n";
    echo '    <p class="b"><input type="submit" class="button" value="Update" name="update" /> <input type="submit" class="button" value="Reset" name="reset" /></p>' . "\n";
}
?>
  </form>
</body>
</html>
