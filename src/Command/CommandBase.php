<?php

/**
 * @file
 * Contains \tv\Command\CommandBase
 */

namespace tv\Command;

use Moinax\TvDb\Episode;
use Moinax\TvDb\Serie;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use tv\Link\LinkProviderInterface;
use tv\TvSourceInterface;

/**
 * The CommandBase class.
 */
class CommandBase extends Command {

  protected $tvdb;
  protected $linkProvider;
  protected $shows;

  public function __construct($name = NULL, TvSourceInterface $tvdb, LinkProviderInterface $link, array $shows) {
    parent::__construct($name);
    $this->tvdb = $tvdb;
    $this->linkProvider = $link;
    $this->shows = $shows;
  }

  protected function getAllSeriesEpisodes() {
    $results = [];
    foreach ($this->shows as $name => $imdbid) {
      $serie = $this->tvdb->getSerie($imdbid);
      $results[$serie->imdbId] = [
        'serie' => $serie,
        'episodes' => $this->tvdb->getEpisodes($serie->id),
      ];
    }
    return $results;
  }

  protected function displayAsTable(SymfonyStyle $io, $results) {
    $rows = [];
    foreach ($results as $day => $shows_per_day) {
      foreach ($shows_per_day as $result) {
        $rows[] = $this->getRow(...$result);
      }
    }
    $header = ['Show', 'Episode Title', 'Season/Episode', 'Date', 'Link'];
    $io->table($header, $rows);
  }

  protected function getRow(Serie $serie, Episode $episode) {
    return [
      $serie->name,
      $episode->name,
      sprintf('S%02d E%02d', $episode->season, $episode->number),
      $this->getFormattedDateTime($episode),
      $this->linkProvider->getLink($serie, $episode),
    ];
  }

  protected function getFormattedDateTime(Episode $episode) {
    $date_time = 'N/A';
    $today = new \DateTimeImmutable();
    if ($episode->firstAired) {
      $date_time = $episode->firstAired->format('D - d/m/Y');

      // If the episode comes out today, then make it green.
      if ($episode->firstAired->format('d/m/y') === $today->format('d/m/y')) {
        $date_time = "<info>$date_time</info>";
      }
      elseif ($episode->firstAired->getTimestamp() > $today->getTimestamp()) {
        // Episode in the future.
        $date_time = "<comment>$date_time</comment>";
      }
      else {
        // Episode in the past.
        $date_time = "<red>$date_time</red>";
      }
    }
    return $date_time;
  }

}
