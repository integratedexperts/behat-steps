<?php

namespace IntegratedExperts\BehatSteps\D7;

use Behat\Gherkin\Node\TableNode;

/**
 * Trait FileTrait.
 *
 * @package IntegratedExperts\BehatSteps\D7
 */
trait FileTrait {

  /**
   * Files ids.
   *
   * @var array
   */
  static protected $fileIds = [];

  /**
   * Create managed file.
   *
   * @code
   * Given managed file:
   * | path      |
   * | file1.txt |
   * @endcode
   *
   * @Given managed file:
   */
  public function fileCreateManaged(TableNode $nodesTable) {
    $fids = new static();

    foreach ($nodesTable->getHash() as $nodeHash) {
      $node = (object) $nodeHash;

      if (empty($node->path)) {
        throw new \RuntimeException('Missing required field "path".');
      }
      $path = ltrim($node->path, '/');

      // Limited support for remote files: all remote files are considered
      // oembed objects and therefore only oembed'able objects will be saved.
      if (parse_url($node->path, PHP_URL_SCHEME) !== NULL) {
        $provider = media_internet_get_provider($path);
        $file = $provider->save();
      }
      // Local file.
      else {
        // Get fixture file path.
        if ($this->getMinkParameter('files_path')) {
          $full_path = rtrim(realpath($this->getMinkParameter('files_path')), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
          if (is_file($full_path)) {
            $path = $full_path;
          }
        }

        if (!is_readable($path)) {
          throw new \RuntimeException(sprintf('Unable to find file "%s"', $path));
        }

        $destination = 'public://' . basename($path);
        $file = file_save_data(file_get_contents($path), $destination, FILE_EXISTS_REPLACE);
      }

      if (!$file) {
        throw new \RuntimeException(sprintf('Unable to save managed file "%s"', $path));
      }

      array_push($fids::$fileIds, $file->fid);
    }
  }

  /**
   * Delete managed files defined by provided properties.
   *
   * @code
   * Given no managed files:
   * | filename      |
   * | myfile.jpg    |
   * | otherfile.jpg |
   * @endcode
   *
   * @Given no managed files:
   */
  public function fileDeleteManagedFiles(TableNode $nodesTable) {
    $rows = $nodesTable->getRows();
    $header = reset($rows);
    if (!in_array('filename', $header)) {
      throw new \RuntimeException('Missing required field "filename".');
    }

    foreach ($nodesTable->getHash() as $hash) {
      $files = file_load_multiple([], $hash);
      file_delete_multiple(array_keys($files));
    }
  }

  /**
   * Delete created managed files.
   *
   * @AfterFeature
   */
  public static function fileRemoveAll() {
    $fids = self::$fileIds;
    if (!empty($fids)) {
      foreach ($fids as $fid) {
        $file = file_load($fid);
        if ($file) {
          file_delete($file, TRUE);
        }
      }
    }
  }

}
