<?php
/**
 * Create the crypt file on the first run
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2016 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *.@license   http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2016-09-19
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
if (!file_exists($includesPath . '/crypt.php')) {
    $adminPass = password_hash('admin', PASSWORD_DEFAULT);
    $content = '<?php' . "\n";
    $content.= '$hash = \'';
    $content.= password_hash('setup', PASSWORD_DEFAULT);
    $content.= '\';' . "\n";
    $content.= '$gig = \'jntwzLW\';' . "\n";
    $content.= '?>' . "\n";
    file_put_contents($includesPath . '/crypt.php', $content);
}
?>
