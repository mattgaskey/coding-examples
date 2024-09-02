# APi Interface

This example Drupal module contains selected components from a larger API
interface that was used to query an external data source for commodities
and countries. It contains an end-to-end example of an object-oriented
approach to creating an extensible and reusable architecture.

It is in use in its extended form on a current production site, but has
been anonymized for instructive purposes here.

## Structure

The primary structure of the module is as follows:

- A base class `ProductionDatatFetcher` that sets up an HTTP Client using GuzzleHttp. This base class contains all the necessary methods for reading custom settings to build the API endpoint URL, setting cache contexts for API responses, making requests to the API, handling errors, and generating an expected data structure.
- A service class `ListFetcher` which extends the base class, and queries specific parts of the API, and transforms that data into usable arrays.
- A controller `ProductionController` which calls the `ListFetcher` methods, and makes the resulting data available to a custom template.
- A custom template `production-global.html.twig` which renders HTML elements using data from the API.
- A routing table `api_interface.routing.yml` which handles when and where the API request methods are triggered.

## Requirements

This module was designed for Drupal 10, but should be backwards compatible with versions going back to 8.x and 9.x.

No other custom or contrib Drupal modules are necessary to make this module setup work.  However, the structure of the external API requests and responses would need to be taken into account in order to use this module in another application.

## Installation

This module suite is installed like any other contributed module. For further information, see [Installing Drupal Modules](https://drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration

- The only configuration settings associated with this module are specific to the external API being used.  The base class expects four items to be set via a `settings.php` file:
  - basic_auth: generally a `username:password` pair
  - host: the URL or host domain of the API endpoint
  - client_id: first part of the API access token
  - client_secret: second part of the API access token
- This allows for test versions to be set in `settings.local.php` and the like.  A common pattern for production sites is to set these values via a secrets file, or using environment variables, then populating the relevant `settings.php` file with methods that can read from those sources.

## Troubleshooting / known issues

Most issues arise from empty response bodies or failed API requests.  These are handled throught the module by returning an empty array, along with the error response code and message.  This allows page renders to continue without data from the API, while rendering whitespace where custom components would normally be rendered. This keeps API errors from causing server errors, but does not give useful information to the end user.  This can be alleviated through further error handling either by extending a service class, or by providing default values in the templates.
