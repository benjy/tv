<?php

/**
 * @file
 * Contains \tv\Link\Imdb
 */

namespace tv\Link;

use Moinax\TvDb\Episode;
use Moinax\TvDb\Serie;

/**
 * The Imdb class.
 */
class Imdb implements LinkProviderInterface {

  /**
   * Gets the link to the show.
   *
   * @param \Moinax\TvDb\Episode $episode
   *   The episode to generate a link for.
   *
   * @return string
   *   The link to the episode.
   */
  public function getLink(Serie $serie, Episode $episode) {
    return $episode->imdbId ? sprintf('http://imdb.com/title/%s', $episode->imdbId) : 'N/A';
  }

}
