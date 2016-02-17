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

  protected function displayAsTable(SymfonyStyle $io, $header, $results) {
    $rows = [];
    foreach ($results as $day => $shows_per_day) {
      foreach ($shows_per_day as $result) {
        $rows[] = $this->getRow(...$result);
      }
    }
    $io->table($header, $rows);
  }

  protected function getRow(Serie $serie, Episode $episode) {
    $time = 'N/A';
    if ($episode->firstAired) {
      $today = new \DateTime();
      $episode_date = clone $episode->firstAired;
      $episode_date->add(new \DateInterval('P1D'));
      if ($episode_date->getTimestamp() >= $today->getTimestamp()) {
        $time = $episode_date->diff($today)->format('%D days %M months');
      }
    }
    return [
      $serie->name,
      $episode->name,
      sprintf('S%02d E%02d', $episode->season, $episode->number),
      $this->getFormattedDateTime($episode),
      $time,
      $this->linkProvider->getLink($serie, $episode),
    ];
  }

  protected function getFormattedDateTime(Episode $episode) {
    if (!$episode->firstAired) {
      return 'N/A';
    }
    $today = new \DateTimeImmutable();
    $format = 'red';

    // If the episode comes out today, then make it green.
    if ($episode->firstAired->format('d/m/y') === $today->format('d/m/y')) {
      $format = 'info';
    }
    elseif ($episode->firstAired->getTimestamp() > $today->getTimestamp()) {
      // Episode in the future.
      $format = 'comment';
    }

    $date_time = $episode->firstAired->format('D - d/m/Y');
    return $format ? "<$format>$date_time</$format>" : $date_time;
  }

}
