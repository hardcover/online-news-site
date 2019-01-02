<?php
/**
 * Create and edit surveys
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
session_start();
require 'z/system/configuration.php';
require $includesPath . '/authorization.php';
require $includesPath . '/common.php';
require $includesPath . '/parsedown-master/Parsedown.php';
//
// User-group authorization
//
$dbh = new PDO($dbEditors);
$stmt = $dbh->prepare('SELECT userType FROM users WHERE idUser=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$_SESSION['userId']]);
$row = $stmt->fetch();
$dbh = null;
if (empty($row['userType']) or $row['userType'] != 1) {
    include 'logout.php';
    exit;
}
$dbh = new PDO($dbSurvey);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "answers" ("idAnswer" INTEGER PRIMARY KEY, "idArticle" INTEGER, "sortOrder" INTEGER, "answer", "ipAddress", "tally" INTEGER)');
$dbh = null;

require $includesPath . '/syncSurveyAnswers.php';
//
// Variables
//
$answer1Edit = null;
$answer1Post = inlinePost('answer1');
$answer2Edit = null;
$answer2Post = inlinePost('answer2');
$answer3Edit = null;
$answer3Post = inlinePost('answer3');
$answer4Edit = null;
$answer4Post = inlinePost('answer4');
$answer5Edit = null;
$answer5Post = inlinePost('answer5');
$answer6Edit = null;
$answer6Post = inlinePost('answer6');
$answer7Edit = null;
$answer7Post = inlinePost('answer7');
$answer8Edit = null;
$answer8Post = inlinePost('answer8');
$endDateEdit = null;
$endDatePost = inlinePost('endDate');
$idArticleEdit = null;
$idArticlePost = inlinePost('idArticle');
$idSectionEdit = null;
$idSectionPost = inlinePost('idSection');
$publicationDateEdit = null;
$publicationDatePost = inlinePost('publicationDate');
$questionEdit = null;
$questionPost = inlinePost('question');
//
if ($publicationDatePost === $today) {
    $publicationTimePost = time();
} else {
    $publicationTimePost = strtotime($publicationDatePost);
}
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
// Button: Update
//
if (isset($_POST['update'])) {
    //
    // Determine insert or update
    //
    if (isset($idArticlePost)) {
        $idArticleEdit = $idArticlePost;
    } else {
        $dbh = new PDO($dbArticleId);
        $stmt = $dbh->prepare('INSERT INTO articles (headline) VALUES (?)');
        $stmt->execute([null]);
        $idArticle = $dbh->lastInsertId();
        $idArticleEdit = $dbh->lastInsertId();
        $stmt = $dbh->prepare('UPDATE articles SET idArticle=? WHERE rowid=?');
        $stmt->execute([$idArticle, $idArticle]);
        $dbh = null;
        $dbh = new PDO($dbEdit);
        $stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE idArticle=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idArticle]);
        $row = $stmt->fetch();
        if (empty($row)) {
            $stmt = $dbh->prepare('INSERT INTO articles (idArticle) VALUES (?)');
            $stmt->execute([$idArticle]);
        }
        $dbh = null;
    }
    //
    // Update the database
    //
    $dbh = new PDO($dbEdit);
    $stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idArticleEdit]);
    $row = $stmt->fetch();
    if ($row) {
        $stmt = $dbh->prepare('UPDATE articles SET headline=?, publicationDate=?, publicationTime=?, endDate=?, survey=?, idSection=? WHERE idArticle=?');
        $stmt->execute([$questionPost, $publicationDatePost, $publicationTimePost, $endDatePost, 1, $idSectionPost, $idArticleEdit]);
    }
    $dbh = null;
    $dbh = new PDO($dbPublished);
    $stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idArticleEdit]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        $dbh = new PDO($dbPublished);
        $stmt = $dbh->prepare('UPDATE articles SET headline=?, publicationDate=?, publicationTime=?, endDate=?, survey=?, idSection=? WHERE idArticle=?');
        $stmt->execute([$questionPost, $publicationDatePost, $publicationTimePost, $endDatePost, 1, $idSectionPost, $idArticleEdit]);
        $dbh = null;
        //
        // For published surveys, update the survey title on the remote sites
        //
        $archive = null;
        $database = $dbPublished;
        $database2 = $dbPublished2;
        $idArticle = $idArticleEdit;
        include $includesPath . '/addUpdateArticle.php';
    }
    $dbh = new PDO($dbSurvey);
    $stmt = $dbh->query('DELETE FROM answers WHERE answer IS NULL');
    $stmt = $dbh->prepare('DELETE FROM answers WHERE idArticle=?');
    $stmt->execute([$idArticlePost]);
    $stmt = $dbh->prepare('INSERT INTO answers (idArticle, sortOrder, answer) VALUES (?, ?, ?)');
    if (isset($answer1Post)) {
        $stmt->execute([$idArticleEdit, 1, $answer1Post]);
        $answer1Edit = $answer1Post;
    }
    if (isset($answer2Post)) {
        $stmt->execute([$idArticleEdit, 2, $answer2Post]);
        $answer2Edit = $answer2Post;
    }
    if (isset($answer3Post)) {
        $stmt->execute([$idArticleEdit, 3, $answer3Post]);
        $answer3Edit = $answer3Post;
    }
    if (isset($answer4Post)) {
        $stmt->execute([$idArticleEdit, 4, $answer4Post]);
        $answer4Edit = $answer4Post;
    }
    if (isset($answer5Post)) {
        $stmt->execute([$idArticleEdit, 5, $answer5Post]);
        $answer5Edit = $answer5Post;
    }
    if (isset($answer6Post)) {
        $stmt->execute([$idArticleEdit, 6, $answer6Post]);
        $answer6Edit = $answer6Post;
    }
    if (isset($answer7Post)) {
        $stmt->execute([$idArticleEdit, 7, $answer7Post]);
        $answer7Edit = $answer7Post;
    }
    if (isset($answer8Post)) {
        $stmt->execute([$idArticleEdit, 8, $answer8Post]);
        $answer8Edit = $answer8Post;
    }
    $dbh = null;
    //
    // For published surveys, update the survey answers on the remote sites
    //
    $answers = [];
    $dbh = new PDO($dbPublished);
    $stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idArticleEdit]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        $request = null;
        $response = null;
        $request['task'] = 'surveyUpdate';
        $request['archive'] = null;
        $request['idArticle'] = $idArticleEdit;
        $dbh = new PDO($dbSurvey);
        $stmt = $dbh->prepare('SELECT * FROM answers WHERE idArticle=? ORDER BY idAnswer');
        $stmt->setFetchMode(PDO::FETCH_NUM);
        $stmt->execute([$idArticleEdit]);
        foreach ($stmt as $row) {
            $answers[] = json_encode($row);
        }
        $dbh = null;
        $request['answers'] = json_encode($answers);
        foreach ($remotes as $remote) {
            $response = soa($remote . 'z/', $request);
        }
    }
}
//
// Set the edit variables
//
$dbh = new PDO($dbEdit);
$stmt = $dbh->prepare('SELECT publicationDate, endDate, headline, idSection FROM articles WHERE idArticle=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$idArticleEdit]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $endDateEdit = $row['endDate'];
    $idSectionEdit = $row['idSection'];
    $publicationDateEdit = $row['publicationDate'];
    $questionEdit = $row['headline'];

}
$dbh = new PDO($dbPublished);
$stmt = $dbh->prepare('SELECT publicationDate, endDate, headline, idSection FROM articles WHERE idArticle=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$idArticleEdit]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $endDateEdit = $row['endDate'];
    $idSectionEdit = $row['idSection'];
    $publicationDateEdit = $row['publicationDate'];
    $questionEdit = $row['headline'];
}
//
// Button: Edit
//
if (isset($_POST['edit']) and isset($_POST['idArticle'])) {
    $dbh = new PDO($dbEdit);
    $stmt = $dbh->prepare('SELECT idArticle, publicationDate, endDate, headline FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idArticlePost]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        $endDateEdit = $row['endDate'];
        $idArticleEdit = $row['idArticle'];
        $publicationDateEdit = $row['publicationDate'];
        $questionEdit = $row['headline'];
        $dbh = new PDO($dbSurvey);
        $stmt = $dbh->prepare('SELECT sortOrder, answer FROM answers WHERE idArticle=? ORDER BY sortOrder');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idArticlePost]);
        $count = 1;
        foreach ($stmt as $row) {
            if ($row['sortOrder'] == 1) {
                $answer1Edit = $row['answer'];
            }
            if ($row['sortOrder'] == 2) {
                $answer2Edit = $row['answer'];
            }
            if ($row['sortOrder'] == 3) {
                $answer3Edit = $row['answer'];
            }
            if ($row['sortOrder'] == 4) {
                $answer4Edit = $row['answer'];
            }
            if ($row['sortOrder'] == 5) {
                $answer5Edit = $row['answer'];
            }
            if ($row['sortOrder'] == 6) {
                $answer6Edit = $row['answer'];
            }
            if ($row['sortOrder'] == 7) {
                $answer7Edit = $row['answer'];
            }
            if ($row['sortOrder'] == 8) {
                $answer8Edit = $row['answer'];
            }
        }
        $dbh = null;
    } else {
        $dbh = new PDO($dbPublished);
        $stmt = $dbh->prepare('SELECT idArticle, publicationDate, endDate, headline FROM articles WHERE idArticle=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idArticlePost]);
        $row = $stmt->fetch();
        $dbh = null;
        if ($row) {
            $endDateEdit = $row['endDate'];
            $idArticleEdit = $row['idArticle'];
            $publicationDateEdit = $row['publicationDate'];
            $questionEdit = $row['headline'];
            $dbh = new PDO($dbSurvey);
            $stmt = $dbh->prepare('SELECT sortOrder, answer FROM answers WHERE idArticle=? ORDER BY sortOrder');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$idArticlePost]);
            $count = 1;
            foreach ($stmt as $row) {
                if ($row['sortOrder'] == 1) {
                    $answer1Edit = $row['answer'];
                }
                if ($row['sortOrder'] == 2) {
                    $answer2Edit = $row['answer'];
                }
                if ($row['sortOrder'] == 3) {
                    $answer3Edit = $row['answer'];
                }
                if ($row['sortOrder'] == 4) {
                    $answer4Edit = $row['answer'];
                }
                if ($row['sortOrder'] == 5) {
                    $answer5Edit = $row['answer'];
                }
                if ($row['sortOrder'] == 6) {
                    $answer6Edit = $row['answer'];
                }
                if ($row['sortOrder'] == 7) {
                    $answer7Edit = $row['answer'];
                }
                if ($row['sortOrder'] == 8) {
                    $answer8Edit = $row['answer'];
                }
            }
            $dbh = null;
        }
    }
}
//
// Button: Reset
//
if (isset($_POST['reset'])) {
    header('Location: ' . $uri . 'survey.php');
    exit;
}
//
// HTML
//
require $includesPath . '/header1.inc';
?>
  <title>Survey</title>
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
<?php require $includesPath . '/header2.inc'; ?>
<body>
  <h1 id="waiting">Please wait.</h1>

  <h1><span class="h">Surveys in edit</span></h1>

<?php
$dbh = new PDO($dbEdit);
$stmt = $dbh->prepare('SELECT idArticle, headline, publicationDate FROM articles WHERE survey=? ORDER BY headline');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([1]);
foreach ($stmt as $row) {
    extract($row);
    echo '  <form class="wait" action="' . $uri . 'survey.php" method="post">' . "\n";
    echo '    <p><span class="p">' . html($headline) . "<br />\n";
    echo '    <input name="idArticle" type="hidden" value="' . $idArticle . '" />' . "\n";
    echo '    <input type="submit" class="button" value="Edit" name="edit" /></span></p>' . "\n";
    echo "  </form>\n\n";
}
$dbh = null;
?>

  <h1><span class="h">Published surveys</span></h1>

<?php
$dbh = new PDO($dbPublished);
$stmt = $dbh->prepare('SELECT idArticle, headline, publicationDate FROM articles WHERE survey=? ORDER BY headline');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([1]);
foreach ($stmt as $row) {
    extract($row);
    echo '  <form class="wait" action="' . $uri . 'survey.php" method="post">' . "\n";
    echo '    <p><span class="p">' . html($headline) . "<br />\n";
    echo '    <input name="idArticle" type="hidden" value="' . $idArticle . '" />' . "\n";
    echo '    <input type="submit" class="button" value="Edit" name="edit" /></span></p>' . "\n";
    echo "  </form>\n\n";
}
$dbh = null;
?>
  <form class="wait" method="post" action="survey.php">
    <input type="hidden" name="idArticle"<?php echoIfValue($idArticleEdit); ?> />
    <p>Question<br />
    <span class="hl"><textarea id="question" name="question" class="h"><?php echoIfText($questionEdit); ?></textarea></span></p>

    <p>Start date<br />
    <input id="publicationDate" name="publicationDate" type="text" class="datepicker h"<?php echoIfValue($publicationDateEdit); ?> /></p>

    <p>End date<br />
    <input name="endDate" type="text" class="datepicker h" <?php echoIfValue($endDateEdit); ?> /></p>

    <p><label for="idSection">Section</label><br />
    <select id="idSection" name="idSection">
<?php
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT idSection, section FROM sections ORDER BY sortOrderSection');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    $selected = $idSectionEdit == $row['idSection'] ? ' selected="selected"' : null;
    echo '      <option value="' . $row['idSection'] . '"' . $selected . '>' . $row['section'] . "</option>\n";
}
$dbh = null;
?>
    </select></p>

    <p>Possible answer 1<br />
    <input type="text" name="answer1"<?php echoIfValue($answer1Edit); ?> class="h" /></p>

    <p>Possible answer 2<br />
    <input type="text" name="answer2"<?php echoIfValue($answer2Edit); ?> class="h" /></p>

    <p>Possible answer 3<br />
    <input type="text" name="answer3"<?php echoIfValue($answer3Edit); ?> class="h" /></p>

    <p>Possible answer 4<br />
    <input type="text" name="answer4"<?php echoIfValue($answer4Edit); ?> class="h" /></p>

    <p>Possible answer 5<br />
    <input type="text" name="answer5"<?php echoIfValue($answer5Edit); ?> class="h" /></p>

    <p>Possible answer 6<br />
    <input type="text" name="answer6"<?php echoIfValue($answer6Edit); ?> class="h" /></p>

    <p>Possible answer 7<br />
    <input type="text" name="answer7"<?php echoIfValue($answer7Edit); ?> class="h" /></p>

    <p>Possible answer 8<br />
    <input type="text" name="answer8"<?php echoIfValue($answer8Edit); ?> class="h" /></p>

    <p><input type="submit" class="button" name="update" value="Add / update" /> <input type="submit" class="button" name="reset" value="Reset" /></p>
  </form>
</body>
</html>
