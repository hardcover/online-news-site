<?php
/**
 * The editing form used by subscribers with article-contribution privileges
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
$altPost = inlinePost('alt');
$bylineEdit = null;
$bylinePost = inlinePost('byline');
$dbFrom = null;
$dbTo = null;
$edit = inlinePost('edit');
$endDateEdit = null;
$endDatePost = inlinePost('endDate');
$expired = null;
$headlineEdit = null;
$headlinePost = securePost('headline');
$html = null;
$idArticle = inlinePost('idArticle');
$idArticleEdit = null;
$idSectionEdit = null;
$idSectionPost = inlinePost('idSection');
$keywordsEdit = null;
$keywordsPost = inlinePost('keywords');
$message = '';
$photoCaptionPost = inlinePost('photoCaption');
$photoCreditPost = inlinePost('photoCredit');
$publicationDateEdit = null;
$publicationDatePost = inlinePost('publicationDate');
$publishPost = inlinePost('publish');
$publishedPost = inlinePost('published');
$sortOrderArticleEdit = null;
$sortOrderArticlePost = time() + (15 * 60);
$standfirstEdit = null;
$standfirstPost = securePost('standfirst');
$textEdit = null;
$textPost = securePost('text');
$widthPost = inlinePost('width');
//
if ($widthPost === 'third') {
    $widthEditFull = null;
    $widthEditThird = ' checked';
} else {
    $widthEditFull = ' checked';
    $widthEditThird = null;
}
//
$dbEdit = $dbEdit;
$dbEdit2 = $dbEdit2;
$imagePath = 'imagee.php';
$imagePath2 = 'imagee2.php';
if (!isset($_FILES['image'])) {
    $_FILES['image'] = null;
}
if (mb_strlen($textPost) > 500) {
    $summaryPost = mb_substr(preg_replace("'\s+'", ' ', $textPost), 0, 500);
    $summaryPost = str_replace(mb_strrchr($summaryPost, ' '), ' ', $summaryPost);
} else {
    $summaryPost = $textPost;
}
if (mb_strpos($summaryPost, '<') === false and mb_strlen($summaryPost) > 1) {
    if (mb_substr($summaryPost, -1) === '.') {
        $summaryPost = $summaryPost . '...';
    } else {
        $summaryPost = $summaryPost . '....';
    }
} else {
    $summaryPost = null;
}
//
// Button: Add / Update
//
if (isset($_POST['addUpdate'])) {
    //
    // Create messages if the minimum fields are blank
    //
    if (empty($headlinePost)) {
        $message.= 'Headline is a required field. ';
    }
    if (empty($idSectionPost)) {
        $message.= 'Section is a required field. Define the newspaper sections in administrative settings.';
    }
    if (empty($message)) {
        //
        // Determine insert or update
        //
        if (empty($_POST['existing'])) {
            $dbh = new PDO($dbEdit);
            $stmt = $dbh->prepare('INSERT INTO articles (headline) VALUES (?)');
            $stmt->execute([null]);
            $idArticle = $dbh->lastInsertId();
            $stmt = $dbh->prepare('UPDATE articles SET idArticle=? WHERE rowid=?');
            $stmt->execute([$idArticle, $idArticle]);
            $dbh = null;
        }
        //
        // Store the image, if any
        //
        if ($_FILES['image']['size'] > 0 and $_FILES['image']['error'] === 0) {
            //
            // Verify JPG file
            //
            $sizes = getimagesize($_FILES['image']['tmp_name']);
            if ($sizes['mime'] === 'image/jpeg') {
                //
                // Variables
                //
                $widthOriginal = $sizes['0'];
                $heightOriginal = $sizes['1'];
                $aspectRatio = $widthOriginal / $heightOriginal;
                $widthHD = 2360;
                $heightHD = round($widthHD / $aspectRatio);
                //
                // Determine if the image is the primary image or a secondary image
                //
                $dbh = new PDO($dbEdit);
                $stmt = $dbh->prepare('SELECT originalImageWidth FROM articles WHERE idArticle=?');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute([$idArticle]);
                $row = $stmt->fetch();
                $dbh = null;
                if (empty($row['originalImageWidth'])) {
                    //
                    // Primary image
                    //
                    // Save the original image information
                    //
                    $dbh = new PDO($dbEdit);
                    $stmt = $dbh->prepare('UPDATE articles SET photoName=?, originalImageWidth=?, originalImageHeight=? WHERE idArticle=?');
                    $stmt->execute([$widthPost, $widthOriginal, $heightOriginal, $idArticle]);
                    $dbh = null;
                    //
                    // Create and save the thumbnail image
                    //
                    $heightThumbnail = 84 * 2; // Double pixels for 4K displays
                    $widthThumbnail = round($heightThumbnail * $aspectRatio);
                    $thumbnail = imagecreatetruecolor($widthThumbnail, $heightThumbnail);
                    imageinterlace($thumbnail, true);
                    $srcImage = imagecreatefromjpeg($_FILES['image']['tmp_name']);
                    imagecopyresampled($thumbnail, $srcImage, 0, 0, 0, 0, $widthThumbnail, $heightThumbnail, ImageSX($srcImage), ImageSY($srcImage));
                    ob_start();
                    imagejpeg($thumbnail, null, 90);
                    imagedestroy($thumbnail);
                    $thumbnailImage = ob_get_contents();
                    ob_end_clean();
                    $heightThumbnail = $heightThumbnail / 2; // Display size
                    $widthThumbnail = round($widthThumbnail / 2);
                    $dbh = new PDO($dbEdit);
                    $stmt = $dbh->prepare('UPDATE articles SET thumbnailImage=?, thumbnailImageWidth=?, thumbnailImageHeight=? WHERE idArticle=?');
                    $stmt->execute([$thumbnailImage, $widthThumbnail, $heightThumbnail, $idArticle]);
                    $dbh = null;
                    //
                    // Create and save the HD image, photo credit and caption
                    //
                    $hd = imagecreatetruecolor($widthHD, $heightHD);
                    imageinterlace($hd, true);
                    $srcImage = imagecreatefromjpeg($_FILES['image']['tmp_name']);
                    imagecopyresampled($hd, $srcImage, 0, 0, 0, 0, $widthHD, $heightHD, ImageSX($srcImage), ImageSY($srcImage));
                    ob_start();
                    imagejpeg($hd, null, 83);
                    imagedestroy($hd);
                    $hdImage = ob_get_contents();
                    ob_end_clean();
                    if (empty($altPost)) {
                        $altPost = $photoCaptionPost;
                    }
                    $dbh = new PDO($dbEdit);
                    $stmt = $dbh->prepare('UPDATE articles SET photoCredit=?, photoCaption=?, alt=?, hdImage=?, hdImageWidth=?, hdImageHeight=? WHERE idArticle=?');
                    $stmt->execute([$photoCreditPost, $photoCaptionPost, $altPost, $hdImage, $widthHD, $heightHD, $idArticle]);
                    $dbh = null;
                } else {
                    //
                    // Secondary images
                    //
                    // Create and save the HD image, photo credit and caption
                    //
                    $hd = imagecreatetruecolor($widthHD, $heightHD);
                    imageinterlace($hd, true);
                    $srcImage = imagecreatefromjpeg($_FILES['image']['tmp_name']);
                    imagecopyresampled($hd, $srcImage, 0, 0, 0, 0, $widthHD, $heightHD, ImageSX($srcImage), ImageSY($srcImage));
                    ob_start();
                    imagejpeg($hd, null, 83);
                    imagedestroy($hd);
                    $hdImage = ob_get_contents();
                    ob_end_clean();
                    if (empty($altPost)) {
                        $altPost = $photoCaptionPost;
                    }
                    $dbh = new PDO($dbEdit2);
                    $stmt = $dbh->prepare('INSERT INTO imageSecondary (idPhoto) VALUES (?)');
                    $stmt->execute([null]);
                    $idPhoto = $dbh->lastInsertId();
                    $stmt = $dbh->prepare('UPDATE imageSecondary SET idPhoto=?, idArticle=?, image=?, photoName=?, photoCredit=?, photoCaption=?, alt=?, time=? WHERE  rowid=?');
                    $stmt->execute([$idPhoto, $idArticle, $hdImage, $widthPost, $photoCreditPost, $photoCaptionPost, $altPost, time(), $idPhoto]);
                    $dbh = null;
                }
            } else {
                $message = 'The uploaded file was not in the JPG format.';
            }
        } elseif (isset($_FILES['image']['error']) and $_FILES['image']['error'] === 1) {
            $message = 'The uploaded image exceeds the upload_max_filesize directive in php.ini.';
        } elseif (isset($_FILES['image']['error']) and ($_FILES['image']['error'] !== 4 and $_FILES['image']['error'] !== 1)) {
            $message = 'There was an unknown error with the uploaded image.';
        }
        //
        // Send the non-image information to the database
        //
        $dbh = new PDO($dbEdit);
        $stmt = $dbh->prepare('UPDATE articles SET userId=?, publicationDate=?, endDate=?, idSection=?, sortOrderArticle=?, byline=?, headline=?, standfirst=?, text=?, summary=? WHERE idArticle=?');
        $stmt->execute([$_SESSION['userId'], $publicationDatePost, $endDatePost, $idSectionPost, $sortOrderArticlePost, $bylinePost, $headlinePost, $standfirstPost, $textPost, $summaryPost, $idArticle]);
        $dbh = null;
    }
}
//
// Button: Delete photos
//
if (isset($_POST['deletePhoto']) and isset($idArticle)) {
    $dbh = new PDO($dbEdit);
    $stmt = $dbh->prepare('UPDATE articles SET photoName=?, photoCredit=?, photoCaption=?, alt=?, originalImageWidth=?, originalImageHeight=?, thumbnailImage=?, thumbnailImageWidth=?, thumbnailImageHeight=?, hdImage=?, hdImageWidth=?, hdImageHeight=? WHERE idArticle=?');
    $stmt->execute([null, null, null, null, null, null, null, null, null, null, null, null, $idArticle]);
    $dbh = null;
    $dbh = new PDO($dbEdit2);
    $stmt = $dbh->prepare('DELETE FROM imageSecondary WHERE idArticle=?');
    $stmt->execute([$idArticle]);
    $dbh = null;
}
//
// Button: Delete
//
if (isset($_POST['delete']) and isset($idArticle)) {
    $dbh = new PDO($dbEdit);
    $stmt = $dbh->prepare('DELETE FROM articles WHERE idArticle=?');
    $stmt->execute([$idArticle]);
    $dbh = null;
    $dbh = new PDO($dbEdit2);
    $stmt = $dbh->prepare('DELETE FROM imageSecondary WHERE idArticle=?');
    $stmt->execute([$idArticle]);
    $dbh = null;
}
//
// Button: Edit
//
if (isset($_POST['edit'])) {
    $dbh = new PDO($dbEdit);
    $stmt = $dbh->prepare('SELECT keywords, idSection, byline, headline, standfirst, text FROM articles WHERE idArticle=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$idArticle]);
    $row = $stmt->fetch();
    $dbh = null;
    extract($row);
    $bylineEdit = $byline;
    $headlineEdit = $headline;
    $idArticleEdit = $idArticle;
    $keywordsEdit = $keywords;
    $idSectionEdit = $idSection;
    $standfirstEdit = $standfirst;
    $textEdit = $text;
}
//
// HTML
//
echo '    <div class="main">' . "\n";
echoIfMessage($message);
echo "      <h1>Article contribution</h1>\n";
?>

      <p>Fifteen minutes from the last edit, the article becomes available to be sent to the editor after which it will no longer be available to edit here.</p>

      <form method="post" action="<?php echo $uri; ?>?m=article-contribution" enctype="multipart/form-data">
        <p><label for="byline">Byline</label><br>
        <input id="byline" name="byline" class="wide" <?php echoIfValue($bylineEdit); ?>><input type="hidden" name="idArticle" value="<?php echo $idArticleEdit; ?>"></p>

        <p><label for="idSection">Section</label><br>
        <select id="idSection" name="idSection">
<?php
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT idSection, section FROM sections ORDER BY sortOrderSection');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    $selected = $idSectionEdit === $row['idSection'] ? ' selected="selected"' : null;
    echo '        <option value="' . $row['idSection'] . '"' . $selected . '>' . $row['section'] . "</option>\n";
}
$dbh = null;
?>
        </select></p>

<?php
if ($use === 'published') {
    echo '    <p><span class="rp">Sort order within section<br>' . "\n";
    echo '    <select name="sortOrderArticle">' . "\n";
    $count = 1;
    $dbh = new PDO($dbPublished);
    $stmt = $dbh->query('SELECT DISTINCT sortOrderArticle FROM articles ORDER BY sortOrderArticle');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        extract($row);
        $selected = ($sortOrderArticle === $count and $sortOrderArticle === $sortOrderArticleEdit) ? ' selected="selected"' : null;
        echo '      <option value="' . $count . '"' . $selected . '>' . $count . '</option>' . "\n";
        $count++;
    }
    $dbh = null;
    echo '      <option value="' . $count . '">' . $count . '</option>' . "\n";
    echo "    </select></span></p>\n\n";
}
?>
        <p><label for="headline">Headline</label><br>
        <input id="headline" name="headline" class="wide" <?php echoIfValue($headlineEdit); ?>></p>

        <p><label for="standfirst">Standfirst</label><br>
        <input id="standfirst" name="standfirst" class="wide"<?php echoIfValue($standfirstEdit); ?>></p>

        <p><label for="text">Article text is entered in either HTML or the <a href="https://www.markdownguide.org/basic-syntax/" target="_blank">markdown syntax</a>. Enter iframe and video tags inside paragraph tags, for example, &lt;p&gt;&lt;iframe&gt;&lt;/iframe&gt;&lt;/p&gt;.</label><br>
        <textarea id="text" name="text" rows="9" class="wide"><?php echoIfText($textEdit); ?></textarea></p>

        <p><label for="image">Photo upload (JPG image only<?php uploadFilesizeMaximum(); ?>)</label><br>
        <input id="image" name="image" type="file" class="wide" accept="image/jpeg"></p>

        <p><label for="full"><input type="radio" name="width" id="full" value=""<?php echo $widthEditFull; ?>> Full width</label> <label for="third"><input type="radio" name="width" id="third" value="third"<?php echo $widthEditThird; ?>> One-third width</label></p>

        <p><label for="photoCaption">Photo caption</label><br>
        <input id="photoCaption" name="photoCaption" class="wide" autocomplete="on"></p>

        <p><label for="alt">Alt text (if different from the caption)</label><br>
        <input id="alt" name="alt" class="wide" autocomplete="on"></p>

        <p><label for="photoCredit">Photo credit</label><br>
        <input id="photoCredit" name="photoCredit" class="wide"></p>

        <p><input type="submit" class="button" value="Add / update" name="addUpdate"> <input type="submit" class="button" value="Delete photos" name="deletePhoto"><input type="hidden" name="existing"<?php echoIfValue($edit); ?>></p>
      </form>

      <p>When there are photos, upload the primary photo first. Then edit the article to upload additional photos one at a time. To correct any photo error — width, caption, credit, order — delete the photos and begin again.</p>

<?php
if (isset($_GET['t'])) {
    //
    // Display article
    //
    $dbh = new PDO($dbEdit);
    $stmt = $dbh->prepare('SELECT idArticle, survey, idSection, byline, headline, standfirst, text, photoName, photoCredit, photoCaption, alt, hdImage FROM articles WHERE idArticle = ?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute([$tGet]);
    $row = $stmt->fetch();
    if ($row) {
        extract($row);
        $html.= '      <h1>' . html($headline) . "</h1>\n\n";
        if (isset($standfirst)) {
            $html.= '      <h3>' . html($standfirst) . "</h3>\n\n";
        }
        if (isset($hdImage)) {
            if ($photoName === 'third') {
                $html.= '      <p class="a"><img src="' . $imagePath . '?i=' . muddle($idArticle) . 'h" class="third border mb" alt="' . $alt . '"></p>' . "\n\n";
            } else {
                $html.= '      <p><img src="' . $imagePath . '?i=' . muddle($idArticle) . 'h" class="wide border" alt="' . $alt . '"></p>' . "\n\n";
            }
        }
        if (!empty($photoCaption) and !empty($photoCredit)) {
            if ($photoName === 'third') {
                $html.= '      <h6 class="a">' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
            } else {
                $html.= '      <h6>' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
            }
        } elseif (isset($photoCaption)) {
            if ($photoName === 'third') {
                $html.= '      <h6 class="a">' . html($photoCaption) . "</h6>\n\n";
            } else {
                $html.= '      <h6>' . html($photoCaption) . "</h6>\n\n";
            }
        } elseif (isset($photoCredit)) {
            if ($photoName === 'third') {
                $html.= '      <h6 class="a">' . $photoCredit . "</h6>\n\n";
            } else {
                $html.= '      <h6>' . $photoCredit . "</h6>\n\n";
            }
        }
        if (!empty($byline) or !empty($bylineDateTime)) {
            $html.= '      <h5>';
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
        $html.= '      ' . $temp . "\n\n";
        $dbhEdit2 = new PDO($dbEdit2);
        $stmt = $dbhEdit2->prepare('SELECT idPhoto, photoName, photoCredit, photoCaption, alt FROM imageSecondary WHERE idArticle=? ORDER BY time');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idArticle]);
        foreach ($stmt as $row) {
            extract($row);
            if ($photoName === 'third') {
                $html.= '      <p class="a"><img src="' . $imagePath2 . '?i=' . muddle($idPhoto) . 'h" class="third border mb" alt="' . $alt . '"></p>' . "\n\n";
            } else {
                $html.= '      <p><img src="' . $imagePath2 . '?i=' . muddle($idPhoto) . 'h" class="wide border" alt="' . $alt . '"></p>' . "\n\n";
            }
            if (!empty($photoCaption) and !empty($photoCredit)) {
                if ($photoName === 'third') {
                    $html.= '  <h6 class="a">' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
                } else {
                    $html.= '      <h6>' . html($photoCaption) . ' (' . $photoCredit . ")</h6>\n\n";
                }
            } elseif (isset($photoCaption)) {
                if ($photoName === 'third') {
                    $html.= '  <h6 class="a">' . html($photoCaption) . "</h6>\n\n";
                } else {
                    $html.= '      <h6>' . html($photoCaption) . "</h6>\n\n";
                }
            } elseif (isset($photoCredit)) {
                if ($photoName === 'third') {
                    $html.= '      <h6 class="a">' . $photoCredit . "</h6>\n\n";
                } else {
                    $html.= '      <h6>' . $photoCredit . "</h6>\n\n";
                }
            }
        }
        $dbhEdit2 = null;
    }
    $dbh = null;
    $html.= '      <p><span class="fr"><a class="n" href="' . $uri . '?m=article-contribution">Index</a></span></p>' . "\n";
} else {
    //
    // Display article list
    //
    $dbhSection = new PDO($dbSettings);
    $stmt = $dbhSection->query('SELECT idSection, section FROM sections ORDER BY sortOrderSection');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    foreach ($stmt as $row) {
        extract($row);
        $dbh = new PDO($dbEdit);
        $stmt = $dbh->prepare('SELECT idSection FROM articles WHERE idSection=?');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute([$idSection]);
        $row = $stmt->fetch();
        if ($row) {
            $count = null;
            $html.= "\n" . '      <h4>' . $section . "</h4>\n\n";
            $stmt = $dbh->prepare('SELECT idArticle, headline, summary, thumbnailImageWidth, thumbnailImageHeight FROM articles WHERE idSection = ? AND userId=? ORDER BY idArticle DESC');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$idSection, $_SESSION['userId']]);
            foreach ($stmt as $row) {
                extract($row);
                if (isset($count)) {
                    $html.= "  <hr>\n\n";
                }
                $count++;
                $html.= '      <h2><a class="n" href="' . $uri . '?m=article-contribution&t=' . $idArticle . '">' . html($headline) . "</a></h2>\n\n";
                if (!empty($summary)) {
                    $html.= '      <p class="summary"><a href="' . $uri . '?m=article-contribution&t=' . $idArticle . '">';
                    if (!empty($thumbnailImageWidth)) {
                        $html.= '<img class="fr b" src="' . $imagePath . '?i=' . muddle($idArticle) . 't" width="' . $thumbnailImageWidth . '" height="' . $thumbnailImageHeight . '" alt="' . $alt . '">';
                    }
                    $summary = str_replace('*', '', $summary);
                    $html.= '</a>' . html($summary) . "</p>\n";
                }
                $html.= "\n" . '      <form action="' . $uri . '?m=article-contribution" method="post">' . "\n";
                $html.= '        <p> <input type="hidden" name="idArticle" value="' . $idArticle . '"><input type="submit" class="button" value="Delete" name="delete"> <input type="submit" class="button" value="Edit" name="edit"></p>' . "\n";
                $html.= "      </form>\n";
            }
        }
        $dbh = null;
    }
    $dbhSection = null;
}
echo $html;
echo '    </div>' . "\n";
?>
