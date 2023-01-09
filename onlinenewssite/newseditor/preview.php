<?php
/**
 * A view of the published articles for an input date
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2021 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2023 01 09
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
session_start();
require 'z/system/configuration.php';
require $includesPath . '/authorization.php';
require $includesPath . '/common.php';
require $includesPath . '/parsedown-master/Parsedown.php';
//
// Variables
//
$datePost = inlinePost('date');
//
if (empty($datePost)) {
    $datePost = $today;
}
$database = $dbPublished;
$database2 = $dbPublished2;
$editorView = null;
$imagePath = 'imagep.php';
$imagePath2 = 'imagep2.php';
$links = null;
$menu = "\n" . '  <nav class="n">
    <h4 class="m"><a class="m" href="edit.php">Edit</a><a class="m" href="published.php">Published</a><a class="s" href="preview.php">Preview</a><a class="m" href="archive.php">Archives</a></h4>
  </nav>' . "\n\n";
$message = '';
$publishedIndexAdminLinks = null;
$title = 'Preview';
$use = 'preview';
//
// HTML
//
require $includesPath . '/header1.inc';
echo '  <title>' . $title . "</title>\n";
?>
  <link rel="icon" type="image/png" href="images/32.png" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="generator" content="Online News Site Software, https://onlinenewssite.com/" />
  <link rel="stylesheet" type="text/css" href="z/jquery-ui.min.css" />
  <link rel="stylesheet" type="text/css" href="z/base.css" />
  <link rel="stylesheet" type="text/css" href="z/admin.css" />
  <script src="z/jquery.min.js"></script>
  <script src="z/jquery-ui.min.js"></script>
  <script src="z/datepicker.js"></script>
  <link rel="manifest" href="manifest.json">
  <link rel="apple-touch-icon" href="images/192.png">
</head>

<?php
require $includesPath . '/body.inc';
echo $menu;
echo '  <div class="column">' . "\n";
echo '    <h1>' . $title . "</h1>\n\n";
echoIfMessage($message);
echo '    <form method="post" action="' . $uri . 'preview.php">' . "\n";
echo '      <p><label for="date">Publication date</label><br />' . "\n";
echo '      <input id="date" name="date" type="text" class="datepicker date" value="' . $datePost . '" /> <input type="submit" class="button" value="Select starting date" /></p>' . "\n";
echo "    </form>\n";
require $includesPath . '/displayIndex.inc';
echo '  </div>' . "\n";
?>
</body>
</html>