<?php
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

/**
 * @author LoB
 * @date 01 Feb 2011
 */
class Job extends Model implements Comparable {

  const type_commonJob = 0;     // jobs common to all projects are type 0
  const type_assignedJob = 1;     // jobs specific to one or more projects are type 1

  public static $typeNames = array(
     self::type_assignedJob => "Assigned",
     self::type_commonJob => "Common",
  );

   private $id;
   private $name;
   private $type;
   private $color;

   /**
    * @param int $id
    * @param string $name
    * @param $type
    * @param $color
    */
   public function __construct($id, $name, $type, $color) {
      $this->id = $id;
      $this->name = $name;
      $this->type = $type;
      $this->color = $color;
   }

   /**
    * Sort by asc
    * @static
    * @param Job $jobA
    * @param Job $jobB
    * @return int 1 if $jobB is higher, -1 if $jobB is lower, 0 if equals
    */
   public static function compare(Comparable $jobA, Comparable $jobB) {
      return strcmp($jobA->name, $jobB->name);
   }

   /**
    * @return int
    */
   public function getId() {
      return $this->id;
   }

   /**
    * @return string
    */
   public function getName() {
      return $this->name;
   }

   /**
    * @return string
    */
   public function getType() {
      return $this->type;
   }

   /**
    * @return string
    */
   public function getColor() {
      return $this->color;
   }

}

class Jobs {

   const JOB_NA = 1; // REM: N/A     job_id = 1, created by SQL file at install
   const JOB_SUPPORT = 2; // REM: Support job_id = 2, created by SQL file at install

   /**
    * @var Job[] The job list
    */
   private $jobList;

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

   public function __construct() {
      $this->jobList = array();

      $query = "SELECT * FROM `codev_job_table`";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $j = new Job($row->id, $row->name, $row->type, $row->color);
         $this->jobList[$row->id] = $j;
      }
   }

   /**
    * @return Job[]
    */
   public function getJobs() {
      return array_values($this->jobList);
   }

   /**
    * @param int $id
    * @return string
    */
   public function getJobName($id) {

      if (!array_key_exists($id, $this->jobList)) {
         $e = new Exception("getJobName($id): job id not found !");
         self::$logger->error("EXCEPTION: ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         //throw $e;
         return "error";
      }
      return $this->jobList[$id]->getName();
   }

   /**
    * @param int $id
    * @return string
    */
   public function getJobColor($id) {
      if (!array_key_exists($id, $this->jobList)) {
         $e = new Exception("getJobColor($id): job id not found !");
         self::$logger->error("EXCEPTION: ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         //throw $e;
         return "000000";
      }
      return $this->jobList[$id]->getColor();
   }

   /**
    * @param int $id
    * @return bool
    */
   public function isSupport($id) {
      return ($id == Jobs::JOB_SUPPORT);
   }

   /**
    * @param string $job_name
    * @param string $job_type
    * @param string $job_color
    * @return int $job_id
    */
   public static function create($job_name, $job_type, $job_color) {
      $query = "INSERT INTO `codev_job_table`  (`name`, `type`, `color`) VALUES ('$job_name','$job_type','$job_color');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      return SqlWrapper::getInstance()->sql_insert_id();
   }

   /**
    * @static
    * @param int $project_id
    * @param int $job_id
    */
   public static function addJobProjectAssociation($project_id, $job_id) {
      $query = "INSERT INTO `codev_project_job_table`  (`project_id`, `job_id`) VALUES ('$project_id','$job_id');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }

}
Jobs::staticInit();
?>
