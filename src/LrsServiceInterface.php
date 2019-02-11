<?php

namespace Drupal\h5p_analytics;

/**
 * Interface LrsServiceInterface.
 */
interface LrsServiceInterface {

  /**
   * Returns batch size value or a default one if value is less than 1
   * @return int Batch size
   */
  public function getBatchSize();

  /**
   * Processes statements into batches
   */
  public function processStatementsCron();

  /**
   * Sends statements to the LRS endpoint.
   * Throws exceptions in case request is not successful.
   * @param  array  $data   Array of statements
   * @return mixed          Response object of HTTP request
   */
  public function sendToLrs(array $data);

}
