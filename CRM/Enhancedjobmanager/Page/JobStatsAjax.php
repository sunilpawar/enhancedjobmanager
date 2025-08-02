<?php
use CRM_Enhancedjobmanager_ExtensionUtil as E;

class CRM_Enhancedjobmanager_Page_JobStatsAjax {

  /**
   * Get job statistics data
   */
  public static function getStats() {
    $filters = self::getFiltersFromRequest();
    $page = new CRM_Enhancedjobmanager_Page_JobStats();
    $stats = $page->getJobStatistics($filters);

    CRM_Utils_JSON::output($stats);
  }

  /**
   * Get chart data
   */
  public static function getChartData() {
    $chartType = CRM_Utils_Request::retrieve('chart_type', 'String');
    $filters = self::getFiltersFromRequest();

    $page = new CRM_Enhancedjobmanager_Page_JobStats();
    $chartData = $page->getChartData($chartType, $filters);

    CRM_Utils_JSON::output([
      'labels' => array_column($chartData, 'period'),
      'data' => array_column($chartData, 'value')
    ]);
  }

  /**
   * Get job list data
   */
  public static function getJobList() {
    $page = new CRM_Enhancedjobmanager_Page_JobStats();
    $jobList = $page->getJobList();

    CRM_Utils_JSON::output($jobList);
  }

  /**
   * Get recent executions
   */
  public static function getRecentExecutions() {
    $limit = CRM_Utils_Request::retrieve('limit', 'Integer', CRM_Core_DAO::$_nullObject, FALSE, 50);
    $page = new CRM_Enhancedjobmanager_Page_JobStats();
    $executions = $page->getRecentExecutions($limit);

    CRM_Utils_JSON::output($executions);
  }

  /**
   * Get job execution details
   */
  public static function getExecutionDetails() {
    $executionId = CRM_Utils_Request::retrieve('execution_id', 'Integer');

    if (!$executionId) {
      CRM_Utils_JSON::output(['error' => 'Missing execution ID']);
      return;
    }

    $query = "
      SELECT
        jl.*,
        j.name as job_name,
        j.api_entity,
        j.api_action,
        j.description as job_description
      FROM civicrm_job_log jl
      LEFT JOIN civicrm_job j ON jl.job_id = j.id
      WHERE jl.id = %1
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$executionId, 'Integer']
    ]);

    if ($dao->fetch()) {
      $details = [
        'id' => $dao->id,
        'job_id' => $dao->job_id,
        'job_name' => $dao->job_name ?: $dao->name,
        'api_call' => $dao->api_entity . '.' . $dao->api_action,
        'command' => $dao->command,
        'description' => $dao->description,
        'job_description' => $dao->job_description,
        'run_time' => $dao->run_time,
        'data' => $dao->data,
        'formatted_data' => self::formatJobLogData($dao->data)
      ];

      CRM_Utils_JSON::output($details);
    }
    else {
      CRM_Utils_JSON::output(['error' => 'Execution not found']);
    }
  }

  /**
   * Get filters from request
   */
  private static function getFiltersFromRequest() {
    $filters = [];

    $jobId = CRM_Utils_Request::retrieve('job_id', 'Integer');
    if ($jobId) {
      $filters['job_id'] = $jobId;
    }

    $startDate = CRM_Utils_Request::retrieve('start_date', 'String');
    if ($startDate) {
      $filters['start_date'] = $startDate;
    }

    $endDate = CRM_Utils_Request::retrieve('end_date', 'String');
    if ($endDate) {
      $filters['end_date'] = $endDate;
    }

    $days = CRM_Utils_Request::retrieve('days', 'Integer');
    if ($days) {
      $filters['days'] = $days;
    }

    return $filters;
  }

  /**
   * Format job log data for display
   */
  private static function formatJobLogData($data) {
    if (empty($data)) {
      return ['message' => 'No data available'];
    }

    // Try to parse as JSON first
    $json = json_decode($data, TRUE);
    if ($json !== NULL) {
      return $json;
    }

    // If not JSON, try to extract key information
    $formatted = [];

    // Look for common patterns
    if (preg_match('/duration:\s*([0-9.]+)/', $data, $matches)) {
      $formatted['duration'] = $matches[1] . ' seconds';
    }

    if (preg_match('/memory:\s*([0-9.]+[KMG]?B?)/', $data, $matches)) {
      $formatted['memory_usage'] = $matches[1];
    }

    if (preg_match('/processed:\s*([0-9,]+)/', $data, $matches)) {
      $formatted['records_processed'] = $matches[1];
    }

    if (preg_match('/error/i', $data)) {
      $formatted['status'] = 'error';
      $formatted['has_errors'] = TRUE;
    }
    elseif (preg_match('/warning/i', $data)) {
      $formatted['status'] = 'warning';
      $formatted['has_warnings'] = TRUE;
    }
    else {
      $formatted['status'] = 'success';
    }

    // Add raw data
    $formatted['raw_data'] = $data;

    return $formatted;
  }
}
