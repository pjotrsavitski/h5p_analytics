<?php

namespace Drupal\h5p_analytics;

/**
 * Interface LrsServiceInterface.
 */
interface LrsServiceInterface {

  /**
   * Returns batch size value or a default one if value is less than 1
   * @return int
   *   Batch size
   */
  public function getBatchSize();

  /**
   * Processes statements into batches
   */
  public function processStatementsCron();

  /**
   * Sends statements to the LRS endpoint.
   * Throws exceptions in case request is not successful.
   *
   * @param  array  $data
   *   Array of statements
   *
   * @return mixed
   *   Response object of HTTP request
   */
  public function sendToLrs(array $data);

  /**
   * Returns an array of statement statistics objects that have parameters:
   * code, reason and total.
   * Please note that reason will have any value that is frist in the grouped
   * result set as there could possibly be different reasons for the same code.
   *
   * @return array
   *   Array of statement statistics objects
   */
  public function getStatementStatistics();

  /**
   * Returns an array of http request statistics objects that have parameters:
   * code, reason, error, total.
   * Please note that both reason and error will have any value that is first in
   * the grouped result set as there could possibly be different reasons and
   * errors for the same code.
   *
   * @return array
   *   An array of http request statistics objects
   */
  public function getRequestStatistics();

}
