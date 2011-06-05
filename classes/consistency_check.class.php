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
<?php


//include_once "constants.php";
//include_once "tools.php";
include_once "issue.class.php";
include_once "user.class.php";
include_once "project.class.php";

class ConsistencyError {

	var $bugId;
	var $userId;
	var $teamId;
	var $desc;
   var $timestamp;
   var $status;

   var $severity; // unused

	public function ConsistencyError($bugId, $userId, $status, $timestamp, $desc) {
		$this->bugId     = $bugId;
      $this->userId    = $userId;
      $this->status = $status;
      $this->timestamp = $timestamp;
      $this->desc      = $desc;
	}
}


class ConsistencyCheck {

	var $projectList;

   // ----------------------------------------------
   public function ConsistencyCheck($projectList = NULL) {
   	if (NULL != $projectList) {
   		$this->projectList = $projectList;
   	} else {
   		 $projectList = array();
   	}
   }

   // ----------------------------------------------
   /**
    * perform all consistency checks
    */
   public function check() {

      $cerrList1 = $this->checkAnalyzed();
      $cerrList2 = $this->checkResolved();
      $cerrList3 = $this->checkDeliveryDate();
      $cerrList4 = $this->checkBadRAE();
      $cerrList5 = $this->checkETA();
      $cerrList = array_merge($cerrList1, $cerrList2, $cerrList3, $cerrList4, $cerrList5);
      return $cerrList;
   }


