<?php
/**
 * Predefined menu item: Contact us
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2021 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2023 01 02
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
//
// Variables
//
$headers = 'From: noreply@' . $_SERVER["HTTP_HOST"] . "\r\n";
$headers.= 'MIME-Version: 1.0' . "\r\n";
$headers.= 'Content-Type: text/plain; charset=utf-8; format=flowed' . "\r\n";
$headers.= 'Content-Transfer-Encoding: 7bit' . "\r\n";
$formPost = inlinePost('form');
$location = null;
$message = '';
//
$emailTo = null;
$information = null;
$dbh = new PDO($dbSettings);
$stmt = $dbh->prepare('SELECT emailClassified FROM alertClassified WHERE idClassified=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([1]);
$row = $stmt->fetch();
if ($row) {
    $emailTo = $row['emailClassified'] . "\r\n";
}
$stmt = $dbh->prepare('SELECT infoForms FROM forms WHERE idForm=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([1]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $temp = Parsedown::instance()->parse($row['infoForms']);
    $temp = str_replace("\n", "\n\n      ", $temp);
    $information = '      ' . $temp . "\n\n";
    $temp = null;
}
if (file_exists($includesPath . '/custom/programs/location.php')) {
    include $includesPath . '/custom/programs/location.php';
}
//
// Form select
//
if (empty($formPost)) {
    $html = '        <h3>Type of content</h3>

        <p><label for="birth"><input name="form" id="birth" type="radio" value="birth" required /> Birth announcement</label><br />
        <label for="engagement"><input name="form" id="engagement" type="radio" value="engagement" /> Engagement announcement</label><br />
        <label for="obituary"><input name="form" id="obituary" type="radio" value="obituary" /> Obituary announcement</label><br />
        <label for="wedding"><input name="form" id="wedding" type="radio" value="wedding" /> Wedding announcement</label><br /><br />
        <label for="calendar"><input name="form" id="calendar" type="radio" value="calendar" /> Calendar event</label><br />
        <label for="letter"><input name="form" id="letter" type="radio" value="letter" /> Letter to the editor</label><br />
        <label for="other"><input name="form" id="other" type="radio" value="other" /> Other</label><br /></p>

        <p><input name="type" type="submit" class="button" value="Select" /></p>' . "\n";
}
//
// Button: Select
//
if (isset($_POST['type']) and empty($formPost)) {
    $message = 'Type of content is required.';
}
//
// Birth announcement
//
if (isset($formPost) and $formPost === 'birth') {
    $emailEdit = null;
    $emailPost = inlinePost('email');
    $babyNameEdit = null;
    $babyNamePost = inlinePost('babyName');
    $birthdayEdit = null;
    $birthdayPost = inlinePost('birthday');
    $childrenEdit = null;
    $childrenPost = inlinePost('children');
    $femaleEdit = null;
    $grandparentsEdit = null;
    $grandparentsPost = inlinePost('grandparents');
    $greatGrandparentsEdit = null;
    $greatGrandparentsPost = inlinePost('greatGrandparents');
    $genderEdit = null;
    $genderPost = inlinePost('gender');
    $maleEdit = null;
    $parentNamesEdit = null;
    $parentNamesPost = inlinePost('parentNames');
    $residenceEdit = null;
    $residencePost = inlinePost('residence');
    $sizeEdit = null;
    $sizePost = inlinePost('size');
    $subject = 'Contact form: Birth announcement' . "\r\n";
    $telephoneEdit = null;
    $telephonePost = inlinePost('telephone');
    //
    // Birth announcement error messages
    //
    if (isset($_POST['submit']) and empty($babyNamePost)) {
        $message.= 'Full name of baby is required.<br />';
    }
    if (isset($_POST['submit']) and empty($genderPost)) {
        $message.= 'Gender is required.<br />';
    }
    if (isset($_POST['submit']) and empty($parentNamesPost)) {
        $message.= 'First and last names of parents are required.<br />';
    }
    if (isset($_POST['submit']) and empty($residencePost)) {
        $message.= 'Parents place of residence is required.<br />';
    }
    if (isset($_POST['submit']) and empty($sizePost)) {
        $message.= 'Weight and length of newborn are required.<br />';
    }
    if (isset($_POST['submit']) and empty($birthdayPost)) {
        $message.= 'Date and place of birth are required.<br />';
    }
    if (isset($_POST['submit']) and empty($childrenPost)) {
        $message.= 'Other children in the family and their ages are required.<br />';
    }
    if (isset($_POST['submit']) and empty($grandparentsPost)) {
        $message.= 'Grandparents (first and last names and places of residence) are required.<br />';
    }
    if (isset($_POST['submit']) and empty($greatGrandparentsPost)) {
        $message.= 'Great grandparents (first and last names and places of residence) are required.<br />';
    }
    if (isset($_POST['submit']) and empty($telephonePost)) {
        $message.= 'Telephone number is required.';
    }
    //
    // Send the information or reset the form
    //
    if (isset($_POST['submit']) and empty($message)) {
        $body = null;
        if (!empty($emailPost)) {
            $body.= 'Email' . "\n";
            $body.= $emailPost . "\n\n";
        }
        $body.= 'Full name of baby' . "\n";
        $body.= $babyNamePost . "\n\n";
        $body.= 'Gender' . "\n";
        if ($genderPost === 'male') {
            $body.= 'Male' . "\n\n";
        } else {
            $body.= 'Female' . "\n\n";
        }
        $body.= 'First and last names of parents' . "\n";
        $body.= $parentNamesPost . "\n\n";
        $body.= 'Parents place of residence' . "\n";
        $body.= $residencePost . "\n\n";
        $body.= 'Weight and length of newborn' . "\n";
        $body.= $sizePost . "\n\n";
        $body.= 'Date and place of birth' . "\n";
        $body.= $birthdayPost . "\n\n";
        $body.= 'Other children in the family and their ages' . "\n";
        $body.= $childrenPost . "\n\n";
        $body.= 'Grandparents (first and last names and places of residence)' . "\n";
        $body.= $grandparentsPost . "\n\n";
        $body.= 'Great grandparents (first and last names and places of residence)' . "\n";
        $body.= $greatGrandparentsPost . "\n\n";
        $body.= 'Telephone number' . "\n";
        $body.= $telephonePost . "\n\n";
        $body.= $location;
        mail($emailTo, $subject, $body, $headers);
        $message = 'The information was sent.';
    } else {
        $emailEdit = $emailPost;
        $babyNameEdit = $babyNamePost;
        $birthdayEdit = $birthdayPost;
        $childrenEdit = $childrenPost;
        $grandparentsEdit = $grandparentsPost;
        $greatGrandparentsEdit = $greatGrandparentsPost;
        $parentNamesEdit = $parentNamesPost;
        $residenceEdit = $residencePost;
        $sizeEdit = $sizePost;
        $telephoneEdit = $telephonePost;
        //
        $genderPost = inlinePost('gender');
        if ($genderPost === 'male') {
            $maleEdit = 1;
        } elseif ($genderPost === 'female') {
            $femaleEdit = 1;
        }
    }
    //
    // Birth announcement HTML
    //
    $html = '        <h3>Birth announcement</h3>

        <input type="hidden" name="form" value="' . $formPost . '" />

        <p><label for="title">Email (optional)<br />
        <input type="email" id="email" name="email" class="wide"' . returnIfValue($emailEdit) . ' /></label></p>

        <p><label for="babyName">Full name of baby<br />
        <input type="text" id="babyName" name="babyName" class="wide"' . returnIfValue($babyNameEdit) . ' required /></label></p>

        <p><label for="male"><input name="gender" id="male" type="radio" value="male"' . returnIfYes($maleEdit) . ' required /> Male</label><br />
        <label for="female"><input name="gender" id="female" type="radio" value="female"' . returnIfYes($femaleEdit) . ' /> Female</label></p>

        <p><label for="parentNames">First and last names of parents<br />
        <input type="text" id="parentNames" name="parentNames" class="wide"' . returnIfValue($parentNamesEdit) . ' required /></label></p>

        <p><label for="residence">Parents place of residence<br />
        <input type="text" id="residence" name="residence" class="wide"' . returnIfValue($residenceEdit) . ' required /></label></p>

        <p><label for="size">Weight and length of newborn<br />
        <input type="text" id="size" name="size" class="wide"' . returnIfValue($sizeEdit) . ' required /></label></p>

        <p><label for="birthday">Date and place of birth<br />
        <input type="text" id="birthday" name="birthday" class="wide"' . returnIfValue($birthdayEdit) . ' required /></label></p>

        <p><label for="children">Other children in the family and their ages</label><br />
        <textarea id="children" name="children" class="wide" required>' . returnIfText($childrenEdit) . '</textarea></p>

        <p><label for="grandparents">Grandparents (first and last names and places of residence)</label><br />
        <textarea id="grandparents" name="grandparents" class="wide" required>' . returnIfText($grandparentsEdit) . '</textarea></p>

        <p><label for="greatGrandparents">Great grandparents (first and last names and places of residence)</label><br />
        <textarea id="greatGrandparents" name="greatGrandparents" class="wide" required>' . returnIfText($greatGrandparentsEdit) . '</textarea></p>

        <p><label for="telephone">Telephone number in case we have questions<br />
        <input type="tel" id="telephone" name="telephone" class="wide"' . returnIfValue($telephoneEdit) . ' required /></label></p>

        <p><input name="submit" type="submit" class="button" value="Send announcement" /></p>' . "\n";
}
//
// Engagement announcement
//
if (isset($formPost) and $formPost === 'engagement') {
    $emailEdit = null;
    $emailPost = inlinePost('email');
    $dateEdit = null;
    $datePost = inlinePost('date');
    $mansEmployerEdit = null;
    $mansEmployerPost = inlinePost('mansEmployer');
    $mansNameEdit = null;
    $mansNamePost = inlinePost('mansName');
    $mansParentsEdit = null;
    $mansParentsPost = inlinePost('mansParents');
    $mansParentsResidenceEdit = null;
    $mansParentsResidencePost = inlinePost('mansParentsResidence');
    $mansResidenceEdit = null;
    $mansResidencePost = inlinePost('mansResidence');
    $mansSchoolsEdit = null;
    $mansSchoolsPost = inlinePost('mansSchools');
    $subject = 'Contact form: Engagement announcement' . "\r\n";
    $telephoneEdit = null;
    $telephonePost = inlinePost('telephone');
    $weddingPlaceEdit = null;
    $weddingPlacePost = inlinePost('weddingPlace');
    $womansEmployerEdit = null;
    $womansEmployerPost = inlinePost('womansEmployer');
    $womansNameEdit = null;
    $womansNamePost = inlinePost('womansName');
    $womansParentsEdit = null;
    $womansParentsPost = inlinePost('womansParents');
    $womansParentsResidenceEdit = null;
    $womansParentsResidencePost = inlinePost('womansParentsResidence');
    $womansResidenceEdit = null;
    $womansResidencePost = inlinePost('womansResidence');
    $womansSchoolsEdit = null;
    $womansSchoolsPost = inlinePost('womansSchools');
    //
    // Engagement announcement error messages
    //
    if (isset($_POST['submit']) and empty($datePost)) {
        $message.= 'Date of wedding is required.<br />';
    }
    if (isset($_POST['submit']) and empty($weddingPlacePost)) {
        $message.= 'Place of wedding is required.<br />';
    }
    if (isset($_POST['submit']) and empty($womansNamePost)) {
        $message.= 'Woman\'s full name is required.<br />';
    }
    if (isset($_POST['submit']) and empty($womansResidencePost)) {
        $message.= 'Woman\'s place of residence is required.<br />';
    }
    if (isset($_POST['submit']) and empty($womansParentsPost)) {
        $message.= 'Names of woman\'s parents are required.<br />';
    }
    if (isset($_POST['submit']) and empty($womansParentsResidencePost)) {
        $message.= 'Place or places of residence of woman\'s parents is required.<br />';
    }
    if (isset($_POST['submit']) and empty($mansNamePost)) {
        $message.= 'Man\'s full name is required.<br />';
    }
    if (isset($_POST['submit']) and empty($mansResidencePost)) {
        $message.= 'Man\'s place of residence is required.<br />';
    }
    if (isset($_POST['submit']) and empty($mansParentsPost)) {
        $message.= 'Names of man\'s parents are required.<br />';
    }
    if (isset($_POST['submit']) and empty($mansParentsResidencePost)) {
        $message.= 'Place or places of residence of man\'s parents is required.<br />';
    }
    if (isset($_POST['submit']) and empty($womansSchoolsPost)) {
        $message.= 'Schools woman has attended, year of graduation, degrees received are required.<br />';
    }
    if (isset($_POST['submit']) and empty($womansEmployerPost)) {
        $message.= 'Woman\'s place of employment or school currently attending is required.<br />';
    }
    if (isset($_POST['submit']) and empty($mansSchoolsPost)) {
        $message.= 'Schools man has attended, year of graduation, degrees received are required.<br />';
    }
    if (isset($_POST['submit']) and empty($mansEmployerPost)) {
        $message.= 'Man\'s place of employment or school currently attending is required.';
    }
    if (isset($_POST['submit']) and empty($telephonePost)) {
        $message.= 'Telephone number is required.<br />';
    }
    //
    // Send the information or reset the form
    //
    if (isset($_POST['submit']) and empty($message)) {
        $body = null;
        if (!empty($emailPost)) {
            $body.= 'Email' . "\n";
            $body.= $emailPost . "\n\n";
        }
        $body.= 'Date of wedding' . "\n";
        $body.= $datePost . "\n\n";
        $body.= 'Place of wedding' . "\n";
        $body.= $weddingPlacePost . "\n\n";
        $body.= 'Woman\'s full name' . "\n";
        $body.= $womansNamePost . "\n\n";
        $body.= 'Woman\'s place of residence' . "\n";
        $body.= $womansResidencePost . "\n\n";
        $body.= 'Names of woman\'s parents' . "\n";
        $body.= $womansParentsPost . "\n\n";
        $body.= 'Place or places of residence of woman\'s parents' . "\n";
        $body.= $womansParentsResidencePost . "\n\n";
        $body.= 'Man\'s full name' . "\n";
        $body.= $mansNamePost . "\n\n";
        $body.= 'Man\'s place of residence' . "\n";
        $body.= $mansResidencePost . "\n\n";
        $body.= 'Names of man\'s parents' . "\n";
        $body.= $mansParentsPost . "\n\n";
        $body.= 'Place or places of residence of man\'s parents' . "\n";
        $body.= $mansParentsResidencePost . "\n\n";
        $body.= 'Schools woman has attended, year of graduation, degrees received' . "\n";
        $body.= $womansSchoolsPost . "\n\n";
        $body.= 'Woman\'s place of employment or school currently attending' . "\n";
        $body.= $womansEmployerPost . "\n\n";
        $body.= 'Schools man has attended, year of graduation, degrees received' . "\n";
        $body.= $mansSchoolsPost . "\n\n";
        $body.= 'Man\'s place of employment or school currently attending' . "\n";
        $body.= $mansEmployerPost . "\n\n";
        $body.= 'Telephone number' . "\n";
        $body.= $telephonePost . "\n\n";
        $body.= $location;
        mail($emailTo, $subject, $body, $headers);
        $message = 'The information was sent.';
    } else {
        $emailEdit = $emailPost;
        $dateEdit = $datePost;
        $mansEmployerEdit = $mansEmployerPost;
        $mansNameEdit = $mansNamePost;
        $mansParentsEdit = $mansParentsPost;
        $mansParentsResidenceEdit = $mansParentsResidencePost;
        $mansResidenceEdit = $mansResidencePost;
        $mansSchoolsEdit = $mansSchoolsPost;
        $telephoneEdit = $telephonePost;
        $weddingPlaceEdit = $weddingPlacePost;
        $womansEmployerEdit = $womansEmployerPost;
        $womansNameEdit = $womansNamePost;
        $womansParentsEdit = $womansParentsPost;
        $womansParentsResidenceEdit = $womansParentsResidencePost;
        $womansResidenceEdit = $womansResidencePost;
        $womansSchoolsEdit = $womansSchoolsPost;
    }
    //
    // Engagement announcement HTML
    //
    $html = '        <h3>Engagement announcement</h3>

        <input type="hidden" name="form" value="' . $formPost . '" />

        <p><label for="title">Email (optional)<br />
        <input type="email" id="email" name="email" class="wide"' . returnIfValue($emailEdit) . ' /></label></p>

        <p><label for="date">Date of wedding<br />
        <input type="date" id="date" name="date" class="wide"' . returnIfValue($dateEdit) . ' required /></label></p>

        <p><label for="weddingPlace">Place of wedding<br />
        <input type="text" id="weddingPlace" name="weddingPlace" class="wide"' . returnIfValue($weddingPlaceEdit) . ' required /></label></p>

        <p><label for="womansName">Woman\'s full name<br />
        <input type="text" id="womansName" name="womansName" class="wide"' . returnIfValue($womansNameEdit) . ' required /></label></p>

        <p><label for="womansResidence">Woman\'s place of residence<br />
        <input type="text" id="womansResidence" name="womansResidence" class="wide"' . returnIfValue($womansResidenceEdit) . ' required /></label></p>

        <p><label for="womansParents">Names of woman\'s parents<br />
        <input type="text" id="womansParents" name="womansParents" class="wide"' . returnIfValue($womansParentsEdit) . ' required /></label></p>

        <p><label for="womansParentsResidence">Place or places of residence of woman\'s parents</label><br />
        <input type="text" id="womansParentsResidence" name="womansParentsResidence" class="wide"' . returnIfValue($womansParentsResidenceEdit) . ' required /></label></p>

        <p><label for="mansName">Man\'s full name</label><br />
        <input type="text" id="mansName" name="mansName" class="wide"' . returnIfValue($mansNameEdit) . ' required /></label></p>

        <p><label for="mansResidence">Man\'s place of residence<br />
        <input type="text" id="mansResidence" name="mansResidence" class="wide"' . returnIfValue($mansResidenceEdit) . ' required /></label></p>

        <p><label for="mansParents">Names of man\'s parents<br />
        <input type="text" id="mansParents" name="mansParents" class="wide"' . returnIfValue($mansParentsEdit) . ' required /></label></p>

        <p><label for="mansParentsResidence">Place or places of residence of man\'s parents<br />
        <input type="text" id="mansParentsResidence" name="mansParentsResidence" class="wide"' . returnIfValue($mansParentsResidenceEdit) . ' required /></label></p>

        <p><label for="womansSchools">Schools woman has attended, year of graduation, degrees received<br />
        <input type="text" id="womansSchools" name="womansSchools" class="wide"' . returnIfValue($womansSchoolsEdit) . ' required /></label></p>

        <p><label for="womansEmployer">Woman\'s place of employment or school currently attending<br />
        <input type="text" id="womansEmployer" name="womansEmployer" class="wide"' . returnIfValue($womansEmployerEdit) . ' required /></label></p>

        <p><label for="mansSchools">Schools man has attended, year of graduation, degrees received<br />
        <input type="text" id="mansSchools" name="mansSchools" class="wide"' . returnIfValue($mansSchoolsEdit) . ' required /></label></p>

        <p><label for="mansEmployer">Man\'s place of employment or school currently attending<br />
        <input type="text" id="mansEmployer" name="mansEmployer" class="wide"' . returnIfValue($mansEmployerEdit) . ' required /></label></p>

        <p><label for="telephone">Telephone number in case we have questions<br />
        <input type="tel" id="telephone" name="telephone" class="wide"' . returnIfValue($telephoneEdit) . ' required /></label></p>

        <p><input name="submit" type="submit" class="button" value="Send announcement" /></p>' . "\n";
}
//
// Obituary announcement
//
if (isset($formPost) and $formPost === 'obituary') {
    $emailEdit = null;
    $emailPost = inlinePost('email');
    $ageEdit = null;
    $agePost = inlinePost('age');
    $arrangementsByEdit = null;
    $arrangementsByPost = inlinePost('arrangementsBy');
    $birthDateEdit = null;
    $birthDatePost = inlinePost('birthDate');
    $birthPlaceEdit = null;
    $birthPlacePost = inlinePost('birthPlace');
    $burialEdit = null;
    $burialInurnmentEdit = null;
    $burialInurnmentPost = inlinePost('burialInurnment');
    $burialPlaceEdit = null;
    $burialPlacePost = inlinePost('burialPlace');
    $deathCauseEdit = null;
    $deathCausePost = inlinePost('deathCause');
    $deathPlaceEdit = null;
    $deathPlacePost = inlinePost('deathPlace');
    $deathTimeEdit = null;
    $deathTimePost = inlinePost('deathTime');
    $educationEdit = null;
    $educationPost = inlinePost('education');
    $employmentEdit = null;
    $employmentPost = inlinePost('employment');
    $funeralEdit = null;
    $genderEdit = null;
    $genderPost = inlinePost('gender');
    $gravesideEdit = null;
    $interestsEdit = null;
    $interestsPost = inlinePost('interests');
    $inurnmentEdit = null;
    $marriageDateEdit = null;
    $marriageDatePost = inlinePost('marriageDate');
    $marriageEdit = null;
    $marriagePlaceEdit = null;
    $marriagePlacePost = inlinePost('marriagePlace');
    $marriagePost = inlinePost('marriage');
    $marriagePreviousEdit = null;
    $marriagePreviousPost = inlinePost('marriagePrevious');
    $memorialDonationsEdit = null;
    $memorialDonationsPost = inlinePost('memorialDonations');
    $memorialEdit = null;
    $militaryEdit = null;
    $militaryPost = inlinePost('military');
    $militaryRankEdit = null;
    $militaryRankPost = inlinePost('militaryRank');
    $nameEdit = null;
    $namePost = inlinePost('name');
    $parentsEdit = null;
    $parentsPost = inlinePost('parents');
    $phoneEdit = null;
    $phonePost = inlinePost('phone');
    $precededByEdit = null;
    $precededByPost = inlinePost('precededBy');
    $residenceLengthEdit = null;
    $residenceLengthPost = inlinePost('residenceLength');
    $residencePlaceEdit = null;
    $residencePlacePost = inlinePost('residencePlace');
    $serviceOfficiantEdit = null;
    $serviceOfficiantPost = inlinePost('serviceOfficiant');
    $servicePlaceEdit = null;
    $servicePlacePost = inlinePost('servicePlace');
    $serviceTimeEdit = null;
    $serviceTimePost = inlinePost('serviceTime');
    $servicesEdit = null;
    $servicesPost = inlinePost('services');
    $subject = 'Contact form: Obituary announcement' . "\r\n";
    $survivorsEdit = null;
    $survivorsPost = inlinePost('survivors');
    //
    if ($servicesPost === 'graveside') {
        $gravesideEdit = 1;
    } elseif ($servicesPost === 'memorial') {
        $memorialEdit = 1;
    } elseif ($servicesPost === 'funeral') {
        $funeralEdit = 1;
    }
    //
    if ($burialInurnmentPost === 'burial') {
        $burialEdit = 1;
    } elseif ($burialInurnmentPost === 'inurnment') {
        $inurnmentEdit = 1;
    }
    //
    // Obituary error messages
    //
    if (isset($_POST['submit']) and empty($namePost)) {
        $message.= 'Name is required.<br />';
    }
    if (isset($_POST['submit']) and empty($genderPost)) {
        $message.= 'Sex is required.<br />';
    }
    if (isset($_POST['submit']) and empty($agePost)) {
        $message.= 'Age is required.<br />';
    }
    if (isset($_POST['submit']) and empty($residencePlacePost)) {
        $message.= 'Place of residence is required.<br />';
    }
    if (isset($_POST['submit']) and empty($residenceLengthPost)) {
        $message.= 'Length of residence is required.<br />';
    }
    if (isset($_POST['submit']) and empty($deathPlacePost)) {
        $message.= 'Place of death is required.<br />';
    }
    if (isset($_POST['submit']) and empty($deathTimePost)) {
        $message.= 'Date, weekday of death is required.<br />';
    }
    if (isset($_POST['submit']) and empty($deathCausePost)) {
        $message.= 'Cause of death is required.<br />';
    }
    if (isset($_POST['submit']) and empty($birthDatePost)) {
        $message.= 'Date of birth is required.<br />';
    }
    if (isset($_POST['submit']) and empty($birthPlacePost)) {
        $message.= 'Place of birth is required.<br />';
    }
    if (isset($_POST['submit']) and empty($parentsPost)) {
        $message.= 'Parents are required.<br />';
    }
    if (isset($_POST['submit']) and empty($educationPost)) {
        $message.= 'Education is required.<br />';
    }
    if (isset($_POST['submit']) and empty($militaryPost)) {
        $message.= 'Military service and dates are required.<br />';
    }
    if (isset($_POST['submit']) and empty($militaryRankPost)) {
        $message.= 'Rank on discharge is required.<br />';
    }
    if (isset($_POST['submit']) and empty($marriagePost)) {
        $message.= 'Marriage is required.<br />';
    }
    if (isset($_POST['submit']) and empty($marriagePlacePost)) {
        $message.= 'Marriage place is required.<br />';
    }
    if (isset($_POST['submit']) and empty($marriageDatePost)) {
        $message.= 'Marriage date is required.<br />';
    }
    if (isset($_POST['submit']) and empty($marriagePreviousPost)) {
        $message.= 'Previous marriage is required.<br />';
    }
    if (isset($_POST['submit']) and empty($employmentPost)) {
        $message.= 'Occupations and Employment are required.<br />';
    }
    if (isset($_POST['submit']) and empty($interestsPost)) {
        $message.= 'Interests and memberships are required.<br />';
    }
    if (isset($_POST['submit']) and empty($survivorsPost)) {
        $message.= 'Survivors are required.<br />';
    }
    if (isset($_POST['submit']) and empty($precededByPost)) {
        $message.= 'Preceded in death is required.<br />';
    }
    if (isset($_POST['submit']) and empty($servicesPost)) {
        $message.= 'Services is required.<br />';
    }
    if (isset($_POST['submit']) and empty($servicePlacePost)) {
        $message.= 'Place of service is required.<br />';
    }
    if (isset($_POST['submit']) and empty($serviceTimePost)) {
        $message.= 'Date and time of service is required.<br />';
    }
    if (isset($_POST['submit']) and empty($serviceOfficiantPost)) {
        $message.= 'Officiant at service is required.<br />';
    }
    if (isset($_POST['submit']) and empty($burialInurnmentPost)) {
        $message.= 'Burial or inurnment is required.<br />';
    }
    if (isset($_POST['submit']) and empty($burialPlacePost)) {
        $message.= 'Place of burial or inurnment is required.<br />';
    }
    if (isset($_POST['submit']) and empty($arrangementsByPost)) {
        $message.= 'Arrangements are under the direction of is required.<br />';
    }
    if (isset($_POST['submit']) and empty($phonePost)) {
        $message.= 'Phone number is required.<br />';
    }
    if (isset($_POST['submit']) and empty($memorialDonationsPost)) {
        $message.= 'Memorial donations to is required.<br />';
    }
    //
    // Send the information or reset the form
    //
    if (isset($_POST['submit']) and empty($message)) {
        $body = null;
        if (!empty($emailPost)) {
            $body.= 'Email' . "\n";
            $body.= $emailPost . "\n\n";
        }
        $body.= 'Name' . "\n";
        $body.= $namePost . "\n\n";
        $body.= 'Sex' . "\n";
        $body.= $genderPost . "\n\n";
        $body.= 'Age' . "\n";
        $body.= $agePost . "\n\n";
        $body.= 'Place of residence' . "\n";
        $body.= $residencePlacePost . "\n\n";
        $body.= 'Length of residence' . "\n";
        $body.= $residenceLengthPost . "\n\n";
        $body.= 'Place of death' . "\n";
        $body.= $deathPlacePost . "\n\n";
        $body.= 'Date, weekday of death' . "\n";
        $body.= $deathTimePost . "\n\n";
        $body.= 'Cause of death' . "\n";
        $body.= $deathCausePost . "\n\n";
        $body.= 'Date of birth' . "\n";
        $body.= $birthDatePost . "\n\n";
        $body.= 'Place of birth' . "\n";
        $body.= $birthPlacePost . "\n\n";
        $body.= 'Parents (mother\'s maiden name in parentheses)' . "\n";
        $body.= $parentsPost . "\n\n";
        $body.= 'Education' . "\n";
        $body.= $educationPost . "\n\n";
        $body.= 'Military service and dates' . "\n";
        $body.= $militaryPost . "\n\n";
        $body.= 'Rank on discharge' . "\n";
        $body.= $militaryRankPost . "\n\n";
        $body.= 'Marriage' . "\n";
        $body.= $marriagePost . "\n\n";
        $body.= 'Marriage place' . "\n";
        $body.= $marriagePlacePost . "\n\n";
        $body.= 'Marriage date' . "\n";
        $body.= $marriageDatePost . "\n\n";
        $body.= 'Previous marriage' . "\n";
        $body.= $marriagePreviousPost . "\n\n";
        $body.= 'Occupations and employment' . "\n";
        $body.= $employmentPost . "\n\n";
        $body.= 'Interests and memberships' . "\n";
        $body.= $interestsPost . "\n\n";
        $body.= 'Survivors' . "\n";
        $body.= $survivorsPost . "\n\n";
        $body.= 'Preceded in death by' . "\n";
        $body.= $precededByPost . "\n\n";
        $body.= 'Services' . "\n";
        $body.= $servicesPost . "\n\n";
        $body.= 'Place of service' . "\n";
        $body.= $servicePlacePost . "\n\n";
        $body.= 'Date and time of service' . "\n";
        $body.= $serviceTimePost . "\n\n";
        $body.= 'Officiant at service' . "\n";
        $body.= $serviceOfficiantPost . "\n\n";
        $body.= 'Burial or inurnment' . "\n";
        $body.= $burialInurnmentPost . "\n\n";
        $body.= 'Place of burial or inurnment' . "\n";
        $body.= $burialPlacePost . "\n\n";
        $body.= 'Arrangements are under the direction of' . "\n";
        $body.= $arrangementsByPost . "\n\n";
        $body.= 'Phone number in case we have questions' . "\n";
        $body.= $phonePost . "\n\n";
        $body.= 'Memorial donations to' . "\n";
        $body.= $memorialDonationsPost . "\n\n";
        $body.= $location;
        mail($emailTo, $subject, $body, $headers);
        $message = 'The information was sent.';
    } else {
        $emailEdit = $emailPost;
        $nameEdit = $namePost;
        $genderEdit = $genderPost;
        $ageEdit = $agePost;
        $residencePlaceEdit = $residencePlacePost;
        $residenceLengthEdit = $residenceLengthPost;
        $deathPlaceEdit = $deathPlacePost;
        $deathTimeEdit = $deathTimePost;
        $deathCauseEdit = $deathCausePost;
        $birthDateEdit = $birthDatePost;
        $birthPlaceEdit = $birthPlacePost;
        $parentsEdit = $parentsPost;
        $educationEdit = $educationPost;
        $militaryEdit = $militaryPost;
        $militaryRankEdit = $militaryRankPost;
        $marriageEdit = $marriagePost;
        $marriagePlaceEdit = $marriagePlacePost;
        $marriageDateEdit = $marriageDatePost;
        $marriagePreviousEdit = $marriagePreviousPost;
        $employmentEdit = $employmentPost;
        $interestsEdit = $interestsPost;
        $survivorsEdit = $survivorsPost;
        $precededByEdit = $precededByPost;
        $servicesEdit = $servicesPost;
        $servicePlaceEdit = $servicePlacePost;
        $serviceTimeEdit = $serviceTimePost;
        $serviceOfficiantEdit = $serviceOfficiantPost;
        $burialInurnmentEdit = $burialInurnmentPost;
        $burialPlaceEdit = $burialPlacePost;
        $arrangementsByEdit = $arrangementsByPost;
        $phoneEdit = $phonePost;
        $memorialDonationsEdit = $memorialDonationsPost;
    }
    //
    // Obituary HTML
    //
    $html = '        <h3>Obituary announcement</h3>

        <p>We do not charge for obituaries that run in the news columns. However, we reserve the right to treat obituaries as news and publish them written in newspaper style. Occasionally that is not acceptable to someone seeking to place an obituary, who insists it must be published word-for-word as submitted. The person then has the option of buying an advertisement that includes the text of the obituary. The ad will be placed within proximity of other obituaries in the newspaper.<input type="hidden" name="form" value="' . $formPost . '" /></p>

        <p><input type="hidden" name="form" value="' . $formPost . '" />

        <p><label for="title">Email (optional)<br />
        <input type="email" id="email" name="email" class="wide"' . returnIfValue($emailEdit) . ' /></label></p>

        <p><label for="name">Name<br />
        <input type="text" id="name" name="name" class="wide"' . returnIfValue($nameEdit) . ' required /></label></p>

        <p><label for="gender">Sex<br />
        <input type="text" id="gender" name="gender" class="wide"' . returnIfValue($genderEdit) . ' required /></label></p>

        <p><label for="age">Age<br />
        <input type="text" id="age" name="age" class="wide"' . returnIfValue($ageEdit) . ' required /></label></p>

        <p><label for="residencePlace">Place of residence<br />
        <input type="text" id="residencePlace" name="residencePlace" class="wide"' . returnIfValue($residencePlaceEdit) . ' required /></label></p>

        <p><label for="residenceLength">Length of residence<br />
        <input type="text" id="residenceLength" name="residenceLength" class="wide"' . returnIfValue($residenceLengthEdit) . ' required /></label></p>

        <p><label for="deathPlace">Place of death<br />
        <input type="text" id="deathPlace" name="deathPlace" class="wide"' . returnIfValue($deathPlaceEdit) . ' required /></label></p>

        <p><label for="deathTime">Date, weekday of death<br />
        <input type="text" id="deathTime" name="deathTime" class="wide"' . returnIfValue($deathTimeEdit) . ' required /></label></p>

        <p><label for="deathCause">Cause of death<br />
        <input type="text" id="deathCause" name="deathCause" class="wide"' . returnIfValue($deathCauseEdit) . ' required /></label></p>

        <p><label for="birthDate">Date of birth<br />
        <input type="text" id="birthDate" name="birthDate" class="wide"' . returnIfValue($birthDateEdit) . ' required /></label></p>

        <p><label for="birthPlace">Place of birth<br />
        <input type="text" id="birthPlace" name="birthPlace" class="wide"' . returnIfValue($birthPlaceEdit) . ' required /></label></p>

        <p><label for="parents">Parents (mother’s maiden name in parentheses)</label><br />
        <textarea id="parents" name="parents" class="wide" required>' . returnIfText($parentsEdit) . '</textarea></p>

        <p><label for="education">Education (if possible, high school attended and year of graduation as well as colleges and year of associate’s, bachelor’s and post- graduate degrees and discipline in which degree was earned)</label><br />
        <textarea id="education" name="education" class="wide" required>' . returnIfText($educationEdit) . '</textarea></p>

        <p><label for="military">Military service and dates<br />
        <input type="text" id="military" name="military" class="wide"' . returnIfValue($militaryEdit) . ' required /></label></p>

        <p><label for="militaryRank">Rank on discharge<br />
        <input type="text" id="militaryRank" name="militaryRank" class="wide"' . returnIfValue($militaryRankEdit) . ' required /></label></p>

        <p><label for="marriage">Marriage<br />
        <input type="text" id="marriage" name="marriage" class="wide"' . returnIfValue($marriageEdit) . ' required /></label></p>

        <p><label for="marriagePlace">Marriage place<br />
        <input type="text" id="marriagePlace" name="marriagePlace" class="wide"' . returnIfValue($marriagePlaceEdit) . ' required /></label></p>

        <p><label for="marriageDate">Marriage date<br />
        <input type="text" id="marriageDate" name="marriageDate" class="wide"' . returnIfValue($marriageDateEdit) . ' required /></label></p>

        <p><label for="marriagePrevious">Previous marriage (specify how ended: i.e. divorce, death and date)<br />
        <input type="text" id="marriagePrevious" name="marriagePrevious" class="wide"' . returnIfValue($marriagePreviousEdit) . ' required /></label></p>

        <p><label for="employment">Occupations and Employment</label><br />
        <textarea id="employment" name="employment" class="wide" required>' . returnIfText($employmentEdit) . '</textarea></p>

        <p><label for="interests">Interests and memberships (hobbies, pastimes, community activities, club, church affiliations, special honors)</label><br />
        <textarea id="interests" name="interests" class="wide" required>' . returnIfText($interestsEdit) . '</textarea></p>

        <p><label for="survivors">Survivors - Relationship, Full Name, Place of Residence</label><br />
        <textarea id="survivors" name="survivors" class="wide" required>' . returnIfText($survivorsEdit) . '</textarea></p>

        <p><label for="precededBy">Preceded in death by (name and relationship)<br />
        <input type="text" id="precededBy" name="precededBy" class="wide"' . returnIfValue($precededByEdit) . ' required /></label></p>

        <p>Services (check one)<br />
        <label for="graveside"><input name="services" id="graveside" type="radio" value="graveside"' . returnIfYes($gravesideEdit) . ' required /> Graveside</label><br />
        <label for="memorial"><input name="services" id="memorial" type="radio" value="memorial"' . returnIfYes($memorialEdit) . ' /> Memorial</label><br />
        <label for="funeral"><input name="services" id="funeral" type="radio" value="funeral"' . returnIfYes($funeralEdit) . ' /> Funeral</label></p>

        <p><label for="servicePlace">Place of service<br />
        <input type="text" id="servicePlace" name="servicePlace" class="wide"' . returnIfValue($servicePlaceEdit) . ' required /></label></p>

        <p><label for="serviceTime">Date and time of service<br />
        <input type="text" id="serviceTime" name="serviceTime" class="wide"' . returnIfValue($serviceTimeEdit) . ' required /></label></p>

        <p><label for="serviceOfficiant">Officiant at service<br />
        <input type="text" id="serviceOfficiant" name="serviceOfficiant" class="wide"' . returnIfValue($serviceOfficiantEdit) . ' required /></label></p>

        <p>Burial or inurnment (check one)<br />
        <label for="burial"><input name="burialInurnment" id="burial" type="radio" value="burial"' . returnIfYes($burialEdit) . ' required /> Burial</label><br />
        <label for="inurnment"><input name="burialInurnment" id="inurnment" type="radio" value="inurnment"' . returnIfYes($inurnmentEdit) . ' /> Inurnment</label></p>

        <p><label for="burialPlace">Place of burial or inurnment<br />
        <input type="text" id="burialPlace" name="burialPlace" class="wide"' . returnIfValue($burialPlaceEdit) . ' required /></label></p>

        <p><label for="arrangementsBy">Arrangements are under the direction of<br />
        <input type="text" id="arrangementsBy" name="arrangementsBy" class="wide"' . returnIfValue($arrangementsByEdit) . ' required /></label></p>

        <p><label for="phone">Phone number in case we have questions<br />
        <input type="text" id="phone" name="phone" class="wide"' . returnIfValue($phoneEdit) . ' required /></label></p>

        <p><label for="memorialDonations">Memorial donations to (include address)<br />
        <input type="text" id="memorialDonations" name="memorialDonations" class="wide"' . returnIfValue($memorialDonationsEdit) . ' required /></label></p>

        <p><input name="submit" type="submit" class="button" value="Send obituary" /></p>' . "\n";
}
//
// Wedding announcement
//
if (isset($formPost) and $formPost === 'wedding') {
    $emailEdit = null;
    $emailPost = inlinePost('email');
    $maidenNameEdit = null;
    $maidenNamePost = inlinePost('maidenName');
    $brideParentsEdit = null;
    $brideParentsPost = inlinePost('brideParents');
    $bridegroomNameEdit = null;
    $bridegroomNamePost = inlinePost('bridegroomName');
    $bridegroomParentsEdit = null;
    $bridegroomParentsPost = inlinePost('bridegroomParents');
    $weddingPlaceEdit = null;
    $weddingPlacePost = inlinePost('weddingPlace');
    $dateHourEdit = null;
    $dateHourPost = inlinePost('dateHour');
    $ceremonyPerformerEdit = null;
    $ceremonyPerformerPost = inlinePost('ceremonyPerformer');
    $doubleRingEdit = null;
    $singleRingEdit = null;
    $noRingEdit = null;
    $ringPost = inlinePost('ring');
    $matronNameEdit = null;
    $matronNamePost = inlinePost('matronName');
    $matronEdit = null;
    $maidEdit = null;
    $noneEdit = null;
    $honorPost = inlinePost('honor');
    $bridesmaidNameEdit = null;
    $bridesmaidNamePost = inlinePost('bridesmaidName');
    $bestManEdit = null;
    $bestManPost = inlinePost('bestMan');
    $ushersGroomsmenEdit = null;
    $ushersGroomsmenPost = inlinePost('ushersGroomsmen');
    $otherParticipantsEdit = null;
    $otherParticipantsPost = inlinePost('otherParticipants');
    $musiciansEdit = null;
    $musiciansPost = inlinePost('musicians');
    $receptionEdit = null;
    $receptionPost = inlinePost('reception');
    $honoreesEdit = null;
    $honoreesPost = inlinePost('honorees');
    $brideInfoEdit = null;
    $brideInfoPost = inlinePost('brideInfo');
    $groomInfoEdit = null;
    $groomInfoPost = inlinePost('groomInfo');
    $tripEdit = null;
    $tripPost = inlinePost('trip');
    $tripDateEdit = null;
    $tripDatePost = inlinePost('tripDate');
    $residenceEdit = null;
    $residencePost = inlinePost('residence');
    $yesEdit = null;
    $noEdit = null;
    $picturePost = inlinePost('picture');
    $contactInfoEdit = null;
    $contactInfoPost = inlinePost('contactInfo');
    $subject = 'Contact form: Wedding announcement' . "\r\n";
    //
    // Wedding announcement error messages
    //
    if (isset($_POST['submit']) and empty($maidenNamePost)) {
        $message.= 'Maiden name of the bride and her residence is required.<br />';
    }
    if (isset($_POST['submit']) and empty($brideParentsPost)) {
        $message.= 'Bride\'s parents and residence is required.<br />';
    }
    if (isset($_POST['submit']) and empty($bridegroomNamePost)) {
        $message.= 'Bridegroom\'s name and residence is required.<br />';
    }
    if (isset($_POST['submit']) and empty($bridegroomParentsPost)) {
        $message.= 'Bridegroom\'s parents and residence is required.<br />';
    }
    if (isset($_POST['submit']) and empty($weddingPlacePost)) {
        $message.= 'Place of wedding is required.<br />';
    }
    if (isset($_POST['submit']) and empty($dateHourPost)) {
        $message.= 'Date and hour are required.<br />';
    }
    if (isset($_POST['submit']) and empty($ceremonyPerformerPost)) {
        $message.= 'Name of person performing ceremony is required.<br />';
    }
    if (isset($_POST['submit']) and empty($ringPost)) {
        $message.= 'Ring ceremony type is required.<br />';
    }
    if (isset($_POST['submit']) and empty($matronNamePost)) {
        $message.= 'Name of the matron or the maid of honor is required.<br />';
    }
    if (isset($_POST['submit']) and empty($honorPost)) {
        $message.= 'Matron or maid of honor is required.<br />';
    }
    if (isset($_POST['submit']) and empty($bridesmaidNamePost)) {
        $message.= 'Names of the bridesmaids are required.<br />';
    }
    if (isset($_POST['submit']) and empty($bestManPost)) {
        $message.= 'Name of the best man is required.<br />';
    }
    if (isset($_POST['submit']) and empty($ushersGroomsmenPost)) {
        $message.= 'Names of the ushers/groomsmen are required.<br />';
    }
    if (isset($_POST['submit']) and empty($otherParticipantsPost)) {
        $message.= 'Names of other participants and roles are required.<br />';
    }
    if (isset($_POST['submit']) and empty($musiciansPost)) {
        $message.= 'Musician(s) is required.<br />';
    }
    if (isset($_POST['submit']) and empty($receptionPost)) {
        $message.= 'Time and place of reception is required.<br />';
    }
    if (isset($_POST['submit']) and empty($honoreesPost)) {
        $message.= 'Honorees serving or assisting are required.<br />';
    }
    if (isset($_POST['submit']) and empty($brideInfoPost)) {
        $message.= 'Bride\'s schools, sororities, employment is required.<br />';
    }
    if (isset($_POST['submit']) and empty($groomInfoPost)) {
        $message.= 'Groom\'s schools, clubs, employment is required.<br />';
    }
    if (isset($_POST['submit']) and empty($tripPost)) {
        $message.= 'Destination of wedding trip is required.<br />';
    }
    if (isset($_POST['submit']) and empty($residencePost)) {
        $message.= 'Where couple will live is required.<br />';
    }
    if (isset($_POST['submit']) and empty($picturePost)) {
        $message.= 'Photo yes or no is required.<br />';
    }
    if (isset($_POST['submit']) and empty($contactInfoPost)) {
        $message.= 'Name and phone (between 8 and 5) of contact person for the story is required.<br />';
    }
    //
    // Send the information or reset the form
    //
    if (isset($_POST['submit']) and empty($message)) {
        $body = null;
        if (!empty($emailPost)) {
            $body.= 'Email' . "\n";
            $body.= $emailPost . "\n\n";
        }
        $body.= 'Maiden name of the bride and her residence' . "\n";
        $body.= $maidenNamePost . "\n\n";
        $body.= 'Bride\'s parents and residence' . "\n";
        $body.= $brideParentsPost . "\n\n";
        $body.= 'Bridegroom\'s name and residence' . "\n";
        $body.= $bridegroomNamePost . "\n\n";
        $body.= 'Bridegroom\'s parents and residence' . "\n";
        $body.= $bridegroomParentsPost . "\n\n";
        $body.= 'Place of wedding' . "\n";
        $body.= $weddingPlacePost . "\n\n";
        $body.= 'Date and hour' . "\n";
        $body.= $dateHourPost . "\n\n";
        $body.= 'Name of person performing ceremony' . "\n";
        $body.= $ceremonyPerformerPost . "\n\n";
        $body.= 'Ring ceremony type' . "\n";
        $body.= $ringPost . "\n\n";
        $body.= 'Name of the matron or the maid of honor' . "\n";
        $body.= $matronNamePost . "\n\n";
        $body.= 'Matron or maid of honor' . "\n";
        $body.= $honorPost . "\n\n";
        $body.= 'Names of the bridesmaids' . "\n";
        $body.= $bridesmaidNamePost . "\n\n";
        $body.= 'Name of the best man' . "\n";
        $body.= $bestManPost . "\n\n";
        $body.= 'Names of the ushers/groomsmen' . "\n";
        $body.= $ushersGroomsmenPost . "\n\n";
        $body.= 'Names of other participants and roles' . "\n";
        $body.= $otherParticipantsPost . "\n\n";
        $body.= 'Musician(s)' . "\n";
        $body.= $musiciansPost . "\n\n";
        $body.= 'Time and place of reception' . "\n";
        $body.= $receptionPost . "\n\n";
        $body.= 'Honorees serving or assisting' . "\n";
        $body.= $honoreesPost . "\n\n";
        $body.= 'Bride\'s schools, sororities, employment' . "\n";
        $body.= $brideInfoPost . "\n\n";
        $body.= 'Groom\'s schools, clubs, employment' . "\n";
        $body.= $groomInfoPost . "\n\n";
        $body.= 'Destination of wedding trip' . "\n";
        $body.= $tripPost . "\n\n";
        $body.= 'Where couple will live' . "\n";
        $body.= $residencePost . "\n\n";
        $body.= 'Photo yes or no' . "\n";
        $body.= $picturePost . "\n\n";
        $body.= 'Name and phone (between 8 and 5) of contact person' . "\n";
        $body.= $contactInfoPost . "\n\n";
        $body.= $location;
        mail($emailTo, $subject, $body, $headers);
        $message = 'The information was sent.';
    } else {
        $emailEdit = $emailPost;
        $maidenNameEdit = $maidenNamePost;
        $brideParentsEdit = $brideParentsPost;
        $bridegroomNameEdit = $bridegroomNamePost;
        $bridegroomParentsEdit = $bridegroomParentsPost;
        $weddingPlaceEdit = $weddingPlacePost;
        $dateHourEdit = $dateHourPost;
        $ceremonyPerformerEdit = $ceremonyPerformerPost;
        $matronNameEdit = $matronNamePost;
        $bridesmaidNameEdit = $bridesmaidNamePost;
        $bestManEdit = $bestManPost;
        $ushersGroomsmenEdit = $ushersGroomsmenPost;
        $otherParticipantsEdit = $otherParticipantsPost;
        $musiciansEdit = $musiciansPost;
        $receptionEdit = $receptionPost;
        $honoreesEdit = $honoreesPost;
        $brideInfoEdit = $brideInfoPost;
        $groomInfoEdit = $groomInfoPost;
        $tripEdit = $tripPost;
        $tripDateEdit = $tripDatePost;
        $residenceEdit = $residencePost;
        $contactInfoEdit = $contactInfoPost;
        $subject = 'Contact form: Wedding announcement' . "\r\n";
        if ($ringPost === 'doubleRing') {
            $doubleRingEdit = 1;
        } elseif ($ringPost === 'singleRing') {
            $singleRingEdit = 1;
        } elseif ($ringPost === 'noRing') {
            $noRingEdit = 1;
        }
        if ($honorPost === 'matron') {
            $matronEdit = 1;
        } elseif ($honorPost === 'maid') {
            $maidEdit = 1;
        } elseif ($honorPost === 'none') {
            $noneEdit = 1;
        }
        if ($picturePost === 'yes') {
            $yesEdit = 1;
        } elseif ($picturePost === 'no') {
            $noEdit = 1;
        }
    }
    //
    //
    //
    $html = '        <h3>Wedding announcement</h3>

        <p><input type="hidden" name="form" value="' . $formPost . '" />

        <p><label for="title">Email (optional)<br />
        <input type="email" id="email" name="email" class="wide"' . returnIfValue($emailEdit) . ' /></label></p>

        <p>If a photo is to go with the announcement, then email the photo to: office@illinois-valley-news.com.</p>

        <p><label for="maidenName">Maiden name of the bride and her residence<br />
        <input type="text" id="maidenName" name="maidenName" class="wide"' . returnIfValue($maidenNameEdit) . ' required /></label></p>

        <p><label for="brideParents">Bride\'s parents and residence<br />
        <input type="text" id="brideParents" name="brideParents" class="wide"' . returnIfValue($brideParentsEdit) . ' required /></label></p>

        <p><label for="bridegroomName">Bridegroom\'s name and residence<br />
        <input type="text" id="bridegroomName" name="bridegroomName" class="wide"' . returnIfValue($bridegroomNameEdit) . ' required /></label></p>

        <p><label for="bridegroomParents">Bridegroom\'s parents and residence<br />
        <input type="text" id="bridegroomParents" name="bridegroomParents" class="wide"' . returnIfValue($bridegroomParentsEdit) . ' required /></label></p>

        <p><label for="weddingPlace">Place of wedding<br />
        <input type="text" id="weddingPlace" name="weddingPlace" class="wide"' . returnIfValue($weddingPlaceEdit) . ' required /></label></p>

        <p><label for="dateHour">Date and hour<br />
        <input type="text" id="dateHour" name="dateHour" class="wide"' . returnIfValue($dateHourEdit) . ' required /></label></p>

        <p><label for="ceremonyPerformer">Name of person performing ceremony<br />
        <input type="text" id="ceremonyPerformer" name="ceremonyPerformer" class="wide"' . returnIfValue($ceremonyPerformerEdit) . ' required /></label></p>

        <p><label for="doubleRing"><input name="ring" id="doubleRing" type="radio" value="doubleRing"' . returnIfYes($doubleRingEdit) . ' required /> Double-ring</label><br />
        <label for="singleRing"><input name="ring" id="singleRing" type="radio" value="singleRing"' . returnIfYes($singleRingEdit) . ' /> Single-ring</label><br />
        <label for="noRing"><input name="ring" id="noRing" type="radio" value="noRing"' . returnIfYes($noRingEdit) . ' /> No ring</label></p>

        <p><label for="matronName">Name of the matron or the maid of honor<br />
        <input type="text" id="matronName" name="matronName" class="wide"' . returnIfValue($matronNameEdit) . ' required /></label></p>

        <p><label for="matron"><input name="honor" id="matron" type="radio" value="matron"' . returnIfYes($matronEdit) . ' required /> Matron</label><br />
        <label for="maid"><input name="honor" id="maid" type="radio" value="maid"' . returnIfYes($maidEdit) . ' /> Maid of honor</label><br />
        <label for="none"><input name="honor" id="none" type="radio" value="none"' . returnIfYes($noneEdit) . ' /> None</label></p>

        <p><label for="bridesmaidName">Names of the bridesmaids<br />
        <input type="text" id="bridesmaidName" name="bridesmaidName" class="wide"' . returnIfValue($bridesmaidNameEdit) . ' required /></label></p>

        <p><label for="bestMan">Name of the best man<br />
        <input type="text" id="bestMan" name="bestMan" class="wide"' . returnIfValue($bestManEdit) . ' required /></label></p>

        <p><label for="ushersGroomsmen">Names of the ushers/groomsmen<br />
        <input type="text" id="ushersGroomsmen" name="ushersGroomsmen" class="wide"' . returnIfValue($ushersGroomsmenEdit) . ' required /></label></p>

        <p><label for="otherParticipants">Names of other participants and roles (e.g. ringbearer, flower girl, candlelighter)<br />
        <input type="text" id="otherParticipants" name="otherParticipants" class="wide"' . returnIfValue($otherParticipantsEdit) . ' required /></label></p>

        <p><label for="musicians">Musician(s) (specify)<br />
        <input type="text" id="musicians" name="musicians" class="wide"' . returnIfValue($musiciansEdit) . ' required /></label></p>

        <p><label for="reception">Time and place of reception<br />
        <input type="text" id="reception" name="reception" class="wide"' . returnIfValue($receptionEdit) . ' required /></label></p>

        <p><label for="honorees">Honorees serving or assisting<br />
        <input type="text" id="honorees" name="honorees" class="wide"' . returnIfValue($honoreesEdit) . ' required /></label></p>

        <p><label for="brideInfo">Bride\'s schools, sororities, employment<br />
        <input type="text" id="brideInfo" name="brideInfo" class="wide"' . returnIfValue($brideInfoEdit) . ' required /></label></p>

        <p><label for="groomInfo">Groom\'s schools, clubs, employment<br />
        <input type="text" id="groomInfo" name="groomInfo" class="wide"' . returnIfValue($groomInfoEdit) . ' required /></label></p>

        <p><label for="trip">Destination of wedding trip<br />
        <input type="text" id="trip" name="trip" class="wide"' . returnIfValue($tripEdit) . ' required /></label></p>

        <p><label for="tripDate">Date of trip<br />
        <input type="text" id="tripDate" name="tripDate" class="wide"' . returnIfValue($tripDateEdit) . ' required /></label></p>

        <p><label for="residence">Where couple will live<br />
        <input type="text" id="residence" name="residence" class="wide"' . returnIfValue($residenceEdit) . ' required /></label></p>

        <p><label for="yes"><input name="picture" id="yes" type="radio" value="yes"' . returnIfYes($yesEdit) . ' required /> I will email a photo to include with the announcement</label><br />
        <label for="no"><input name="picture" id="no" type="radio" value="no"' . returnIfYes($noEdit) . ' /> None</label></p>

        <p><label for="contactInfo">Name and phone (between 8 and 5) of contact person for the story<br />
        <input type="text" id="contactInfo" name="contactInfo" class="wide"' . returnIfValue($contactInfoEdit) . ' required /></label></p>

        <p><input name="submit" type="submit" class="button" value="Send announcement" /></p>' . "\n";
}
//
// Calendar event
//
if (isset($formPost) and $formPost === 'calendar') {
    $emailEdit = null;
    $emailPost = inlinePost('email');
    $nameEdit = null;
    $namePost = inlinePost('name');
    $addressEdit = null;
    $addressPost = inlinePost('address');
    $telephoneEdit = null;
    $telephonePost = inlinePost('telephone');
    $titleEdit = null;
    $titlePost = inlinePost('title');
    $contentEdit = null;
    $contentPost = securePost('content');
    $subject = 'Contact form: Calendar event' . "\r\n";
    //
    // Letter to the editor error messages
    //
    if (isset($_POST['submit']) and empty($namePost)) {
        $message.= 'Name is required.<br />';
    }
    if (isset($_POST['submit']) and empty($addressPost)) {
        $message.= 'Address is required.<br />';
    }
    if (isset($_POST['submit']) and empty($telephonePost)) {
        $message.= 'Telephone is required.<br />';
    }
    if (isset($_POST['submit']) and empty($titlePost)) {
        $message.= 'Title is required.<br />';
    }
    if (isset($_POST['submit']) and empty($contentPost)) {
        $message.= 'Calendar event is required.';
    }
    //
    // Send the information or reset the form
    //
    if (isset($_POST['submit']) and empty($message)) {
        $body = null;
        if (!empty($emailPost)) {
            $body.= 'Email' . "\n";
            $body.= $emailPost . "\n\n";
        }
        $body.= 'Name' . "\n";
        $body.= $namePost . "\n\n";
        $body.= 'Address' . "\n";
        $body.= $addressPost . "\n\n";
        $body.= 'Telephone' . "\n";
        $body.= $telephonePost . "\n\n";
        $body.= 'Title' . "\n";
        $body.= $titlePost . "\n\n";
        $body.= 'Calendar event' . "\n";
        $body.= $contentPost . "\n\n";
        $body.= $location;
        mail($emailTo, $subject, $body, $headers);
        $message = 'The information was sent.';
    } else {
        $emailEdit = $emailPost;
        $nameEdit = $namePost;
        $addressEdit = $addressPost;
        $telephoneEdit = $telephonePost;
        $titleEdit = $titlePost;
        $contentEdit = $contentPost;
    }
    //
    // Letter to the editor HTML
    //
    $html = '        <h3>Calendar event</h3>

        <p><input type="hidden" name="form" value="' . $formPost . '" />

        <p><label for="title">Email (optional)<br />
        <input type="email" id="email" name="email" class="wide"' . returnIfValue($emailEdit) . ' /></label></p>

        <p><label for="name">Name<br />
        <input type="text" id="name" name="name" class="wide"' . returnIfValue($nameEdit) . ' required /></label></p>

        <p><label for="address">Address<br />
        <input type="text" id="address" name="address" class="wide"' . returnIfValue($addressEdit) . ' required /></label></p>

        <p><label for="telephone">Telephone<br />
        <input type="tel" id="telephone" name="telephone" class="wide"' . returnIfValue($telephoneEdit) . ' required /></label></p>

        <p><label for="title">Title<br />
        <input type="text" id="title" name="title" class="wide"' . returnIfValue($titleEdit) . ' required /></label></p>

        <p><label for="content">Calendar event, time, date, and for recurring events, the schedule</label><br />
        <textarea id="content" name="content" class="wide" required>' . returnIfText($contentEdit) . '</textarea></p>

        <p><input name="submit" type="submit" class="button" value="Send calendar event" /></p>' . "\n";
}
//
// Letter to the editor
//
if (isset($formPost) and $formPost === 'letter') {
    $emailEdit = null;
    $emailPost = inlinePost('email');
    $nameEdit = null;
    $namePost = inlinePost('name');
    $addressEdit = null;
    $addressPost = inlinePost('address');
    $telephoneEdit = null;
    $telephonePost = inlinePost('telephone');
    $titleEdit = null;
    $titlePost = inlinePost('title');
    $contentEdit = null;
    $contentPost = securePost('content');
    $subject = 'Contact form: Letter to the editor' . "\r\n";
    //
    // Letter to the editor error messages
    //
    if (isset($_POST['submit']) and empty($namePost)) {
        $message.= 'Name is required.<br />';
    }
    if (isset($_POST['submit']) and empty($addressPost)) {
        $message.= 'Address is required.<br />';
    }
    if (isset($_POST['submit']) and empty($telephonePost)) {
        $message.= 'Telephone is required.<br />';
    }
    if (isset($_POST['submit']) and empty($titlePost)) {
        $message.= 'Title is required.<br />';
    }
    if (isset($_POST['submit']) and empty($contentPost)) {
        $message.= 'Content is required.';
    }
    //
    // Send the information or reset the form
    //
    if (isset($_POST['submit']) and empty($message)) {
        $body = null;
        if (!empty($emailPost)) {
            $body.= 'Email' . "\n";
            $body.= $emailPost . "\n\n";
        }
        $body.= 'Name' . "\n";
        $body.= $namePost . "\n\n";
        $body.= 'Address' . "\n";
        $body.= $addressPost . "\n\n";
        $body.= 'Telephone' . "\n";
        $body.= $telephonePost . "\n\n";
        $body.= 'Title' . "\n";
        $body.= $titlePost . "\n\n";
        $body.= 'Content for publication' . "\n";
        $body.= $contentPost . "\n\n";
        $body.= $location;
        mail($emailTo, $subject, $body, $headers);
        $message = 'The information was sent.';
    } else {
        $emailEdit = $emailPost;
        $nameEdit = $namePost;
        $addressEdit = $addressPost;
        $telephoneEdit = $telephonePost;
        $titleEdit = $titlePost;
        $contentEdit = $contentPost;
    }
    //
    // Letter to the editor HTML
    //
    $html = '        <h3>Letter to the editor</h3>

        <p><input type="hidden" name="form" value="' . $formPost . '" />

        <p><label for="title">Email (optional)<br />
        <input type="email" id="email" name="email" class="wide"' . returnIfValue($emailEdit) . ' /></label></p>

        <p><label for="name">Name<br />
        <input type="text" id="name" name="name" class="wide"' . returnIfValue($nameEdit) . ' required /></label></p>

        <p><label for="address">Address<br />
        <input type="text" id="address" name="address" class="wide"' . returnIfValue($addressEdit) . ' required /></label></p>

        <p><label for="telephone">Telephone<br />
        <input type="tel" id="telephone" name="telephone" class="wide"' . returnIfValue($telephoneEdit) . ' required /></label></p>

        <p><label for="title">Title<br />
        <input type="text" id="title" name="title" class="wide"' . returnIfValue($titleEdit) . ' required /></label></p>

        <p><label for="content">Content for publication</label><br />
        <textarea id="content" name="content" class="wide" required>' . returnIfText($contentEdit) . '</textarea></p>

        <p><input name="submit" type="submit" class="button" value="Send letter" /></p>' . "\n";
}
//
// Letter to the editor
//
if (isset($formPost) and $formPost === 'other') {
    $emailEdit = null;
    $emailPost = inlinePost('email');
    $nameEdit = null;
    $namePost = inlinePost('name');
    $addressEdit = null;
    $addressPost = inlinePost('address');
    $telephoneEdit = null;
    $telephonePost = inlinePost('telephone');
    $titleEdit = null;
    $titlePost = inlinePost('title');
    $contentEdit = null;
    $contentPost = securePost('content');
    $subject = 'Contact form: Other' . "\r\n";
    //
    // Other error messages
    //
    if (isset($_POST['submit']) and empty($namePost)) {
        $message.= 'Name is required.<br />';
    }
    if (isset($_POST['submit']) and empty($addressPost)) {
        $message.= 'Address is required.<br />';
    }
    if (isset($_POST['submit']) and empty($telephonePost)) {
        $message.= 'Telephone is required.<br />';
    }
    if (isset($_POST['submit']) and empty($titlePost)) {
        $message.= 'Title is required.<br />';
    }
    if (isset($_POST['submit']) and empty($contentPost)) {
        $message.= 'Message is required.';
    }
    //
    // Send the information or reset the form
    //
    if (isset($_POST['submit']) and empty($message)) {
        $body = null;
        if (!empty($emailPost)) {
            $body.= 'Email' . "\n";
            $body.= $emailPost . "\n\n";
        }
        $body.= 'Name' . "\n";
        $body.= $namePost . "\n\n";
        $body.= 'Address' . "\n";
        $body.= $addressPost . "\n\n";
        $body.= 'Telephone' . "\n";
        $body.= $telephonePost . "\n\n";
        $body.= 'Title' . "\n";
        $body.= $titlePost . "\n\n";
        $body.= 'Message' . "\n";
        $body.= $contentPost . "\n\n";
        $body.= $location;
        mail($emailTo, $subject, $body, $headers);
        $message = 'The information was sent.';
    } else {
        $emailEdit = $emailPost;
        $nameEdit = $namePost;
        $addressEdit = $addressPost;
        $telephoneEdit = $telephonePost;
        $titleEdit = $titlePost;
        $contentEdit = $contentPost;
    }
    //
    // Other HTML
    //
    $html = '        <h3>Other</h3>

        <p><input type="hidden" name="form" value="' . $formPost . '" />

        <p><label for="title">Email (optional)<br />
        <input type="email" id="email" name="email" class="wide"' . returnIfValue($emailEdit) . ' /></label></p>

        <p><label for="name">Name<br />
        <input type="text" id="name" name="name" class="wide"' . returnIfValue($nameEdit) . ' required /></label></p>

        <p><label for="address">Address<br />
        <input type="text" id="address" name="address" class="wide"' . returnIfValue($addressEdit) . ' required /></label></p>

        <p><label for="telephone">Telephone<br />
        <input type="tel" id="telephone" name="telephone" class="wide"' . returnIfValue($telephoneEdit) . ' required /></label></p>

        <p><label for="title">Title<br />
        <input type="text" id="title" name="title" class="wide"' . returnIfValue($titleEdit) . ' required /></label></p>

        <p><label for="content">Message</label><br />
        <textarea id="content" name="content" class="wide" required>' . returnIfText($contentEdit) . '</textarea></p>

        <p><input name="submit" type="submit" class="button" value="Send message" /></p>' . "\n";
}
//
// HTML
//
echoIfMessage($message);
echo '      <h1>Contact us</h1>' . "\n\n";
echo $information;
echo '      <form method="post" action="' . $uri . '?m=contact-us">' . "\n";
echo $html;
echo '      </form>' . "\n";
?>