<?php

namespace Drupal\h5p_analytics;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\h5p_analytics\Exception\MissingConfigurationException;

/**
 * Class LrsService.
 */
class LrsService implements LrsServiceInterface {

  /**
   * Config settings
   * @var string
   */
  const SETTINGS = 'h5p_analytics.settings';

  /**
   * Default batch size
   * @var integer
   */
  const DEFAULT_BATCH_SIZE = 100;

  /**
   * Symfony\Component\DependencyInjection\ContainerAwareInterface definition.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerAwareInterface
   */
  protected $queue;
  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;
  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;
  /**
   * Constructs a new LrsService object.
   */
  public function __construct(ContainerAwareInterface $queue, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    $this->queue = $queue;
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
  }

   /**
    * {@inheritdoc}
    */
  public function getBatchSize() {
    $config = $this->configFactory->get(static::SETTINGS);
    $size = (int)$config->get('batch_size');

    return ($size > 0) ? $size : static::DEFAULT_BATCH_SIZE;
  }

  /**
   * {@inheritdoc}
   */
  public function processStatementsCron() {
    $statements = $this->queue->get('h5p_analytics_statements');

    if ($statements->numberOfItems() > 0) {
      $batches = $this->queue->get('h5p_analytics_batches');
      $size = $this->getBatchSize();

      $totalBatches = ceil($statements->numberOfItems() / $size);

      foreach (range(1, $totalBatches) as $batch) {
        $data = [];
        while((sizeof($data) < $size) && ($item = $statements->claimItem())) {
          $data[] = $item->data;
          $statements->deleteItem($item);
        }
        $batches->createItem($data);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sendToLrs(array $data) {
    $config = $this->configFactory->get(static::SETTINGS);
    $endpoint = $config->get('xapi_endpoint');
    $authUser = $config->get('key');
    $authPassword = $config->get('secret');

    if ( !( $endpoint && $authUser && $authPassword ) ) {
      throw new MissingConfigurationException('At least one of the required LRS configuration settings is missing!');
    }

    $url = $endpoint . '/statements';
    $options = [
      'json' => $data,
      'auth' => [$authUser, $authPassword],
      'headers' => [
        'X-Experience-API-Version' => '1.0.1',
      ],
      'timeout' => 45,
    ];

    try {
      return $this->httpClient->post($url, $options);
    } catch (RequestException $e) {
      $debug = [
        'request' => [
          'url' => $url,
          'count' => is_array($data) ? sizeof($data) : 1,
        ],
        'response' => [
          'code' => $e->getCode(),
          'status' => $e->hasResponse() ? $e->getResponse()->getReasonPhrase() : '',
          'error' => $e->getMessage(),
        ]
      ];
      $this->loggerFactory->get('h5p_analytics')->error(json_encode($debug));
      // TODO This could throw an exception, needs to be handled
      \Drupal::service('database')->insert('h5p_analytics_request_log')
      ->fields([
        'code' => $e->getCode(),
        'reason' => $e->hasResponse() ? $e->getResponse()->getReasonPhrase() : '',
        'error' => $e->getMessage(),
        'count' => sizeof($data),
        'data' => json_encode($data),
        'created' => REQUEST_TIME,
      ])
      ->execute();
      throw $e;
    } catch (\Exception $e) {
      // TODO Need to make sure this one even exists
      $this->loggerFactory->get('h5p_analytics')->error($e->getMessage());
      throw $e;
    }
  }

}
