<?php
/**
 * Diplay a single article
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
$aGet = secure($_GET['a']);
//
// Check for article by ID
//
if (strpos($database, 'archive') === false) {
    //
    // Edit and published databases
    //
    $dbh = new PDO($database);
    $stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([secure($aGet)]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row['idArticle']) {
        $dbh = new PDO($database);
        $stmt = $dbh->prepare('SELECT * FROM articles WHERE idArticle=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([secure($aGet)]);
        $row = $stmt->fetch();
        $dbh = null;
        $row = array_map('strval', $row);
        extract($row);
        //
        // Set section name
        //
        $dbh = new PDO($dbSettings);
        $stmt = $dbh->prepare('SELECT section FROM sections WHERE idSection=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idSection]);
        $row = $stmt->fetch();
        $dbh = null;
        extract($row);
        //
        // HTML
        //
        $bylineDateTime = !empty($publicationDate) ? date("l, F j, Y", strtotime($publicationDate)) : null;
        if ($database !== $dbArchive) {
            $html.= '    <p><span class="fr"><a class="button" href="' . $use . '.php">Index</a></span></p>' . "\n\n";
            $html.= '    <p><b>' . $section . "</b></p>\n\n";
        }
        $html.= '    <h1>' . html($headline) . "</h1>\n\n";
        if (!empty($standfirst)) {
            $html.= '    <h3>' . html($standfirst) . "</h3>\n\n";
        }
        if (!empty($hdImage)) {
            if ($photoName === 'third') {
                $html.= '    <p><img src="' . $imagePath . '?i=' . muddle($idArticle) . 'h" class="third border mb" alt="' . $alt . '"></p>' . "\n\n";
            } else {
                $html.= '    <p><img src="' . $imagePath . '?i=' . muddle($idArticle) . 'h" class="wide border" alt="' . $alt . '"></p>' . "\n\n";
            }
        }
        if (!empty($photoCaption) and !empty($photoCredit)) {
            if ($photoName === 'third') {
                $html.= '    <h6>' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
            } else {
                $html.= '    <h6>' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
            }
        } elseif (!empty($photoCaption)) {
            if ($photoName === 'third') {
                $html.= '    <h6>' . html($photoCaption) . "</h6>\n\n";
            } else {
                $html.= '    <h6>' . html($photoCaption) . "</h6>\n\n";
            }
        } elseif (!empty($photoCredit)) {
            if ($photoName === 'third') {
                $html.= '    <h6>' . $photoCredit . "</h6>\n\n";
            } else {
                $html.= '    <h6>' . $photoCredit . "</h6>\n\n";
            }
        }
        if (!empty($byline) or !empty($bylineDateTime)) {
            $html.= '    <h5>';
        }
        if (!empty($byline)) {
            $html.= 'By ' . $byline;
        }
        if (!empty($byline) and !empty($bylineDateTime)) {
            $html.= ', ';
        }
        if (!empty($bylineDateTime)) {
            $html.= html($bylineDateTime);
        }
        if (!empty($byline) or !empty($bylineDateTime)) {
            $html.= "</h5>\n\n";
        }
        $temp = Parsedown::instance()->parse($text);
        $temp = str_replace("\n", "\n\n    ", $temp);
        $temp = str_replace('<p>', '<p>', $temp);
        $temp = str_replace('<ol>', '<ol>', $temp);
        $temp = str_replace('<ul>', '<ul>', $temp);
        $temp = str_replace('<li>', '    <li>', $temp);
        $html.= '    ' . $temp . "\n";
        if ($survey === '1') {
            //
            // Tally the vote, display the survey results
            //
            $dbh = new PDO($dbSurvey);
            $stmt = $dbh->prepare('SELECT count(idArticle) FROM tally WHERE idArticle=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$aGet]);
            $row = $stmt->fetch();
            $total = $row['count(idArticle)'];
            if ($total === '0') {
                $total = 1;
            }
            $stmt = $dbh->prepare('SELECT idAnswer, answer FROM answers WHERE idArticle=? ORDER BY sortOrder');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$aGet]);
            foreach ($stmt as $row) {
                extract($row);
                $stmt = $dbh->prepare('SELECT count(idAnswer) FROM tally WHERE idAnswer=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$idAnswer]);
                $row = $stmt->fetch();
                $each = $row['count(idAnswer)'];
                $html.= '    <p><meter min="0" max="' . $total . '" value="' . $each . '" title="' . $answer . '">' . $answer . '"</meter> ' . $answer . ', ' . $each . "</p>\n\n";
            }
            $dbh = null;
        } else {
            $dbh = new PDO($database2);
            $stmt = $dbh->prepare('SELECT idPhoto, photoName, photoCredit, photoCaption, alt FROM imageSecondary WHERE idArticle=? ORDER BY time');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$idArticle]);
            foreach ($stmt as $row) {
                extract($row);
                if ($photoName === 'third') {
                    $html.= '    <p><img src="' . $imagePath2 . '?i=' . muddle($idPhoto) . 'h" class="third border mb" alt="' . $alt . '"></p>' . "\n\n";
                } else {
                    $html.= '    <p><img src="' . $imagePath2 . '?i=' . muddle($idPhoto) . 'h" class="wide border" alt="' . $alt . '"></p>' . "\n\n";
                }
                if (!empty($photoCaption) and !empty($photoCredit)) {
                    if ($photoName === 'third') {
                        $html.= '    <h6>' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
                    } else {
                        $html.= '    <h6>' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
                    }
                } elseif (!empty($photoCaption)) {
                    if ($photoName === 'third') {
                        $html.= '    <h6>' . html($photoCaption) . "</h6>\n\n";
                    } else {
                        $html.= '    <h6>' . html($photoCaption) . "</h6>\n\n";
                    }
                } elseif (!empty($photoCredit)) {
                    if ($photoName === 'third') {
                        $html.= '    <h6>' . $photoCredit . "</h6>\n\n";
                    } else {
                        $html.= '    <h6>' . $photoCredit . "</h6>\n\n";
                    }
                }
            }
            $dbh = null;
            $html.= "\n" . '    <p><span class="fr"><a class="button" href="' . $use . '.php">Index</a></span><br></p>' . "\n";;
        }
    } else {
        header('Location: ' . $uri);
    }
} else {
    //
    // Archive databases
    //
    $dbNumber = 0;
    while ($dbNumber !== -1) {
        $db = str_replace('archive', 'archive-' . $dbNumber, $dbArchive);
        if ($dbNumber === 0
            or file_exists(str_replace('sqlite:', '', $db))
        ) {
            if ($dbNumber === 0) {
                $database = $dbArchive;
            } else {
                $database = $db;
            }
            $dbNumber++;
        } else {
            $dbNumber = -1;
            $dbh = null;
        }
        if (!empty($database)) {
            $dbh = new PDO($database);
            $stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE idArticle=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$aGet]);
            $row = $stmt->fetch();
            $dbh = null;
            if ($row) {
                $dbNumber = -1;
                $dbh = new PDO($database);
                $stmt = $dbh->prepare('SELECT * FROM articles WHERE idArticle=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$aGet]);
                $row = $stmt->fetch();
                extract($row);
                $dbh = null;
                $html.= '    <h1>' . html($headline) . "</h1>\n\n";
                if (isset($standfirst)) {
                    $html.= '    <h3>' . html($standfirst) . '<br><br></h3>' . "\n\n";
                }
                if (isset($hdImage)) {
                    if ($photoName === 'third') {
                        $html.= '    <p class="a"><img src="' . $imagePath . '?i=' . muddle($idArticle) . 'h" class="third border mb" alt="' . $alt . '"></p>' . "\n\n";
                    } else {
                        $html.= '    <p><img src="' . $imagePath . '?i=' . muddle($idArticle) . 'h" class="wide border" alt="' . $alt . '"></p>' . "\n\n";
                    }
                }
                if (!empty($photoCaption) and !empty($photoCredit)) {
                    if ($photoName === 'third') {
                        $html.= '    <h6 class="a">' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
                    } else {
                        $html.= '    <h6>' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
                    }
                } elseif (isset($photoCaption)) {
                    if ($photoName === 'third') {
                        $html.= '    <h6 class="a">' . html($photoCaption) . "</h6>\n\n";
                    } else {
                        $html.= '    <h6>' . html($photoCaption) . "</h6>\n\n";
                    }
                } elseif (isset($photoCredit)) {
                    if ($photoName === 'third') {
                        $html.= '    <h6 class="a">(' . $photoCredit . ")</h6>\n\n";
                    } else {
                        $html.= '    <h6>(' . $photoCredit . ")</h6>\n\n";
                    }
                }
                if (!empty($byline) or !empty($bylineDateTime)) {
                    $html.= '    <h5 class="a">';
                }
                if (!empty($byline)) {
                    $html.= 'By ' . $byline;
                }
                if (!empty($byline) and !empty($bylineDateTime)) {
                    $html.= ', ';
                }
                if (!empty($bylineDateTime)) {
                    $html.= html($bylineDateTime);
                }
                if (!empty($byline) or !empty($bylineDateTime)) {
                    $html.= "</h5>\n\n";
                }
                $temp = Parsedown::instance()->parse($text);
                $temp = str_replace("\n", "\n\n    ", $temp);
                $temp = str_replace('<p>', '<p class="a">', $temp);
                $temp = str_replace('<ol>', '<ol class="a">', $temp);
                $temp = str_replace('<ul>', '<ul class="a">', $temp);
                $temp = str_replace('<li>', '  <li>', $temp);
                $html.= $temp . "\n";
                if ($survey === '1') {
                    if ($use === 'news') {
                        //
                        // Display a survey not yet in the archives
                        //
                        if (empty($_POST['vote'])) {
                            $html.= '  <form action="' . $uri . 'news.php?a=' . $aGet . '" method="post">' . "\n";
                            $html.= '    <input name="idQuestion" type="hidden" value="' . $aGet . '">' . "\n";
                            $dbh = new PDO($dbSurvey);
                            $stmt = $dbh->prepare('SELECT idAnswer, sortOrder, answer FROM answers WHERE idArticle=? ORDER BY sortOrder');
                            $stmt->setFetchMode(PDO::FETCH_ASSOC);
                            $stmt->execute([$aGet]);
                            foreach ($stmt as $row) {
                                extract($row);
                                $html.= '  <p class="a"><label for="' . $idAnswer . '"><input id="' . $idAnswer . '" name="answer" type="radio" value="' . $idAnswer . '"> ' . $answer . '</label></p>' . "\n\n";
                            }
                            $dbh = null;
                            $html.= '  <p class="a"><input type="submit" class="button" value="Cast vote" name="vote"></p>' . "\n";
                            $html.= "  </form>\n";
                        } else {
                            if (empty($_POST['answer'])) {
                                //
                                // If no vote was cast
                                //
                                $html.= '  <p class="error">A selection is required.</p>' . "\n\n";
                                $html.= '  <form action="' . $uri . 'news.php?a=' . $aGet . '" method="post">' . "\n";
                                $html.= '    <input name="idQuestion" type="hidden" value="' . $aGet . '">' . "\n";
                                $dbh = new PDO($dbSurvey);
                                $stmt = $dbh->prepare('SELECT idAnswer, sortOrder, answer FROM answers WHERE idArticle=? ORDER BY sortOrder');
                                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                                $stmt->execute([$aGet]);
                                foreach ($stmt as $row) {
                                    extract($row);
                                    $html.= '  <p class="a"><label for="' . $sortOrder . '"><input id="' . $sortOrder . '" name="answer" type="radio" value="' . $sortOrder . '"> ' . $answer . '</label></p>' . "\n\n";
                                }
                                $dbh = null;
                                $html.= '  <p class="a"><input type="submit" class="button" value="Cast vote" name="vote"></p>' . "\n";
                                $html.= "  </form>\n";
                            } else {
                                //
                                // Tally the vote, display the survey results
                                //
                                $dbh = new PDO($dbSurvey);
                                $stmt = $dbh->prepare('SELECT ipAddress FROM tally WHERE idArticle=? AND ipAddress=?');
                                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                                $stmt->execute([$aGet, $_SERVER['REMOTE_ADDR']]);
                                $row = $stmt->fetch();
                                $dbh = null;
                                if ($row) {
                                    $html.= '  <p class="error">Your vote had already been tallied.</p>' . "\n\n";
                                } else {
                                    $dbh = new PDO($dbSurvey);
                                    $stmt = $dbh->prepare('INSERT INTO tally (idArticle, idAnswer, ipAddress) VALUES (?, ?, ?)');
                                    $stmt->execute([$aGet, $idAnswerPost, $_SERVER['REMOTE_ADDR']]);
                                    $dbh = null;
                                }
                                $dbh = new PDO($dbSurvey);
                                $stmt = $dbh->prepare('SELECT count(idArticle) FROM tally WHERE idArticle=?');
                                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                                $stmt->execute([$aGet]);
                                $row = $stmt->fetch();
                                $total = $row['count(idArticle)'];
                                $stmt = $dbh->prepare('SELECT idAnswer, answer FROM answers WHERE idArticle=? ORDER BY sortOrder');
                                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                                $stmt->execute([$aGet]);
                                foreach ($stmt as $row) {
                                    extract($row);
                                    $stmt = $dbh->prepare('SELECT count(idAnswer) FROM tally WHERE idAnswer=?');
                                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                                    $stmt->execute([$idAnswer]);
                                    $row = $stmt->fetch();
                                    $each = $row['count(idAnswer)'];
                                    $html.= '  <p class="a"><meter min="0" max="' . $total . '" value="' . $each . '" title="' . $answer . '">' . $answer . '"</meter> ' . $answer . ', ' . $each . "</p>\n\n";
                                }
                                $dbh = null;
                            }
                        }
                    }
                    if ($use === '?m=archive') {
                        //
                        // Display a survey in the archives
                        //
                        $dbh = new PDO($dbSurvey);
                        $stmt = $dbh->prepare('SELECT count(idArticle) FROM tally WHERE idArticle=?');
                        $stmt->setFetchMode(PDO::FETCH_ASSOC);
                        $stmt->execute([$aGet]);
                        $row = $stmt->fetch();
                        $total = $row['count(idArticle)'];
                        if ($total === '0') {
                            $total = 1;
                        }
                        $stmt = $dbh->prepare('SELECT idAnswer, answer FROM answers WHERE idArticle=? ORDER BY sortOrder');
                        $stmt->setFetchMode(PDO::FETCH_ASSOC);
                        $stmt->execute([$aGet]);
                        foreach ($stmt as $row) {
                            extract($row);
                            $stmt = $dbh->prepare('SELECT count(idAnswer) FROM tally WHERE idAnswer=?');
                            $stmt->setFetchMode(PDO::FETCH_ASSOC);
                            $stmt->execute([$idAnswer]);
                            $row = $stmt->fetch();
                            $each = $row['count(idAnswer)'];
                            $html.= '  <p class="a"><meter min="0" max="' . $total . '" value="' . $each . '" title="' . $answer . '">' . $answer . '"</meter> ' . $answer . ', ' . $each . "</p>\n\n";
                        }
                        $dbh = null;
                    }
                } else {
                    //
                    // Display secondary images
                    //
                    if ($database2 === $dbArchive2) {
                        $dbNumber2 = 0;
                        while ($dbNumber2 !== -1) {
                            $db = str_replace('archive2', 'archive2-' . $dbNumber2, $dbArchive2);
                            if ($dbNumber2 === 0
                                or file_exists(str_replace('sqlite:', '', $db))
                            ) {
                                if ($dbNumber2 === 0) {
                                    $database = $dbArchive2;
                                } else {
                                    $database = $db;
                                }
                                $dbNumber2++;
                            } else {
                                $dbNumber2 = -1;
                                $dbh = null;
                            }
                            if (!empty($database)
                                and file_exists($database)
                            ) {
                                $dbh = new PDO($database);
                                $stmt = $dbh->prepare('SELECT idPhoto, photoName, photoCredit, photoCaption FROM imageSecondary WHERE idArticle=? ORDER BY time');
                                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                                $stmt->execute([$idArticle]);
                                foreach ($stmt as $row) {
                                    extract($row);
                                    $dbNumber2 = -1;
                                    if ($photoName === 'third') {
                                        $html.= '    <p class="a"><img src="' . $imagePath2 . '?i=' . muddle($idPhoto) . 'h" class="third border mb" alt="' . $alt . '"></p>' . "\n\n";
                                    } else {
                                        $html.= '    <p><img src="' . $imagePath2 . '?i=' . muddle($idPhoto) . 'h" class="wide border" alt="' . $alt . '"></p>' . "\n\n";
                                    }
                                    if (!empty($photoCaption) and !empty($photoCredit)) {
                                        if ($photoName === 'third') {
                                            $html.= '    <h6 class="a">' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
                                        } else {
                                            $html.= '    <h6>' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
                                        }
                                    } elseif (isset($photoCaption)) {
                                        if ($photoName === 'third') {
                                            $html.= '    <h6 class="a">' . html($photoCaption) . "</h6>\n\n";
                                        } else {
                                            $html.= '    <h6>' . html($photoCaption) . "</h6>\n\n";
                                        }
                                    } elseif (isset($photoCredit)) {
                                        if ($photoName === 'third') {
                                            $html.= '    <h6 class="a">(' . $photoCredit . ")</h6>\n\n";
                                        } else {
                                            $html.= '    <h6>(' . $photoCredit . ")</h6>\n\n";
                                        }
                                    }
                                }
                                $dbh = null;
                            }
                        }
                    }
                }
            }
        }
    }
}
?>