   // ----------------------------------------------
   /**
    * if $deliveryIssueCustomField is specified, then $deliveryDateCustomField should also be specified.
    */
   public function checkDeliveryDate() {

      $deliveryIdCustomField     = Config::getInstance()->getValue(Config::id_customField_deliveryId);
      $deliveryDateCustomField   = Config::getInstance()->getValue(Config::id_customField_deliveryDate);
      $resolved_status_threshold = ConfigMantis::getInstance()->getValue(ConfigMantis::id_bugResolvedStatusThreshold);

      $cerrList = array();

      // select all issues which current status is 'analyzed'
      $query = "SELECT id AS bug_id, status, handler_id, last_updated ".
        "FROM `mantis_bug_table` ".
        "WHERE status >= $resolved_status_threshold ";

      if (0 != count($this->projectList)) {
      	$formatedProjects = valuedListToSQLFormatedString($this->projectList);
      	$query .= "AND project_id IN ($formatedProjects) ";
      }

       $query .="ORDER BY last_updated DESC, bug_id DESC";

      $result    = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
         $issue = IssueCache::getInstance()->getIssue($row->bug_id);

         if ((NULL != $issue->deliveryId) &&
         	 (NULL == $issue->deliveryDate)) {
               $cerr = new ConsistencyError($row->bug_id,
                                              $row->handler_id,
                                              $row->status,
                                              $row->last_updated,
                                              T_("Delivery date not specified: If a delivery sheet is specified, then a Delivery Date is requested."));
               $cerr->severity = T_("Error");
               $cerrList[] = $cerr;
         	 }
      }
      return $cerrList;

   }

   // ----------------------------------------------
   // fiches analyzed dont BI non renseignes
   // fiches analyzed dont RAE non renseignes
   public function checkAnalyzed() {

   	global $status_analyzed;
      global $status_accepted;
      global $status_openned;
      global $status_deferred;
      global $status_resolved;
      global $status_delivered;
      global $status_closed;

   	  $FDJ_teamid = Config::getInstance()->getValue(Config::id_ClientTeamid);

      $cerrList = array();

      // select all issues which current status is 'analyzed'
      $query = "SELECT id AS bug_id, status, handler_id, last_updated ".
        "FROM `mantis_bug_table` ".
        "WHERE status in ($status_analyzed, $status_accepted, $status_openned, $status_deferred) ";

      if (0 != count($this->projectList)) {
         $formatedProjects = valuedListToSQLFormatedString($this->projectList);
         $query .= "AND project_id IN ($formatedProjects) ";
      }

      $query .="ORDER BY last_updated DESC, bug_id DESC";

      $result    = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
      	$issue = IssueCache::getInstance()->getIssue($row->bug_id);

         if (NULL == $issue->effortEstim) {
           $cerr = new ConsistencyError($row->bug_id,
                                              $row->handler_id,
                                              $row->status,
                                              $row->last_updated,
                                              T_("BI not specified: BI = Time(Analysis + Dev + Tests)"));
            $cerr->severity = T_("Error");
            $cerrList[] = $cerr;
         }
      	if (NULL == $issue->remaining) {
           $cerr = new ConsistencyError($row->bug_id,
                                              $row->handler_id,
                                              $row->status,
                                              $row->last_updated,
                                              T_("Remaining not specified: Remaining = Time(BI - Analysis)"));
            $cerr->severity = T_("Error");
            $cerrList[] = $cerr;
      	}
         if ($status_analyzed == $row->status) {
             $user = UserCache::getInstance()->getUser($row->handler_id);
             if (! $user->isTeamMember($FDJ_teamid)) {
              $cerr = new ConsistencyError($row->bug_id,
                                                 $row->handler_id,
                                                 $row->status,
                                                 $row->last_updated,
                                                 T_("Once analysed, a Task must be assigned to 'FDJ' for validation"));
               $cerr->severity = T_("Error");
               $cerrList[] = $cerr;
             }

         }
      }


      // check if fields correctly set

      return $cerrList;
   }

   // ----------------------------------------------
   /**
    * fiches resolved dont le RAE != 0
    */
   public function checkResolved() {

   	global $statusNames;

      $resolved_status_threshold = ConfigMantis::getInstance()->getValue(ConfigMantis::id_bugResolvedStatusThreshold);

      $cerrList = array();

      // select all issues which current status is 'analyzed'
      $query = "SELECT id AS bug_id, status, handler_id, last_updated ".
        "FROM `mantis_bug_table` ".
        "WHERE status >= $resolved_status_threshold ";

      if (0 != count($this->projectList)) {
         $formatedProjects = valuedListToSQLFormatedString($this->projectList);
         $query .= "AND project_id IN ($formatedProjects) ";
      }

      $query .="ORDER BY last_updated DESC, bug_id DESC";

      $result    = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
         // check if fields correctly set
      	$issue = IssueCache::getInstance()->getIssue($row->bug_id);

         if (0 != $issue->remaining) {
           $cerr = new ConsistencyError($row->bug_id,
                                              $row->handler_id,
                                              $row->status,
                                              $row->last_updated,
                                              T_("Remaining should be 0 (not $issue->remaining)."));
            $cerr->severity = T_("Error");
            $cerrList[] = $cerr;
         }
      }



      return $cerrList;
  	}



   // ----------------------------------------------
   /**
    * fiches NOT resolved with RAE == 0
    */
  	public function checkBadRAE() {
      global $status_new;
      global $status_ack;
  		global $statusNames;

      $resolved_status_threshold = ConfigMantis::getInstance()->getValue(ConfigMantis::id_bugResolvedStatusThreshold);

      $min_remaining = 0;


      $cerrList = array();

      // select all issues which current status is 'analyzed'
      $query = "SELECT id AS bug_id, status, handler_id, last_updated ".
        "FROM `mantis_bug_table` ".
        "WHERE status NOT IN ($status_new, $status_ack) ".
        "AND status < $resolved_status_threshold ";

      if (0 != count($this->projectList)) {
         $formatedProjects = valuedListToSQLFormatedString($this->projectList);
         $query .= "AND project_id IN ($formatedProjects) ";
      }

      $query .="ORDER BY last_updated DESC, bug_id DESC";

      $result    = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
         // check if fields correctly set
         $issue = IssueCache::getInstance()->getIssue($row->bug_id);

         if ($issue->remaining <= $min_remaining) {
           $cerr = new ConsistencyError($row->bug_id,
                                              $row->handler_id,
                                              $row->status,
                                              $row->last_updated,
                                              T_("Remaining == 0: Remaining may not be up to date."));
            $cerr->severity = T_("Warning");
            $cerrList[] = $cerr;
         }
      }



      return $cerrList;

   }

   // ----------------------------------------------
   /**
    * an ETA should be defined when creating an Issue
    */
   public function checkETA() {

   	$cerrList = array();

   	// select all issues
      $query = "SELECT id AS bug_id, status, handler_id, last_updated ".
        "FROM `mantis_bug_table` ";




      if (0 != count($this->projectList)) {

      	// --- except SideTasksProjects (they don't have an ETA field)
      	$prjListNoSideTasks = $this->projectList; // copy
         foreach ($prjListNoSideTasks as $id => $name) {
         	$p = ProjectCache::getInstance()->getProject($id);
         	if (true == $p->isSideTasksProject()) {
               unset($prjListNoSideTasks[$id]);
         	}
         }



         $formatedProjects = valuedListToSQLFormatedString($prjListNoSideTasks);
         $query .= "WHERE project_id IN ($formatedProjects) ";
      } else {
      	// TODO except SideTasksProjects
      }

      $query .="ORDER BY last_updated DESC, bug_id DESC";

      $result    = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
         // check if fields correctly set
         $issue = IssueCache::getInstance()->getIssue($row->bug_id);

         if ( (NULL == $issue->prelEffortEstimName) || ('none' == $issue->prelEffortEstimName)) {

           $cerr = new ConsistencyError($row->bug_id,
                                              $row->handler_id,
                                              $row->status,
                                              $row->last_updated,
                                              T_("ETA not set."));
            $cerr->severity = T_("Error");
            $cerrList[] = $cerr;
         }
      }



      return $cerrList;

   }

}


?>