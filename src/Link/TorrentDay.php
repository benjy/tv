<?php

/**
 * @file
 * Contains \tv\Link\TorrentDay
 */

namespace tv\Link;

use Moinax\TvDb\Episode;
use Moinax\TvDb\Serie;

/**
 * The TorrentDay link provider.
 */
class TorrentDay implements LinkProviderInterface {

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
    // Trim off any year on the series title, e.g. The Flash (2012) becomes
    // The Flash.
    $name = trim(preg_replace('/\(.+/', '', $serie->name));
    return str_replace(' ', '+', sprintf('torrentday.com/browse.php?search=%s', $name));
  }

}
