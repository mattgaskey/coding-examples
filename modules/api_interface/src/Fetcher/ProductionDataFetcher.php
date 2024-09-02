<?php

namespace Drupal\api_interface\Fetcher;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;

/**
 * Base class for fetching and manipulating data from an API.
 */
class ProductionDataFetcher {
  /**
   * The cache id.
   *
   * @var string
   */
  protected $cacheId;

  /**
   * The API endpoint.
   *
   * @var string
   */
  protected $endpoint;

  /**
   * The API endpoint path component.
   *
   * @var string
   */
  protected $path;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The REST client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $restClient;

  /**
   * The Drupal cache backend interface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Constructs a new ApiDataFetcher object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \GuzzleHttp\ClientInterface $rest_client
   *   The REST client service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The REST client service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $rest_client, CacheBackendInterface $cache_backend) {
    $this->configFactory = $config_factory;
    $this->restClient = $rest_client;
    $this->cacheBackend = $cache_backend;

    // Set these values in a secure way, usually via secrets referenced in settings.php.
    $basic_auth = $this->configFactory->get('api_interface.settings')->get('basic_auth');
    $host = $this->configFactory->get('api_interface.settings')->get('host');

    if (empty($basic_auth) || empty($host)) {
      $this->endpoint = NULL;
    }
    else {
      $this->endpoint = $basic_auth . '@' . $host;
    }
  }

  /**
   * Provides the cache ID.
   *
   * @return string|null
   *   The cache ID.
   */
  protected function getCacheId() {
    if ($this->cacheId !== NULL) {
      return 'api_interface.' . $this->cacheId;
    }
    else {
      return NULL;
    }
  }

  /**
   * Makes a request to the API.
   *
   * @param string $endpoint
   *   The endpoint.
   * @param string $client_id
   *   The client ID.
   * @param string $client_secret
   *   The client secret.
   *
   * @return array
   *   The JSON response data.
   */
  protected function makeRequest($endpoint, $client_id, $client_secret) {
    try {
      $response = $this->restClient->request('GET', $endpoint, [
        'headers' => [
          'client_id' => $client_id,
          'client_secret' => $client_secret,
        ],
        'verify' => FALSE,
      ]);
      $data = json_decode($response->getBody(), TRUE);
      return $data;
    } catch (ClientException $e) {
      $this->setExceptionState($e->getResponse()->getStatusCode(), $e->getMessage());
      return [];
    } catch (ServerException $e) {
      $this->setExceptionState($e->getResponse()->getStatusCode(), $e->getMessage());
      return [];
    } catch (RequestException $e) {
      $this->setExceptionState($e->getResponse()->getStatusCode(), $e->getMessage());
      return [];
    }
  }

  /**
   * Fetches raw data from the API.
   *
   * @return array
   *   The raw data.
   */
  protected function fetchRawData() {
    $cid = $this->getCacheId();

    if ($cache = $this->cacheBackend->get($cid)) {
      return $cache->data;
    }

    // Set these values in a secure way, usually via secrets referenced in settings.php.
    $client_id = $this->configFactory->get('api_interface.settings')->get('client_id');
    $client_secret = $this->configFactory->get('api_interface.settings')->get('client_secret');

    if (empty($client_id) || empty($client_secret) || empty($this->endpoint)) {
      // Log the error and return an empty array so API failures return empty render arrays
      // instead of server errors.
      \Drupal::logger('api_interface')->error(
        'The config settings for the API could not be found. Please check the settings file for this site, 
        and ensure your env variables are properly set.');
      return [];
    }

    $endpoint = $this->buildUrlForPath();

    $data = $this->makeRequest($endpoint, $client_id, $client_secret);

    if (!empty($data)) {
      $data = $data['resultSet1'];
    }
    else {
      return [];
    }

    // Cache for 1 day.
    $this->cacheBackend->set($cid, $data, time() + 86400);

    return $data;
  }

  /**
   * Builds the full URL for a specific path.
   *
   * @return string
   *   The full URL.
   */
  protected function buildUrlForPath() {
    return 'https://' . $this->endpoint . '/' . ltrim($this->path, '/');
  }

  /**
   * Sets the endpoint path component.
   *
   * @param string $path
   *   The path.
   *
   * @return $this
   */
  protected function setPath(string $path) {
    $this->path = str_replace(',', '%2C', $path);

    return $this;
  }

  /**
   * Create a URL that points to the commodity/country view production page.
   *
   * @param string $type
   *   'Commodity' or 'country'.
   * @param string $code
   *   Country/commodity code.
   * @param string $name
   *   Country/commodity name.
   *
   * @return array|mixed[]
   *   Link pointing to commodity or country view in production section.
   */
  protected function generateUrl($type, $code, $name) {
    $uri = strtolower('base:/data/production/' . $type . '/' . $code);
    $url = Url::fromUri($uri);
    $link = Link::fromTextAndUrl($name, $url);
    return $link->toRenderable();
  }

  /**
   * Provides the cache ID.
   *
   * @param string $statusCode
   * The statusCode from Guzzle Exception getResponse()->getStatusCode().
   * @param string $message
   * The message from Guzzle Exception getMessage().
 */
  protected function setExceptionState($statusCode, $message) {
    \Drupal::logger('api_interface')->error('API request failed with status ' . $statusCode . ': ' . $message);
    \Drupal::messenger()->addWarning('There was an issue fetching data for this page.  Content may be missing or not appear as intended.', TRUE);

    // Invalidate cache to always look for non-error state.
    $this->cacheBackend->invalidate($this->cacheId);
  }

}
