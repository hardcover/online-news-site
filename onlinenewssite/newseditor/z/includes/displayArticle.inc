<?php
/**
 * Diplay a single article
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2019 12 7
 * @link      https://hardcoverwebdesign.com/
 * @link      https://online-news-site.com/
 * @link      https://github.com/hardcover/
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
        extract($row);
        //
        // Set previous and next links
        //
        $order = [];
        if (is_null($editorView)) {
            $stmt = $dbh->prepare('SELECT idArticle, sortOrderArticle FROM articles WHERE idSection = ? AND publicationDate <= "' . $datePost . '" AND "' . $datePost . '" <= endDate ORDER BY sortOrderArticle');
        } else {
            $stmt = $dbh->prepare('SELECT idArticle, sortOrderArticle FROM articles WHERE idSection = ? ORDER BY sortOrderArticle');
        }
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idSection]);
        foreach ($stmt as $row) {
            $order[$row['sortOrderArticle']] = $row['idArticle'];
        }
        $dbh = null;
        if (isset($order[$sortOrderArticle - 1])) {
            $previous = '<a class="n" href="' . $uri . $use . '.php?a=' . $order[$sortOrderArticle - 1] . '">Previous</a> | ';
        } else {
            $previous = null;
        }
        if (isset($order[$sortOrderArticle + 1])) {
            $next = '<a class="n" href="' . $uri . $use . '.php?a=' . $order[$sortOrderArticle + 1] . '">Next</a>';
            $pipe = ' | ';
        } else {
            $next = null;
            $pipe = null;
        }
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
        $bylineDateTime = isset($publicationDate) ? date("l, F j, Y", strtotime($publicationDate)) : null;
        if ($database != $dbArchive) {
            $html.= '  <p><span class="al">' . $previous . '<a class="n" href="' . $use . '.php">Index</a> | ' . $next . $pipe;
            $html.= $links . '<a class="n" href="' . $uri . 'logout.php">Log out</a></span><br /></p>' . "\n\n";
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
        if ($photoCaption != '' and $photoCredit != '') {
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
        if ($survey == 1) {
            //
            // Tally the vote, display the survey results
            //
            $dbh = new PDO($dbSurvey);
            $stmt = $dbh->prepare('SELECT count(idArticle) FROM tally WHERE idArticle=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$aGet]);
            $row = $stmt->fetch();
            $total = $row['count(idArticle)'];
            if ($total == 0) {
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
                $html.= '<p><meter min="0" max="' . $total . '" value="' . $each . '" title="' . $answer . '">' . $answer . '"</meter> ' . $answer . ', ' . $each . "</p>\n\n";
            }
            $dbh = null;
            $html.= '<form class="wait"></form>' . "\n\n";
        } else {
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
                if ($photoCaption != '' and $photoCredit != '') {
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
            $html.= "\n" . '  <p><br /><span class="al">' . $previous . '<a class="n" href="' . $use . '.php">Index</a>' . $pipe . $next . '</span><br /></p>' . "\n";;
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
        if ($database != null) {
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
                extract($row);
                //
                // Set previous and next links
                //
                $order = [];
                if (is_null($editorView)) {
                    $stmt = $dbh->prepare('SELECT idArticle, sortOrderArticle FROM articles WHERE idSection = ? AND publicationDate <= "' . $datePost . '" AND "' . $datePost . '" <= endDate ORDER BY sortOrderArticle');
                } else {
                    $stmt = $dbh->prepare('SELECT idArticle, sortOrderArticle FROM articles WHERE idSection = ? ORDER BY sortOrderArticle');
                }
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$idSection]);
                foreach ($stmt as $row) {
                    $order[$row['sortOrderArticle']] = $row['idArticle'];
                }
                $dbh = null;
                $dbNumber = -1;
                if (isset($order[$sortOrderArticle - 1])) {
                    $previous = '<a class="n" href="' . $uri . $use . '.php?a=' . $order[$sortOrderArticle - 1] . '">Previous</a> | ';
                } else {
                    $previous = null;
                }
                if (isset($order[$sortOrderArticle + 1])) {
                    $next = '<a class="n" href="' . $uri . $use . '.php?a=' . $order[$sortOrderArticle + 1] . '">Next</a>';
                    $pipe = ' | ';
                } else {
                    $next = null;
                    $pipe = null;
                }
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
                $bylineDateTime = isset($publicationDate) ? date("l, F j, Y", strtotime($publicationDate)) : null;
                if (strpos($database, 'archive') === false) {
                    $html.= '  <p><span class="al">' . $previous . '<a class="n" href="' . $use . '.php">Index</a> | ' . $next . $pipe;
                    $html.= $links . '<a class="n" href="' . $uri . 'logout.php">Log out</a></span><br /></p>' . "\n\n";
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
                if ($photoCaption != '' and $photoCredit != '') {
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
                if ($survey == 1) {
                    //
                    // Tally the vote, display the survey results
                    //
                    $dbh = new PDO($dbSurvey);
                    $stmt = $dbh->prepare('SELECT count(idArticle) FROM tally WHERE idArticle=?');
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $stmt->execute([$aGet]);
                    $row = $stmt->fetch();
                    $total = $row['count(idArticle)'];
                    if ($total == 0) {
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
                        $html.= '<p><meter min="0" max="' . $total . '" value="' . $each . '" title="' . $answer . '">' . $answer . '"</meter> ' . $answer . ', ' . $each . "</p>\n\n";
                    }
                    $dbh = null;
                    $html.= '<form class="wait"></form>' . "\n\n";
                } else {
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
                            if ($database != null) {
                                $dbh = new PDO($database);
                                $stmt = $dbh->prepare('SELECT idPhoto, photoName, photoCredit, photoCaption FROM imageSecondary WHERE idArticle=? ORDER BY time');
                                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                                $stmt->execute([$idArticle]);
                                foreach ($stmt as $row) {
                                    if ($row) {
                                        $dbNumber2 = -1;
                                        extract($row);
                                        if ($photoName === 'third') {

                                            $html.= '  <p><img src="' . $imagePath2 . '?i=' . muddle($idPhoto) . 'h" class="t b" alt=""></p>' . "\n\n";
                                        } else {
                                            $html.= '  <p><img src="' . $imagePath2 . '?i=' . muddle($idPhoto) . 'h" class="w b" alt=""></p>' . "\n\n";
                                        }
                                        if ($photoCaption != '' and $photoCredit != '') {
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
                                }
                                $dbh = null;
                            }
                        }
                    } else {
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
                            if ($photoCaption != '' and $photoCredit != '') {
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
?>
