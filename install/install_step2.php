<?php if (!isset($_SESSION)) { session_start(); } ?>
<?php /*
    This file is part of CoDev-Timetracking.

    CoDev-Timetracking is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    CoDev-Timetracking is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with CoDev-Timetracking.  If not, see <http://www.gnu.org/licenses/>.
*/ ?>

<?php include_once '../path.inc.php'; ?>
<?php include_once 'i18n.inc.php'; ?>

<?php
   $_POST[page_name] = T_("Install - Step 2");
   include 'install_header.inc.php';

   include_once "mysql_connect.inc.php";
   include_once "internal_config.inc.php";
   include_once "constants.php";

   include 'install_menu.inc.php';

?>

<script language="JavaScript">

function proceedStep2() {

     document.forms["form2"].action.value="proceedStep2";
     document.forms["form2"].submit();
}

</script>

<div id="content">


<?php

include_once 'install.class.php';

function displayStepInfo() {
   echo "<h2>".T_("Prerequisites")."</h2>\n";
   echo "Step 1 finished";
   echo "<br/>";
   echo "<h2>".T_("Actions")."</h2>\n";
   echo "<ul>\n";
   echo "<li>set Mantis related information</li>";
  echo "</ul>\n";
   echo "";
}


function displayForm($originPage,
                     $g_status_enum_string, $s_priority_enum_string, $s_resolution_enum_string) {

   echo "<form id='form2' name='form2' method='post' action='$originPage' >\n";
   echo "<hr align='left' width='20%'/>\n";
   echo "<h2>".T_("Confirm Mantis customizations")."</h2>\n";

   echo "<table class='invisible'>\n";

   echo "  <tr>\n";
   echo "    <td width='120'>".T_("Priority")."</td>\n";
   echo "    <td><input size='150' type='text' style='font-family: sans-serif' name='s_priority_enum_string'  id='s_priority_enum_string' value='$s_priority_enum_string'></td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td width='120'>".T_("Resolve status")."</td>\n";
   echo "    <td><input size='150' type='text' style='font-family: sans-serif' name='s_resolution_enum_string'  id='s_resolution_enum_string' value='$s_resolution_enum_string'></td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td width='120'>".T_("Status")."</td>\n";
   echo "    <td><input size='150' type='text' style='font-family: sans-serif' name='g_status_enum_string'  id='g_status_enum_string' value='$g_status_enum_string'></td>\n";
   echo "  </tr>\n";
   echo "</table>\n";

   // ---
   echo "  <br/>\n";
   echo "  <br/>\n";

   echo "<div  style='text-align: center;'>\n";
   echo "<input type=button style='font-size:150%' value='".T_("Proceed Step 2")."' onClick='javascript: proceedStep2()'>\n";
   echo "</div>\n";

   echo "<input type=hidden name=action      value=noAction>\n";

   echo "</form>";
}

// ================ MAIN =================


$originPage = "install_step2.php";

// Values copied from:  mantis/lang/strings_english.txt
// Values copied from: mantis/config_inc.php

// FDJ defaults
//$default_priority   = '10:aucune,20:basse,30:normale,40:elevee,50:urgente,60:immediate';
//$default_resolution = '10:ouvert,20:resolu,30:rouvert,40:impossible a reproduire,50:impossible a corriger,60:doublon,70:pas un bogue,80:suspendu,90:ne sera pas resolu';
//$default_status     = '10:new,20:feedback,30:acknowledged,40:analyzed,45:accepted,50:openned,55:deferred,80:resolved,85:delivered,90:closed';
//$default_eta        = "10:none,20:< 1 day,30:2-3 days,40:<1 week,50:< 15 days,60:> 15 days";
//$default_eta_balance = "10:1,20:1,30:3,40:5,50:10,60:15";

// Mantis defaults
$default_priority    = "10:none,20:low,30:normal,40:high,50:urgent,60:immediate";
$default_resolution  = "10:open,20:fixed,30:reopened,40:unable to reproduce,50:not fixable,60:duplicate,70:no change required,80:suspended,90:won t fix";
$default_status      = "10:new,20:feedback,30:acknowledged,40:confirmed,50:assigned,80:resolved,90:closed";

$s_priority_enum_string = isset($_POST[s_priority_enum_string]) ? $_POST[s_priority_enum_string] : $default_priority;
$s_resolution_enum_string = isset($_POST[s_resolution_enum_string]) ? $_POST[s_resolution_enum_string] : $default_resolution;
$g_status_enum_string =  isset($_POST[g_status_enum_string]) ? $_POST[g_status_enum_string] : $default_status;


$action      = $_POST[action];


displayStepInfo();

displayForm($originPage,
            $g_status_enum_string, $s_priority_enum_string, $s_resolution_enum_string);


if ("proceedStep2" == $action) {

    echo "DEBUG add statusNames<br/>";
    $desc = T_("status Names as defined in Mantis (g_status_enum_string)");
    Config::getInstance()->setValue(Config::id_statusNames, $g_status_enum_string, Config::configType_keyValue , $desc);

    echo "DEBUG add priorityNames<br/>";
    $desc = T_("priority Names as defined in Mantis (s_priority_enum_string)");
    Config::getInstance()->setValue(Config::id_priorityNames, $s_priority_enum_string, Config::configType_keyValue , $desc);

    echo "DEBUG add resolutionNames<br/>";
    $desc = T_("resolution Names as defined in Mantis (s_resolution_enum_string)");
    Config::getInstance()->setValue(Config::id_resolutionNames, $s_resolution_enum_string, Config::configType_keyValue , $desc);

    // ------
    echo "DEBUG add ETA<br/>";
    $desc = T_("ETA as defined in Mantis (g_eta_enum_string)");
    Config::getInstance()->setValue(Config::id_ETA_names, $g_eta_enum_string, Config::configType_keyValue , $desc);

    echo "DEBUG add ETA balance<br/>";
    $desc = T_("ETA balance");
    Config::getInstance()->setValue(Config::id_ETA_balance, $eta_balance_string, Config::configType_keyValue , $desc);


}

?>

