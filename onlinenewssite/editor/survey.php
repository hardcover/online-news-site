<?php
/**
 * Create and edit surveys
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
session_start();
require 'z/system/configuration.php';
$includesPath = '../' . $includesPath;
require $includesPath . '/editor/authorization.php';
require $includesPath . '/editor/common.php';
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
if (empty($row['userType']) or strval($row['userType']) !== '1') {
    include 'logout.php';
    exit;
}
$dbh = new PDO($dbSurvey);
$stmt = $dbh->query('CREATE TABLE IF NOT EXISTS "answers" ("idAnswer" INTEGER PRIMARY KEY, "idArticle" INTEGER, "sortOrder" INTEGER, "answer", "ipAddress", "tally" INTEGER)');
$dbh = null;
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
// Button: Add / Update
//
if (isset($_POST['update'])) {
    //
    // Determine insert or update
    //
    if (!empty($idArticlePost)) {
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
    }
    $dbh = new PDO($dbSurvey);
    $stmt = $dbh->query('DELETE FROM answers WHERE answer IS NULL');
    $stmt = $dbh->prepare('DELETE FROM answers WHERE idArticle=?');
    $stmt->execute([$idArticlePost]);
    $stmt = $dbh->prepare('INSERT INTO answers (idArticle, sortOrder, answer) VALUES (?, ?, ?)');
    if (!empty($answer1Post)) {
        $stmt->execute([$idArticleEdit, 1, $answer1Post]);
        $answer1Edit = $answer1Post;
    }
    if (!empty($answer2Post)) {
        $stmt->execute([$idArticleEdit, 2, $answer2Post]);
        $answer2Edit = $answer2Post;
    }
    if (!empty($answer3Post)) {
        $stmt->execute([$idArticleEdit, 3, $answer3Post]);
        $answer3Edit = $answer3Post;
    }
    if (!empty($answer4Post)) {
        $stmt->execute([$idArticleEdit, 4, $answer4Post]);
        $answer4Edit = $answer4Post;
    }
    if (!empty($answer5Post)) {
        $stmt->execute([$idArticleEdit, 5, $answer5Post]);
        $answer5Edit = $answer5Post;
    }
    if (!empty($answer6Post)) {
        $stmt->execute([$idArticleEdit, 6, $answer6Post]);
        $answer6Edit = $answer6Post;
    }
    if (!empty($answer7Post)) {
        $stmt->execute([$idArticleEdit, 7, $answer7Post]);
        $answer7Edit = $answer7Post;
    }
    if (!empty($answer8Post)) {
        $stmt->execute([$idArticleEdit, 8, $answer8Post]);
        $answer8Edit = $answer8Post;
    }
    $dbh = null;
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
        $row = array_map('strval', $row);
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
            $row = array_map('strval', $row);
            if ($row['sortOrder'] === '1') {
                $answer1Edit = $row['answer'];
            }
            if ($row['sortOrder'] === '2') {
                $answer2Edit = $row['answer'];
            }
            if ($row['sortOrder'] === '3') {
                $answer3Edit = $row['answer'];
            }
            if ($row['sortOrder'] === '4') {
                $answer4Edit = $row['answer'];
            }
            if ($row['sortOrder'] === '5') {
                $answer5Edit = $row['answer'];
            }
            if ($row['sortOrder'] === '6') {
                $answer6Edit = $row['answer'];
            }
            if ($row['sortOrder'] === '7') {
                $answer7Edit = $row['answer'];
            }
            if ($row['sortOrder'] === '8') {
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
            $row = array_map('strval', $row);
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
                $row = array_map('strval', $row);
                if ($row['sortOrder'] === '1') {
                    $answer1Edit = $row['answer'];
                }
                if ($row['sortOrder'] === '2') {
                    $answer2Edit = $row['answer'];
                }
                if ($row['sortOrder'] === '3') {
                    $answer3Edit = $row['answer'];
                }
                if ($row['sortOrder'] === '4') {
                    $answer4Edit = $row['answer'];
                }
                if ($row['sortOrder'] === '5') {
                    $answer5Edit = $row['answer'];
                }
                if ($row['sortOrder'] === '6') {
                    $answer6Edit = $row['answer'];
                }
                if ($row['sortOrder'] === '7') {
                    $answer7Edit = $row['answer'];
                }
                if ($row['sortOrder'] === '8') {
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
require $includesPath . '/editor/header1.inc';
?>
  <title>Survey</title>
  <link rel="icon" type="image/png" href="images/32.png">
  <link rel="stylesheet" type="text/css" href="z/jquery-ui.min.css">
  <link rel="stylesheet" type="text/css" href="z/base.css">
  <link rel="stylesheet" type="text/css" href="z/admin.css">
  <script src="z/jquery.min.js"></script>
  <script src="z/jquery-ui.min.js"></script>
  <script src="z/datepicker.js"></script>
  <link rel="manifest" href="manifest.json">
  <link rel="apple-touch-icon" href="images/192.png">
</head>

<?php
require $includesPath . '/editor/body.inc';
?>

  <nav class="n">
    <h4 class="m"><a class="m" href="edit.php">Edit</a><a class="m" href="published.php">Published</a><a class="m" href="preview.php">Preview</a><a class="m" href="archive.php">Archives</a></h4>
  </nav>

  <div class="flex">
    <main>
      <h1>Create and edit surveys</h1>

      <form method="post" action="survey.php">
        <input type="hidden" name="idArticle"<?php echoIfValue($idArticleEdit); ?>>
        <p>Question<br>
        <textarea id="question" name="question" class="h"><?php echoIfText($questionEdit); ?></textarea></p>

        <p>Start date<br>
        <input id="publicationDate" name="publicationDate" class="datepicker date"<?php echoIfValue($publicationDateEdit); ?>></p>

        <p>End date<br>
        <input name="endDate" class="datepicker date" <?php echoIfValue($endDateEdit); ?>></p>

        <p><label for="idSection">Section</label><br>
        <select id="idSection" name="idSection">
<?php
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT idSection, section FROM sections ORDER BY sortOrderSection');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    $selected = $idSectionEdit === $row['idSection'] ? ' selected="selected"' : null;
    echo '          <option value="' . $row['idSection'] . '"' . $selected . '>' . $row['section'] . "</option>\n";
}
$dbh = null;
?>
        </select></p>

        <p>Possible answer 1<br>
        <input name="answer1"<?php echoIfValue($answer1Edit); ?> class="h"></p>

        <p>Possible answer 2<br>
        <input name="answer2"<?php echoIfValue($answer2Edit); ?> class="h"></p>

        <p>Possible answer 3<br>
        <input name="answer3"<?php echoIfValue($answer3Edit); ?> class="h"></p>

        <p>Possible answer 4<br>
        <input name="answer4"<?php echoIfValue($answer4Edit); ?> class="h"></p>

        <p>Possible answer 5<br>
        <input name="answer5"<?php echoIfValue($answer5Edit); ?> class="h"></p>

        <p>Possible answer 6<br>
        <input name="answer6"<?php echoIfValue($answer6Edit); ?> class="h"></p>

        <p>Possible answer 7<br>
        <input name="answer7"<?php echoIfValue($answer7Edit); ?> class="h"></p>

        <p>Possible answer 8<br>
        <input name="answer8"<?php echoIfValue($answer8Edit); ?> class="h"></p>

        <p><input type="submit" class="button" name="update" value="Add / update"> <input type="submit" class="button" name="reset" value="Reset"></p>
      </form>
    </main>

    <aside>
      <h2>Surveys in edit</h2>

<?php
$dbh = new PDO($dbEdit);
$stmt = $dbh->prepare('SELECT idArticle, headline, publicationDate FROM articles WHERE survey=? ORDER BY headline');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([1]);
foreach ($stmt as $row) {
    extract($row);
    echo '      <form action="' . $uri . 'survey.php" method="post">' . "\n";
    echo '        <p>' . html($headline) . "<br>\n";
    echo '        <input name="idArticle" type="hidden" value="' . $idArticle . '">' . "\n";
    echo '        <input type="submit" class="button" value="Edit" name="edit"></p>' . "\n";
    echo '      </form>' . "\n\n";
}
$dbh = null;
?>
      <h2>Published surveys</h2>

<?php
$dbh = new PDO($dbPublished);
$stmt = $dbh->prepare('SELECT idArticle, headline, publicationDate FROM articles WHERE survey=? ORDER BY headline');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([1]);
foreach ($stmt as $row) {
    extract($row);
    echo '      <form action="' . $uri . 'survey.php" method="post">' . "\n";
    echo '        <p>' . html($headline) . "<br>\n";
    echo '        <input name="idArticle" type="hidden" value="' . $idArticle . '">' . "\n";
    echo '        <input type="submit" class="button" value="Edit" name="edit"></p>' . "\n";
    echo '      </form>' . "\n\n";
}
$dbh = null;
?>
    </aside>
  </div>
</body>
</html>
