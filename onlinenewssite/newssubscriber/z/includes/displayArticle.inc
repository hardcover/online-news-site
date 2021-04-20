<?php
/**
 * Display a single article
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2021 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2021 4 20
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
//
// Variables
//
$aGet = secure($_GET['a']);
$idAnswerPost = inlinePost('answer');
if (empty($survey)) {
    $survey = null;
}
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
    $stmt->execute([$aGet]);
    $row = $stmt->fetch();
    $dbh = null;
    if ($row['idArticle']) {
        $dbh = new PDO($database);
        $stmt = $dbh->prepare('SELECT * FROM articles WHERE idArticle=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$aGet]);
        $row = $stmt->fetch();
        extract($row);
        //
        // Set previous and next links
        //
        $sectionsOrder = [];
        $dbhSection = new PDO($dbSettings);
        $stmt = $dbhSection->query('SELECT idSection FROM sections ORDER BY sortOrderSection');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            $sectionsOrder[] = $row['idSection'];
        }
        $dbh = null;
        $articlesOrder = [];
        foreach ($sectionsOrder as $sectionOrder) {
            $dbh = new PDO($database);
            $stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE idSection = ? AND publicationDate <= "' . $datePost . '" AND "' . $datePost . '" <= endDate ORDER BY sortOrderArticle');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$sectionOrder]);
            foreach ($stmt as $row) {
                $articlesOrder[] = $row['idArticle'];
            }
            $dbh = null;
        }
        $key = array_search($aGet, $articlesOrder);
        if (empty($key)) {
            $key = 0;
        }
        if ($key === 0 or $key === 1) {
            $previous = null;
        } else {
            $previous = $articlesOrder[$key - 1];
            $previous = '<a class="n" href="' . $uri . $use . '.php?a=' . $previous . '">Previous</a> | ';
        }
        if ($key === count($articlesOrder) or $key + 1 === count($articlesOrder)) {
            $next = null;
            $pipe = null;
        } else {
            $next = $articlesOrder[$key + 1];
            $next = '<a class="n" href="' . $uri . $use . '.php?a=' . $next . '">Next</a>';
            $pipe = ' | ';
        }
        //
        // Set paper and section name
        //
        $dbh = new PDO($dbSettings);
        $stmt = $dbh->query('SELECT name FROM names');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $row = $stmt->fetch();
        if ($row) {
            extract($row);
        } else {
            $name = null;
        }
        $stmt = $dbh->prepare('SELECT section FROM sections WHERE idSection=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idSection]);
        $row = $stmt->fetch();
        $dbh = null;
        if ($row) {
            extract($row);
        } else {
            $section = null;
        }
        //
        // HTML
        //
        $bylineDateTime = isset($publicationDate) ? date("l, F j, Y", strtotime($publicationDate)) : null;
        if ($database !== $dbArchive) {
            $html.= '  <p><span class="al">' . $previous . '<a class="n" href="' . $uri . '">Index</a>' . $pipe . $next;
            $html.= $logOutHtml . "</span><br /></p>\n\n";
            $html.= '  <h2>' . $name . "</h2>\n\n";
            $html.= '  <p><b>' . $section . "</b></p>\n\n";
        }
        $html.= '  <h1>' . html($headline) . "</h1>\n\n";
        if (isset($standfirst)) {
            $html.= '  <h3>' . html($standfirst) . "</h3>\n\n";
        }
        if (isset($hdImage)) {
            if ($photoName === 'third') {
                $html.= '  <p><img src="' . $imagePath . '?i=' . muddle($idArticle) . 'h" class="t b" alt=""></p>' . "\n\n";
            } else {
                $html.= '  <p><img src="' . $imagePath . '?i=' . muddle($idArticle) . 'h" class="w b" alt=""></p>' . "\n\n";
            }
        }
        if (!empty($photoCaption) and !empty($photoCredit)) {
            if ($photoName === 'third') {
                $html.= '  <h6 class="fn">' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
            } else {
                $html.= '  <h6>' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
            }
        } elseif (isset($photoCaption)) {
            if ($photoName === 'third') {
                $html.= '  <h6 class="fn">' . html($photoCaption) . "</h6>\n\n";
            } else {
                $html.= '  <h6>' . html($photoCaption) . "</h6>\n\n";
            }
        } elseif (isset($photoCredit)) {
            if ($photoName === 'third') {
                $html.= '  <h6 class="fn">' . $photoCredit . "</h6>\n\n";
            } else {
                $html.= '  <h6 class="r">' . $photoCredit . "</h6>\n\n";
            }
        }
        if (!empty($byline) or !empty($bylineDateTime)) {
            $html.= '  <h5>';
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
        $temp = str_replace("\n", "\n\n  ", $temp);
        $html.= '  ' . $temp . "\n";
        if ($survey === '1') {
            if ($use === 'news') {
                //
                // Display a survey not yet in the archives
                //
                if (empty($_POST['vote'])) {
                    $html.= '  <form class="wait" action="' . $uri . 'news.php?a=' . $aGet . '" method="post">' . "\n";
                    $html.= '    <input name="idQuestion" type="hidden" value="' . $aGet . '" />' . "\n";
                    $dbh = new PDO($dbSurvey);
                    $stmt = $dbh->prepare('SELECT idAnswer, sortOrder, answer FROM answers WHERE idArticle=? ORDER BY sortOrder');
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $stmt->execute([$aGet]);
                    foreach ($stmt as $row) {
                        extract($row);
                        $html.= '    <p><label for="' . $idAnswer . '"><input id="' . $idAnswer . '" name="answer" type="radio" value="' . $idAnswer . '" /> ' . $answer . '</label></p>' . "\n\n";
                    }
                    $html.= '    <p><input type="submit" class="button" value="Cast vote" name="vote" /></p>' . "\n";
                    $html.= "  </form>\n";
                } else {
                    if (empty($_POST['answer'])) {
                        //
                        // If no vote was cast
                        //
                        $html.= '  <p class="e">A selection is required.</p>' . "\n\n";
                        $html.= '  <form class="wait" action="' . $uri . 'news.php?a=' . $aGet . '" method="post">' . "\n";
                        $html.= '    <input name="idQuestion" type="hidden" value="' . $aGet . '" />' . "\n";
                        $dbh = new PDO($dbSurvey);
                        $stmt = $dbh->prepare('SELECT idAnswer, sortOrder, answer FROM answers WHERE idArticle=? ORDER BY sortOrder');
                        $stmt->setFetchMode(PDO::FETCH_ASSOC);
                        $stmt->execute([$aGet]);
                        foreach ($stmt as $row) {
                            extract($row);
                            $html.= '    <p><label for="' . $sortOrder . '"><input id="' . $sortOrder . '" name="answer" type="radio" value="' . $sortOrder . '" /> ' . $answer . '</label></p>' . "\n\n";
                        }
                        $html.= '    <p><input type="submit" class="button" value="Cast vote" name="vote" /></p>' . "\n";
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
                            $html.= '  <p class="e">Your vote had already been tallied.</p>' . "\n\n";
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
                            $html.= '  <p><meter min="0" max="' . $total . '" value="' . $each . '" title="' . $answer . '">' . $answer . '"</meter> ' . $answer . ', ' . $each . "</p>\n\n";
                        }
                        $dbh = null;
                    }
                }
            }
        } else {
            //
            // Display an article
            //
            $dbh = new PDO($database2);
            $stmt = $dbh->prepare('SELECT idPhoto, photoName, photoCredit, photoCaption FROM imageSecondary WHERE idArticle=? ORDER BY time');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$idArticle]);
            foreach ($stmt as $row) {
                extract($row);
                if ($photoName === 'third') {
                    $html.= '  <p><img src="' . $imagePath2 . '?i=' . muddle($idPhoto) . 'h" class="t b" alt=""></p>' . "\n\n";
                } else {
                    $html.= '  <p><img src="' . $imagePath2 . '?i=' . muddle($idPhoto) . 'h" class="w b" alt=""></p>' . "\n\n";
                }
                if (!empty($photoCaption) and !empty($photoCredit)) {
                    if ($photoName === 'third') {
                        $html.= '  <h6 class="fn">' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
                    } else {
                        $html.= '  <h6>' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
                    }
                } elseif (isset($photoCaption)) {
                    if ($photoName === 'third') {
                        $html.= '  <h6 class="fn">' . html($photoCaption) . "</h6>\n\n";
                    } else {
                        $html.= '  <h6>' . html($photoCaption) . "</h6>\n\n";
                    }
                } elseif (isset($photoCredit)) {
                    if ($photoName === 'third') {
                        $html.= '  <h6 class="fn">' . $photoCredit . "</h6>\n\n";
                    } else {
                        $html.= '  <h6 class="r">' . $photoCredit . "</h6>\n\n";
                    }
                }
            }
            $dbh = null;
        }
        $html.= "\n" . '  <p><br /><span class="al">' . $previous . '<a class="n" href="' . $uri . '">Index</a>' . $pipe . $next;
        $html.= $logOutHtml . "</span><br /></p>\n";
    } else {
        echo '<meta HTTP-EQUIV="REFRESH" content="2; url=' . $uri . '">';
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
                $html.= '  <h1>' . html($headline) . "</h1>\n\n";
                if (isset($standfirst)) {
                    $html.= '  <h3>' . html($standfirst) . "</h3>\n\n";
                }
                if (isset($hdImage)) {
                    if ($photoName === 'third') {
                        $html.= '  <p><img src="' . $imagePath . '?i=' . muddle($idArticle) . 'h" class="t b" alt=""></p>' . "\n\n";
                    } else {
                        $html.= '  <p><img src="' . $imagePath . '?i=' . muddle($idArticle) . 'h" class="w b" alt=""></p>' . "\n\n";
                    }
                }
                if (!empty($photoCaption) and !empty($photoCredit)) {
                    if ($photoName === 'third') {
                        $html.= '  <h6 class="fn">' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
                    } else {
                        $html.= '  <h6>' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
                    }
                } elseif (isset($photoCaption)) {
                    if ($photoName === 'third') {
                        $html.= '  <h6 class="fn">' . html($photoCaption) . "</h6>\n\n";
                    } else {
                        $html.= '  <h6>' . html($photoCaption) . "</h6>\n\n";
                    }
                } elseif (isset($photoCredit)) {
                    if ($photoName === 'third') {
                        $html.= '  <h6 class="fn">' . $photoCredit . "</h6>\n\n";
                    } else {
                        $html.= '  <h6 class="r">' . $photoCredit . "</h6>\n\n";
                    }
                }
                if (!empty($byline) or !empty($bylineDateTime)) {
                    $html.= '  <h5>';
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
                $temp = str_replace("\n", "\n\n  ", $temp);
                $html.= '  ' . $temp . "\n";
                if ($survey === '1') {
                    if ($use === 'news') {
                        //
                        // Display a survey not yet in the archives
                        //
                        if (empty($_POST['vote'])) {
                            $html.= '  <form class="wait" action="' . $uri . 'news.php?a=' . $aGet . '" method="post">' . "\n";
                            $html.= '    <input name="idQuestion" type="hidden" value="' . $aGet . '" />' . "\n";
                            $dbh = new PDO($dbSurvey);
                            $stmt = $dbh->prepare('SELECT idAnswer, sortOrder, answer FROM answers WHERE idArticle=? ORDER BY sortOrder');
                            $stmt->setFetchMode(PDO::FETCH_ASSOC);
                            $stmt->execute([$aGet]);
                            foreach ($stmt as $row) {
                                extract($row);
                                $html.= '    <p><label for="' . $idAnswer . '"><input id="' . $idAnswer . '" name="answer" type="radio" value="' . $idAnswer . '" /> ' . $answer . '</label></p>' . "\n\n";
                            }
                            $html.= '    <p><input type="submit" class="button" value="Cast vote" name="vote" /></p>' . "\n";
                            $html.= "  </form>\n";
                        } else {
                            if (empty($_POST['answer'])) {
                                //
                                // If no vote was cast
                                //
                                $html.= '  <p class="e">A selection is required.</p>' . "\n\n";
                                $html.= '  <form class="wait" action="' . $uri . 'news.php?a=' . $aGet . '" method="post">' . "\n";
                                $html.= '    <input name="idQuestion" type="hidden" value="' . $aGet . '" />' . "\n";
                                $dbh = new PDO($dbSurvey);
                                $stmt = $dbh->prepare('SELECT idAnswer, sortOrder, answer FROM answers WHERE idArticle=? ORDER BY sortOrder');
                                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                                $stmt->execute([$aGet]);
                                foreach ($stmt as $row) {
                                    extract($row);
                                    $html.= '    <p><label for="' . $sortOrder . '"><input id="' . $sortOrder . '" name="answer" type="radio" value="' . $sortOrder . '" /> ' . $answer . '</label></p>' . "\n\n";
                                }
                                $html.= '    <p><input type="submit" class="button" value="Cast vote" name="vote" /></p>' . "\n";
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
                                    $html.= '  <p class="e">Your vote had already been tallied.</p>' . "\n\n";
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
                                    $html.= '  <p><meter min="0" max="' . $total . '" value="' . $each . '" title="' . $answer . '">' . $answer . '"</meter> ' . $answer . ', ' . $each . "</p>\n\n";
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
                            $html.= '  <p><meter min="0" max="' . $total . '" value="' . $each . '" title="' . $answer . '">' . $answer . '"</meter> ' . $answer . ', ' . $each . "</p>\n\n";
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
                            if (!empty($database)) {
                                $dbh = new PDO($database);
                                $stmt = $dbh->prepare('SELECT idPhoto, photoName, photoCredit, photoCaption FROM imageSecondary WHERE idArticle=? ORDER BY time');
                                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                                $stmt->execute([$idArticle]);
                                foreach ($stmt as $row) {
                                    extract($row);
                                    $dbNumber2 = -1;
                                    if ($photoName === 'third') {
                                        $html.= '  <p><img src="' . $imagePath2 . '?i=' . muddle($idPhoto) . 'h" class="t b" alt=""></p>' . "\n\n";
                                    } else {
                                        $html.= '  <p><img src="' . $imagePath2 . '?i=' . muddle($idPhoto) . 'h" class="w b" alt=""></p>' . "\n\n";
                                    }
                                    if (!empty($photoCaption) and !empty($photoCredit)) {
                                        if ($photoName === 'third') {
                                            $html.= '  <h6 class="fn">' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
                                        } else {
                                            $html.= '  <h6>' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
                                        }
                                    } elseif (isset($photoCaption)) {
                                        if ($photoName === 'third') {
                                            $html.= '  <h6 class="fn">' . html($photoCaption) . "</h6>\n\n";
                                        } else {
                                            $html.= '  <h6>' . html($photoCaption) . "</h6>\n\n";
                                        }
                                    } elseif (isset($photoCredit)) {
                                        if ($photoName === 'third') {
                                            $html.= '  <h6 class="fn">' . $photoCredit . "</h6>\n\n";
                                        } else {
                                            $html.= '  <h6 class="r">' . $photoCredit . "</h6>\n\n";
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