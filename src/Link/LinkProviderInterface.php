<?php

namespace tv\Link;

use Moinax\TvDb\Episode;
use Moinax\TvDb\Serie;

interface LinkProviderInterface {

  public function getLink(Serie $serie, Episode $episode);

}
