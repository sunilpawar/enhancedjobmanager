<?php
use CRM_Enhancedjobmanager_ExtensionUtil as E;

class CRM_Enhancedjobmanager_Page_JobStats extends CRM_Core_Page {

  public function run() {
    // Set page title
    CRM_Utils_System::setTitle(ts('Job Log Statistics'));

    // Add CSS and JS resources
    CRM_Core_Resources::singleton()
      ->addStyleFile('com.skvare.enhancedjobmanager', 'css/jobstats.css')
      ->addScriptFile('com.skvare.enhancedjobmanager', 'js/jobstats.js')
      ->addScriptUrl('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js');

    // Get initial data for the page
    $jobStats = $this->getJobStatistics();
    $jobList = $this->getJobList();
    $recentExecutions = $this->getRecentExecutions();

    // Assign variables to template
    $this->assign('jobStats', $jobStats);
    $this->assign('jobList', $jobList);
    $this->assign('recentExecutions', $recentExecutions);
    $this->assign('dateRanges', $this->getDateRangeOptions());

    parent::run();
  }

  /**
   * Get job statistics summary
   * @param array $filters
   * @return array
   */
  public function getJobStatistics($filters = []) {
    $whereClause = $this->buildWhereClause($filters);

    // Get total executions
    $totalExecutions = CRM_Core_DAO::singleValueQuery(
      "SELECT COUNT(*) FROM civicrm_job_log WHERE 1=1 $whereClause"
    );

    // Get success/error counts
    $successCount = CRM_Core_DAO::singleValueQuery(
      "SELECT COUNT(*) FROM civicrm_job_log
       WHERE (data NOT LIKE '%error%' AND data NOT LIKE '%failed%')
       AND 1=1 $whereClause"
    );

    // Get error count
    $errorCount = $totalExecutions - $successCount;

    // Get average duration (if run_time field exists and contains duration info)
    $avgDuration = CRM_Core_DAO::singleValueQuery(
      "SELECT AVG(
         CASE
           WHEN data LIKE '%duration:%' THEN
             CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, 'duration:', -1), ' ', 1) AS DECIMAL(10,2))
           ELSE 0
         END
       ) FROM civicrm_job_log WHERE 1=1 $whereClause"
    ) ?: 0;

    return [
      'total_executions' => $totalExecutions,
      'success_count' => $successCount,
      'error_count' => $errorCount,
      'success_rate' => $totalExecutions > 0 ? round(($successCount / $totalExecutions) * 100, 1) : 0,
      'error_rate' => $totalExecutions > 0 ? round(($errorCount / $totalExecutions) * 100, 1) : 0,
      'avg_duration' => round($avgDuration, 2)
    ];
  }

  /**
   * Get list of available jobs
   * @return array
   */
  public function getJobList() {
    $query = "
      SELECT DISTINCT
        j.id,
        j.name,
        j.api_entity,
        j.api_action,
        j.description,
        j.is_active,
        j.last_run,
        COUNT(jl.id) as execution_count,
        SUM(CASE WHEN jl.data NOT LIKE '%error%' AND jl.data NOT LIKE '%failed%' THEN 1 ELSE 0 END) as success_count,
        SUM(CASE WHEN jl.data LIKE '%error%' OR jl.data LIKE '%failed%' THEN 1 ELSE 0 END) as error_count
      FROM civicrm_job j
      LEFT JOIN civicrm_job_log jl ON j.id = jl.job_id
        AND jl.run_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
      GROUP BY j.id, j.name, j.api_entity, j.api_action, j.description, j.is_active, j.last_run
      ORDER BY j.name
    ";

    $dao = CRM_Core_DAO::executeQuery($query);
    $jobs = [];

    while ($dao->fetch()) {
      $errorRate = $dao->execution_count > 0 ? round(($dao->error_count / $dao->execution_count) * 100, 1) : 0;

      $jobs[] = [
        'id' => $dao->id,
        'name' => $dao->name,
        'api_call' => $dao->api_entity . '.' . $dao->api_action,
        'description' => $dao->description,
        'is_active' => $dao->is_active,
        'last_run' => $dao->last_run,
        'next_run' => date('Y-m-d H:i:s', strtotime($dao->last_run) + 86400), // Assuming daily jobs
        'execution_count' => $dao->execution_count,
        'success_count' => $dao->success_count,
        'error_count' => $dao->error_count,
        'error_rate' => $errorRate,
        'status' => $this->determineJobStatus($dao->error_count, $dao->execution_count, $dao->is_active)
      ];
    }

    return $jobs;
  }

  /**
   * Get recent job executions
   * @param int $limit
   * @return array
   */
  public function getRecentExecutions($limit = 50) {
    $query = "
      SELECT
        jl.id,
        jl.job_id,
        jl.name,
        jl.command,
        jl.description,
        jl.run_time,
        jl.data,
        j.name as job_name,
        j.api_entity,
        j.api_action
      FROM civicrm_job_log jl
      LEFT JOIN civicrm_job j ON jl.job_id = j.id
      ORDER BY jl.run_time DESC
      LIMIT $limit
    ";

    $dao = CRM_Core_DAO::executeQuery($query);
    $executions = [];

    while ($dao->fetch()) {
      $duration = $this->extractDurationFromData($dao->data);
      $status = $this->determineExecutionStatus($dao->data);

      $executions[] = [
        'id' => $dao->id,
        'job_id' => $dao->job_id,
        'job_name' => $dao->job_name ?: $dao->name,
        'api_call' => $dao->api_entity . '.' . $dao->api_action,
        'command' => $dao->command,
        'description' => $dao->description,
        'run_time' => $dao->run_time,
        'duration' => $duration,
        'status' => $status,
        'data' => $dao->data
      ];
    }

    return $executions;
  }

  /**
   * Get chart data for specified period and job
   * @param string $chartType
   * @param array $filters
   * @return array
   */
  public function getChartData($chartType, $filters = []) {
    $whereClause = $this->buildWhereClause($filters);
    $days = $filters['days'] ?? 30;
    $interval = $this->getIntervalByDays($days);

    switch ($chartType) {
      case 'executions':
        return $this->getExecutionsChartData($whereClause, $interval, $days);
      case 'duration':
        return $this->getDurationChartData($whereClause, $interval, $days);
      case 'errors':
        return $this->getErrorsChartData($whereClause, $interval, $days);
      case 'success':
        return $this->getSuccessRateChartData($whereClause, $interval, $days);
      default:
        return [];
    }
  }

  /**
   * Get executions chart data
   */
  private function getExecutionsChartData($whereClause, $interval, $days) {
    $query = "
      SELECT
        DATE($interval(run_time)) as period,
        COUNT(*) as executions
      FROM civicrm_job_log
      WHERE run_time >= DATE_SUB(NOW(), INTERVAL $days DAY) $whereClause
      GROUP BY DATE($interval(run_time))
      ORDER BY period
    ";

    return $this->executeChartQuery($query);
  }

  /**
   * Get duration chart data
   */
  private function getDurationChartData($whereClause, $interval, $days) {
    $query = "
      SELECT
        DATE($interval(run_time)) as period,
        AVG(
          CASE
            WHEN data LIKE '%duration:%' THEN
              CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, 'duration:', -1), ' ', 1) AS DECIMAL(10,2))
            ELSE 0
          END
        ) as avg_duration
      FROM civicrm_job_log
      WHERE run_time >= DATE_SUB(NOW(), INTERVAL $days DAY) $whereClause
      GROUP BY DATE($interval(run_time))
      ORDER BY period
    ";

    return $this->executeChartQuery($query);
  }

  /**
   * Get errors chart data
   */
  private function getErrorsChartData($whereClause, $interval, $days) {
    $query = "
      SELECT
        DATE($interval(run_time)) as period,
        SUM(CASE WHEN data LIKE '%error%' OR data LIKE '%failed%' THEN 1 ELSE 0 END) as errors
      FROM civicrm_job_log
      WHERE run_time >= DATE_SUB(NOW(), INTERVAL $days DAY) $whereClause
      GROUP BY DATE($interval(run_time))
      ORDER BY period
    ";

    return $this->executeChartQuery($query);
  }

  /**
   * Get success rate chart data
   */
  private function getSuccessRateChartData($whereClause, $interval, $days) {
    $query = "
      SELECT
        DATE($interval(run_time)) as period,
        ROUND(
          (SUM(CASE WHEN data NOT LIKE '%error%' AND data NOT LIKE '%failed%' THEN 1 ELSE 0 END) / COUNT(*)) * 100,
          1
        ) as success_rate
      FROM civicrm_job_log
      WHERE run_time >= DATE_SUB(NOW(), INTERVAL $days DAY) $whereClause
      GROUP BY DATE($interval(run_time))
      ORDER BY period
    ";

    return $this->executeChartQuery($query);
  }

  /**
   * Execute chart query and return formatted data
   */
  private function executeChartQuery($query) {
    $dao = CRM_Core_DAO::executeQuery($query);
    $data = [];

    while ($dao->fetch()) {
      $data[] = [
        'period' => $dao->period,
        'value' => isset($dao->executions) ? $dao->executions :
          (isset($dao->avg_duration) ? round($dao->avg_duration, 2) :
            (isset($dao->errors) ? $dao->errors :
              (isset($dao->success_rate) ? $dao->success_rate : 0)))
      ];
    }

    return $data;
  }

  /**
   * Build WHERE clause for filtering
   * @param array $filters
   * @return string
   */
  private function buildWhereClause($filters = []) {
    $conditions = [];

    if (!empty($filters['job_id'])) {
      $jobId = (int)$filters['job_id'];
      $conditions[] = "job_id = $jobId";
    }

    if (!empty($filters['start_date'])) {
      $startDate = CRM_Utils_Type::escape($filters['start_date'], 'String');
      $conditions[] = "run_time >= '$startDate'";
    }

    if (!empty($filters['end_date'])) {
      $endDate = CRM_Utils_Type::escape($filters['end_date'], 'String');
      $conditions[] = "run_time <= '$endDate'";
    }

    if (!empty($filters['status'])) {
      if ($filters['status'] === 'success') {
        $conditions[] = "(data NOT LIKE '%error%' AND data NOT LIKE '%failed%')";
      }
      elseif ($filters['status'] === 'error') {
        $conditions[] = "(data LIKE '%error%' OR data LIKE '%failed%')";
      }
    }

    return $conditions ? ' AND ' . implode(' AND ', $conditions) : '';
  }

  /**
   * Get appropriate interval based on number of days
   */
  private function getIntervalByDays($days) {
    if ($days <= 7) {
      return 'HOUR'; // Hourly data for week view
    }
    elseif ($days <= 90) {
      return 'DAY'; // Daily data for month/quarter view
    }
    else {
      return 'WEEK'; // Weekly data for year view
    }
  }

  /**
   * Determine job status based on recent performance
   */
  private function determineJobStatus($errorCount, $totalCount, $isActive) {
    if (!$isActive) {
      return 'inactive';
    }

    if ($totalCount == 0) {
      return 'unknown';
    }

    $errorRate = ($errorCount / $totalCount) * 100;

    if ($errorRate == 0) {
      return 'success';
    }
    elseif ($errorRate <= 5) {
      return 'warning';
    }
    else {
      return 'error';
    }
  }

  /**
   * Determine execution status from job log data
   */
  private function determineExecutionStatus($data) {
    if (empty($data)) {
      return 'unknown';
    }

    $data = strtolower($data);

    if (strpos($data, 'error') !== FALSE || strpos($data, 'failed') !== FALSE || strpos($data, 'exception') !== FALSE) {
      return 'error';
    }
    elseif (strpos($data, 'warning') !== FALSE) {
      return 'warning';
    }
    else {
      return 'success';
    }
  }

  /**
   * Extract duration from job log data
   */
  private function extractDurationFromData($data) {
    if (preg_match('/duration:\s*([0-9.]+)/', $data, $matches)) {
      return (float)$matches[1];
    }

    if (preg_match('/took\s+([0-9.]+)\s*seconds?/', $data, $matches)) {
      return (float)$matches[1];
    }

    if (preg_match('/execution time:\s*([0-9.]+)/', $data, $matches)) {
      return (float)$matches[1];
    }

    return 0;
  }

  /**
   * Get date range options for dropdown
   */
  private function getDateRangeOptions() {
    return [
      '7' => ts('Last 7 days'),
      '30' => ts('Last 30 days'),
      '90' => ts('Last 90 days'),
      '365' => ts('Last year'),
      'custom' => ts('Custom range')
    ];
  }

  /**
   * AJAX endpoint for fetching updated statistics
   */
  public static function getStatsAjax() {
    $filters = [];

    if (!empty($_POST['job_id'])) {
      $filters['job_id'] = $_POST['job_id'];
    }

    if (!empty($_POST['start_date'])) {
      $filters['start_date'] = $_POST['start_date'];
    }

    if (!empty($_POST['end_date'])) {
      $filters['end_date'] = $_POST['end_date'];
    }

    if (!empty($_POST['days'])) {
      $filters['days'] = (int)$_POST['days'];
    }

    $page = new self();
    $stats = $page->getJobStatistics($filters);

    CRM_Utils_JSON::output($stats);
  }

  /**
   * AJAX endpoint for fetching chart data
   */
  public static function getChartDataAjax() {
    $chartType = CRM_Utils_Request::retrieve('chart_type', 'String');
    $filters = [];

    if (!empty($_POST['job_id'])) {
      $filters['job_id'] = $_POST['job_id'];
    }

    if (!empty($_POST['start_date'])) {
      $filters['start_date'] = $_POST['start_date'];
    }

    if (!empty($_POST['end_date'])) {
      $filters['end_date'] = $_POST['end_date'];
    }

    if (!empty($_POST['days'])) {
      $filters['days'] = (int)$_POST['days'];
    }

    $page = new self();
    $chartData = $page->getChartData($chartType, $filters);

    CRM_Utils_JSON::output($chartData);
  }

  /**
   * AJAX endpoint for fetching job list
   */
  public static function getJobListAjax() {
    $page = new self();
    $jobList = $page->getJobList();

    CRM_Utils_JSON::output($jobList);
  }

  /**
   * AJAX endpoint for fetching recent executions
   */
  public static function getRecentExecutionsAjax() {
    $limit = CRM_Utils_Request::retrieve('limit', 'Integer', CRM_Core_DAO::$_nullObject, FALSE, 50);
    $page = new self();
    $executions = $page->getRecentExecutions($limit);

    CRM_Utils_JSON::output($executions);
  }
}
