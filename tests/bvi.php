<?php

require('../include/session.inc.php');

/*
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
 */


require('../path.inc.php');

class BVIController extends Controller {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   protected function display() {
      if (Tools::isConnectedUser()) {
         $user = UserCache::getInstance()->getUser($_SESSION['userid']);

         $isel = new IssueSelection('testSel');
         $isel->addIssue(565);
         $isel->addIssue(567);

         $indic = new BacklogVariationIndicator();
         $indic->execute($isel);

         $data = $indic->getSmartyObject();
         foreach ($data as $smartyKey => $smartyVariable) {
            $this->smartyHelper->assign($smartyKey, $smartyVariable);
         }
         

      }
   }

}

// ========== MAIN ===========
BVIController::staticInit();
$controller = new BVIController('TEST BacklogVariationIndicator', 'Tests');
$controller->execute();
?>