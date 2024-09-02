<?php

namespace Drupal\api_interface\Service;

use Drupal\api_interface\Fetcher\ProductionDataFetcher;

/**
 * Service class for manipulating bare lists of data.
 */
class ListFetcher extends ProductionDataFetcher {

  /**
   * Loads the full list of countries.
   *
   * @return array
   *   The loaded data.
   */
  public function loadCountries() {
    $this->cacheId = 'country_details';
    $path = '/production_data/country_details';
    $this->setPath($path);
    $rows = $this->fetchRawData();
    $country_list = [];

    foreach ($rows as $row) {
      $country_list[$row['countryCode']] = [
        'code' => $row['countryCode'],
        'label' => $row['countryName'],
        'link' => $this->generateUrl('country', $row['countryCode'], $row['countryName']),
      ];
    }

    usort($country_list, function ($a, $b) {
      return strcmp($a['label'], $b['label']);
    });

    return $country_list;
  }

  /**
   * Loads the full list of commodities.
   *
   * @return array
   *   The loaded data.
   */
  public function loadCommodities() {
    $this->cacheId = 'commodity_detail';
    $path = '/production_data/commodity_detail';
    $this->setPath($path);
    $rows = $this->fetchRawData();
    $commodity_list = [];

    foreach ($rows as $row) {
      // If commodity does not have a parent group.
      if (empty($row['commodityGroup'])) {
        $commodity_list[$row['commodityName']] = [
          'group' => $row['commodityName'],
          'code' => $row['commodityCode'],
          'label' => $row['commodityName'],
          'link' => $this->generateUrl('commodity', $row['commodityCode'], $row['commodityName']),
        ];
      }
      else {
        // If commodityGroup has not been added yet.
        if (!isset($commodity_list[$row['commodityGroup']])) {
          $commodity_list[$row['commodityGroup']] = [
            'group' => $row['commodityGroup'],
            'sub_commodities' => [],
          ];
        }
        // Add subcommodity details.
        $commodity_details = [
          'group' => $row['commodityGroup'],
          'code' => $row['commodityCode'],
          'label' => $row['commodityName'],
          'link' => $this->generateUrl('commodity', $row['commodityCode'], $row['commodityName']),
        ];

        // Check if the commodity already exists in the sub_commodities array.
        $commodityExists = array_search($commodity_details, $commodity_list[$row['commodityGroup']]['sub_commodities']);

        // If the commodity does not exist, add it to the sub_commodities array.
        if ($commodityExists === FALSE) {
          array_push($commodity_list[$row['commodityGroup']]['sub_commodities'], $commodity_details);
        }
      }
    }

    foreach ($commodity_list as $group => $commodity) {
      if (isset($commodity['sub_commodities'])) {
        usort($commodity['sub_commodities'], function ($a, $b) {
          return strcmp($a['label'], $b['label']);
        });
        $commodity_list[$group]['sub_commodities'] = $commodity['sub_commodities'];
      }
    }

    ksort($commodity_list);

    return $commodity_list;
  }

}
