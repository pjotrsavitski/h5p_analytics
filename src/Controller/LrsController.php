<?php

namespace Drupal\h5p_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class LrsController.
 */
class LrsController extends ControllerBase {

  /**
   * H5P analytics statements queue
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $statementsQueue;

  /**
   * H5P analytics logger
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Controller constructor
   * @param QueueFactory                  $queueFactory  Queue factory
   * @param LoggerChannelFactoryInterface $loggerFactory Logger factory
   */
  public function __construct(QueueFactory $queue_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->statementsQueue = $queue_factory->get('h5p_analytics_statements');
    $this->logger = $logger_factory->get('h5p_analytics');
  }

  /**
   * [create description]
   * @param  ContainerInterface $container [description]
   * @return [type]                        [description]
   */
  public static function create(ContainerInterface $container) {
    $queueFactory = $container->get('queue');
    $loggerFactory = $container->get('logger.factory');

    return new static($queueFactory, $loggerFactory);
  }

  /**
   * xAPI
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   Return response with statement data.
   */
  public function xapi(Request $request) {
    $statement = $request->request->get('statement');

    if (!$statement) {
      return new JsonResponse([], 400);
    }

    $data = json_decode($statement, TRUE);

    if (!$data) {
      return new JsonResponse([], 400);
    }

    // Set timestamp as browser side one is unreliable and statement storage in
    // LRS will involve a delay
    $data['timestamp'] = date(DATE_RFC3339);

    try {
      $this->statementsQueue->createItem($data);
    } catch (Exception $e) {
      $this->logger->error($e->getTraceAsString());
      return new JsonResponse([], 500);
    }

    return new JsonResponse($data, 200);
  }

}
