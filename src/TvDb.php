<?php

/**
 * @file
 * Contains \tv\TvDb
 */

namespace tv;

use Doctrine\Common\Cache\CacheProvider;
use Moinax\TvDb\Client;
use Moinax\TvDb\Episode;
use Moinax\TvDb\Serie;

/**
 * The TvDb class.
 */
class TvDb implements TvSourceInterface {

  const CACHE_TIME = 86400 * 3;

  /**
   * The TvDb API key.
   */
  const TVDB_API_KEY = '5AA24E7B3E86E8DA';

  /**
   * The TvDb url.
   */
  const TVDB_URL = 'http://thetvdb.com';

  public function __construct(Client $tvdb, CacheProvider $cache) {
    $this->tvdb = $tvdb;
    $this->cache = $cache;
  }

  public function getSerie(string $id) : Serie {
    if ($serie = $this->cache->fetch($id)) {
      return $serie;
    }

    $serie = $this->tvdb->getSerieByRemoteId(['imdbid' => $id]);
    $this->cache->save($id, $serie);
    return $serie;
  }

  public function getEpisodes(int $serie_id) : array {
    if ($episodes = $this->cache->fetch("$serie_id:episodes")) {
      return $episodes;
    }
    $episodes = $this->tvdb->getSerieEpisodes($serie_id);
    $this->cache->save("$serie_id:episodes", $episodes['episodes'], static::CACHE_TIME);
    return $episodes['episodes'];
  }

  public function getLatestEpisode(array $episodes) : Episode {
    $episodes = array_values($episodes);

    // Get the latest episode which was aired after today.
    $today = strtotime('-2 days');
    $i = 0;
    do {
      $current_episode = $episodes[$i];

      // If we don't have a date, doesn't mean it's the latest, just keep
      // looking.
      if($current_episode->firstAired) {
        // Great, we've found one.
        if ($current_episode->firstAired->getTimestamp() > $today) {
          break;
        }
      }
    } while (isset($episodes[++$i]));

    return $current_episode;
  }

}
