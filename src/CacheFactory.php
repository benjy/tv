<?php

/**
 * @file
 * Contains \tv\CacheFactory
 */

namespace tv;

use Doctrine\Common\Cache\FilesystemCache;

class CacheFactory {

  public static function create() {
    $dir = sys_get_temp_dir() . '/tv';
    if (!file_exists($dir)) {
      mkdir($dir);
    }
    return new FilesystemCache($dir, 'tv');
  }
}
