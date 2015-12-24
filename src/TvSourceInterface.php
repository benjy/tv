<?php

/**
 * @file
 * Contains \tv\TvSourceInterface.
 */

namespace tv;

// @todo, depending on these breaks our abstraction.
use Moinax\TvDb\Episode;
use Moinax\TvDb\Serie;

interface TvSourceInterface {

  public function getSerie(string $id) : Serie;

  public function getEpisodes(int $serie_id) : array;

  public function getLatestEpisode(array $episodes) : Episode;
}
