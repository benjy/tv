<?php

namespace tv\Link;

use Moinax\TvDb\Episode;
use Moinax\TvDb\Serie;

interface LinkProvider {

  public function getLink(Serie $serie, Episode $episode);

}
