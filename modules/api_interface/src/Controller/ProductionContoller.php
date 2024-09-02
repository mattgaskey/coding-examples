<?php

namespace Drupal\api_interface\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\fas_production\Service\ListFetcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Production routes.
 */
class FasProductionController extends ControllerBase {

  /**
   * ListFetcher object.
   *
   * @var \Drupal\fas_production\Service\ListFetcher
   */
  protected $listFetcher;

  /**
   * FasProductionController constructor.
   *
   * @param \Drupal\fas_production\Service\ListFetcher $list_fetcher
   *   List API fetcher object.
   */
  public function __construct(ListFetcher $list_fetcher, EntityTypeManager $entity_type_manager) {
    $this->listFetcher = $list_fetcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('fas_production.list_fetcher')
    );
  }

  /**
   * Builds the GlobalProduction response.
   */
  public function globalProduction() {
    $build['content'] = [
      '#theme' => 'production_global',
      '#commodities' => $this->listFetcher->loadCommodities(),
      '#countries' => $this->listFetcher->loadCountries(),
    ];
    return $build;
  }
}