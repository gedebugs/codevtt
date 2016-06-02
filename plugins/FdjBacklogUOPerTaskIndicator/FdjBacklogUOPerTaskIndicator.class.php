<?php
/*
   This file is part of CodevTT

   CodevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CodevTT is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CodevTT.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Description of FdjBacklogUOPerTaskIndicator
 *
 * For each Task, return the sum of the backlog UO of its assigned tasks.
 * 
 * @author lob
 */
class FdjBacklogUOPerTaskIndicator extends IndicatorPluginAbstract {

   /**
    * @var Logger The logger
    */
   private static $logger;
   private static $domains;
   private static $categories;

   private $inputIssueSel;

   // config options from Dashboard

   // internal
   private $issue;
   private $timetracks;
   protected $execData;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_TASK,
         self::DOMAIN_COMMAND,
         self::DOMAIN_COMMAND_SET
      );
      self::$categories = array (
         self::CATEGORY_ACTIVITY,
      );
   }

   public static function getName() {
      return '== FDJ == UO restant par tache';
   }
   public static function getDesc($isShortDesc = true) {
      $desc = 'Retourne les UO restant pour la tache courante';
      if (!$isShortDesc) {
         $desc .= '<br><br>';
      }
      return $desc;
   }
   public static function getAuthor() {
      return 'CodevTT (GPL v3)';
   }
   public static function getVersion() {
      return '1.0.0';
   }
   public static function getDomains() {
      return self::$domains;
   }
   public static function getCategories() {
      return self::$categories;
   }
   public static function isDomain($domain) {
      return in_array($domain, self::$domains);
   }
   public static function isCategory($category) {
      return in_array($category, self::$categories);
   }
   public static function getCssFiles() {
      return array(
      );
   }
   public static function getJsFiles() {
      return array(
         'js_min/table2csv.min.js',
         'js_min/progress.min.js',
         'js_min/tooltip.min.js',
      );
   }


   /**
    *
    * @param \PluginDataProviderInterface $pluginDataProv
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProv) {
      
      $this->timetracks = array();

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION)) {
         $this->inputIssueSel = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      }

   }

   /**
    * settings are saved by the Dashboard
    * 
    * @param array $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {
      if (NULL != $pluginSettings) {
         // override default with user preferences
      }
   }


   /**
    *
    * returns an array of 
    * activity in (elapsed, sidetask, other, external, leave)
    *
    */
   public function execute() {
      
      $bugidList = array_keys($this->inputIssueSel->getIssueList());
      self::$logger->error($bugidList);
      foreach ($bugidList as $bugid) {
         $this->issue = IssueCache::getInstance()->getIssue($bugid);
         $this->timetracks = array_keys($this->issue->getTimetracks());

         $SumUOArray = UniteOeuvre::getSumUOPerTask($this->timetracks);
         $chargeInitUO = $this->issue->getChargeInit();
         $uoRemaining = $chargeInitUO - $SumUOArray;
         $uoRemainingColor = UniteOeuvre::getUORemainingColor($this->issue->getBugResolvedStatusThreshold(), $this->issue->getCurrentStatus(), $uoRemaining);
         $CommandList = $this->issue->getCommandList();
         $CommandeListString = implode(', ', $CommandList);
         
         $taskArray[$bugid] = array (
          'commandes' => $CommandeListString,
          'mantisURL' => Tools::mantisIssueURL($bugid, NULL, true),
          'issueURL' => Tools::issueInfoURL($bugid),
          'taskName' => $this->issue->getSummary(),
          'chargeInitUO' => str_replace('.', ',',round($chargeInitUO,2)),
          'elapsed' => str_replace('.', ',', round($SumUOArray,2)),
          'uoRemaining' =>  str_replace('.', ',', round($uoRemaining,2)),
          'uoRemainingColor' => $uoRemainingColor,
      );
      }
      
      

      $this->execData = array();
      $this->execData['taskArray'] = $taskArray;
      
      return $this->execData;
   }

   public function getSmartyVariables($isAjaxCall = false) {
      $smartyVariables = array(
         'fdjBacklogUOPerTaskIndicator_taskArray' => $this->execData['taskArray']
      );

      if (false == $isAjaxCall) {
         $smartyVariables['fdjBacklogUOPerTaskIndicator_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['fdjBacklogUOPerTaskIndicator_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }
}

// Initialize complex static variables
FdjBacklogUOPerTaskIndicator::staticInit();
