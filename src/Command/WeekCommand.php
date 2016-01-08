<?php declare(strict_types=1);

/**
 * @file
 * Contains \tv\Command\StatusCommand
 */

namespace tv\Command;

use Moinax\TvDb\Episode;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * The Week class.
 */
class WeekCommand extends CommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('week')
      ->setDescription('Check the status of all shows')
      ->addOption('range', 'r', InputOption::VALUE_OPTIONAL, 'The range of episodes to show, e.g. week, day, month.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $io = new SymfonyStyle($input, $output);
    $io->getFormatter()->setStyle('red', new OutputFormatterStyle('red', 'default'));

    $start = new \DateTimeImmutable('last sunday');
    $end = new \DateTimeImmutable('this sunday');
    $io->title(sprintf('Showing episodes from %s to %s', $start->format('d/m/Y'), $end->format('d/m/Y')));
    $sorted_results = $this->getEpisodesForWeek($start, $end);

    $this->displayAsTable($io, $sorted_results);
  }

  /**
   * Get all the episodes for the current week.
   */
  protected function getEpisodesForWeek(\DateTimeImmutable $start, \DateTimeImmutable $end) {
    $sorted_results = [];
    foreach ($this->getAllSeriesEpisodes() as $serie_imdb => $info) {
      /** @var \Moinax\TvDb\Episode $episode */
      foreach (array_reverse($info['episodes']) as $episode) {
        // If we don't have a timestamp we don't know what to do.
        if (!$episode->firstAired) {
          continue;
        }

        // We're going backwards so if we have a timestamp that is before the
        // start of our week then we don't have an episode.
        if ($episode->firstAired->getTimestamp() < $start->getTimestamp()) {
          break;
        }

        // If the episode falls within our week then we're good.
        $episode_timestamp = $episode->firstAired->getTimestamp();
        if ($episode_timestamp > $start->getTimestamp() && $episode_timestamp < $end->getTimestamp()) {
          $day = $episode->firstAired->add(new \DateInterval('P1D'))->format('N');
          $sorted_results[$day][] = [$info['serie'], $episode];
        }
      }
    }
    ksort($sorted_results);

    return $sorted_results;
  }

  protected function getFormattedDateTime(Episode $episode) {
    $date_time = 'N/A';
    if ($episode->firstAired) {
      $date_time = $episode->firstAired->format('D - d/m/Y');
      $today = new \DateTimeImmutable();
      if ($episode->firstAired->format('d/m/y') === $today->format('d/m/y')) {
        $date_time = '<info>' . $date_time . '</info>';
      }
    }
    return $date_time;
  }

}
