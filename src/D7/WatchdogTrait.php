<?php

namespace IntegratedExperts\BehatSteps\D7;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

/**
 * Trait WatchdogTrait.
 *
 * @package IntegratedExperts\BehatSteps\D7
 */
trait WatchdogTrait {

  /**
   * Start time for each scenario.
   *
   * @var int
   */
  protected $watchdogScenarioStartTime;

  /**
   * Store current time.
   *
   * @BeforeScenario
   */
  public function watchdogSetScenarioStartTime(BeforeScenarioScope $scope) {
    // Allow to skip this by adding a tag.
    if ($scope->getScenario()->hasTag('behat-steps-skip:' . __FUNCTION__)) {
      return;
    }

    $this->watchdogScenarioStartTime = time();
  }

  /**
   * Assert for errors since the start of the scenario.
   *
   * Add @error to any scenario that is expected to trigger an error - the
   * error tracking will be ignored.
   *
   * @AfterScenario
   */
  public function watchdogAssertErrors(AfterScenarioScope $scope) {
    // Allow to skip this by adding a tag.
    if ($scope->getScenario()->hasTag('behat-steps-skip:' . __FUNCTION__)) {
      return;
    }

    // Bypass the error checking if the scenario is expected to trigger an
    // error. Such scenarios should be tagged with "@error".
    if (in_array('error', $scope->getScenario()->getTags())) {
      return;
    }

    // Watchdog is not enabled - return silently.
    if (!db_table_exists('watchdog')) {
      return;
    }

    // Select all logged entries for PHP channel that appeared from the start
    // of the scenario.
    $entries = db_select('watchdog', 'w')
      ->fields('w')
      ->condition('w.type', 'php', '=')
      ->condition('w.timestamp', $this->watchdogScenarioStartTime, '>=')
      ->execute()
      ->fetchAll();

    if (empty($entries)) {
      return;
    }

    $errors = [];
    if (!defined('WATCHDOG_WARNING')) {
      define('WATCHDOG_WARNING', 4);
    }

    // Remove entries below severity threshold.
    foreach ($entries as $k => $error) {
      if ($error->severity > WATCHDOG_WARNING) {
        unset($entries[$k]);
        continue;
      }
      $error->variables = unserialize($error->variables);
      $errors[$error->wid] = print_r($error, TRUE);
    }

    if (!empty($errors)) {
      db_delete('watchdog')
        ->condition('wid', array_keys($errors), 'IN')
        ->execute();

      throw new \Exception(sprintf('PHP errors were logged to watchdog during this scenario: %s', PHP_EOL . implode(PHP_EOL . PHP_EOL, $errors)));
    }
  }

}
