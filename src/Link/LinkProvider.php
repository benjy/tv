<?php

namespace tv\Link;

use Moinax\TvDb\Episode;

interface LinkProvider {

  public function getLink(Episode $episode);

}
