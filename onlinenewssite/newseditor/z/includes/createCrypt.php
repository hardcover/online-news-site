<?php
/**
 * Create crypt.php on the first run
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *            http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2018 01 08
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
if (!file_exists($includesPath . '/crypt.php')) {
    $content = '<?php' . "\n";
    $content.= '$onus = \'';
    $content.= 'setup';
    $content.= '\';' . "\n";
    $content.= '$gig = \'jntwzLW\';' . "\n";
    $content.= '?>' . "\n";
    file_put_contents($includesPath . '/crypt.php', $content);
}
?>
