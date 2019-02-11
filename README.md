# H5P Analytics (h5p_analytics)

A Drupal 8 integration of Experience API (xAPI) statements emitted by H5P content types to be captured and sent to Learning Record Store (LRS).

## Requirements

* PHP 7.0+
* Drupal 8 (8.6+)
* H5P module for Drupal

## Installation

* Add to `/modules` directory and activate
* Fill in the LRS configuration data
* Setup the cron job to be triggered every 30 minutes

## General logic

Module integrates with H5P on the client size (covering both internal content within normal pages and an externally embedded one). The xAPI event listener is being set up and statements are sent to the backend. The backend is capturing statements and adding those to a queue. A periodic background process will go through the queue and combine individual statements into batches with configurable size. Those would in turn be processed by the BatchQueue job and sent to the LRS if possible. All HTTP requests would have their failures logged, with probability of the same batch appearing multiple times under specific circumstances. Statistical data fro failed or successful requests would be added to a standalone log (failures would include JSON-encoded statements batch).

## TODO

* Add better handling of different response cases (come up with a solution for request timeout).
* Make sure that a simple status page with counts of statements for certain codes is present (mostly 200 for success and anything else for failure).
* Make proper use of DI where appropriate instead of using `\Drupal::service()`.
* See if it would make sense to remove the statement data from the request log after a certain period of time (storing that indefinitely seems wasteful and pointless).
* Add a functionality that would allow one to test if used LRS connection data is correct. Making a test request to the LRS might be good enough (make sure that URI exists and is an LRS + authentication data is correct).

## Issues

* Handling of LRS responses is incomplete. Current solution might not be able to handle all the meaningful HTTP call failures that would allow retrying with the same batch (a few cases are handled, but that should be a default behaviour).
* Logging is eager and stores full batch dataset on each failed HTTP request. Might need to be discontinued or automatically removed by a cleanup procedure.
* Needs more testing with larger volumes of data outside of the local environment
