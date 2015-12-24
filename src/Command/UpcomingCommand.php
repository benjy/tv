<?php

/**
 * @file
 * Contains \tv\Command\UpcomingCommand
 */

namespace tv\Command;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The UpcomingCommand class.
 */
class UpcomingCommand extends CommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('upcoming')
      ->setDescription('Check the status of all shows');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);
    $io->getFormatter()->setStyle('red', new OutputFormatterStyle('red', 'default'));

    $this->displayAsTable($io, $this->getUpcomingEpisodes());
  }

  /**
   * Display all episodes for the week surrounding the current day.
   */
  protected function getUpcomingEpisodes() {
    $sorted_results = [];

    // Pull out all the latest episodes for each show, keyed by the day of the
    // week.
    foreach ($this->getAllSeriesEpisodes() as $serie_imdbid => $info) {
      $latest_episode = $this->tvdb->getLatestEpisode($info['episodes']);
      // Add one day to allow for the US release times.
      $day = $latest_episode->firstAired ? $latest_episode->firstAired->add(new \DateInterval('P1D'))->format('N') : 0;
      $sorted_results[$day][] = [$info['serie'], $latest_episode];
    }
    ksort($sorted_results);

    // Build the formatted output.
    foreach ($sorted_results as $day => &$shows_per_day) {
      // Sort the episodes within the day as well.
      usort($shows_per_day, function($a, $b) {
        list($serie_a, $episode_a) = $a;
        list($serie_b, $episode_b) = $b;
        $a_stamp = $episode_a->firstAired ? $episode_a->firstAired->getTimestamp() : 0;
        $b_stamp = $episode_b->firstAired ? $episode_b->firstAired->getTimestamp() : 0;
        return $a_stamp <=> $b_stamp;
      });
    }
    return $sorted_results;
  }

}
