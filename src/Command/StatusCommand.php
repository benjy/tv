<?php declare(strict_types=1);

/**
 * @file
 * Contains \tv\Command\StatusCommand
 */

namespace tv\Command;

use Doctrine\Common\Cache\CacheProvider;
use Moinax\TvDb\Client;
use Moinax\TvDb\Episode;
use Moinax\TvDb\Serie;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use tv\Link\LinkProvider;

/**
 * The StatusCommand class.
 */
class StatusCommand extends Command {

  const CACHE_TIME = 86400;

  /**
   * The TvDb API key.
   */
  const TVDB_API_KEY = '5AA24E7B3E86E8DA';

  /**
   * The TvDb url.
   */
  const TVDB_URL = 'http://thetvdb.com';

  protected $tvdb;
  protected $cache;
  protected $linkProvider;
  protected $shows;

  public function __construct($name = NULL, Client $tvdb, CacheProvider $cache, LinkProvider $link, array $shows) {
    parent::__construct($name);
    $this->tvdb = $tvdb;
    $this->cache = $cache;
    $this->linkProvider = $link;
    $this->shows = $shows;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('status')
      ->setDescription('Check the status of all shows');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);
    $output->getFormatter()->setStyle('red', new OutputFormatterStyle('red', 'default'));
    $results = [];
    foreach ($this->shows as $name => $imdbid) {
      $serie = $this->getSerie($imdbid);
      $latest_episode = $this->getLatestEpisode($serie->id);

      // Calculate the dates for the next episode.
      $day = '0';
      $date_time = 'N/A';
      if ($latest_episode->firstAired) {
        // Add one day to allow for the US release times.
        $latest_episode->firstAired->add(new \DateInterval('P1D'));
        $day = $latest_episode->firstAired->format('N');
        $date_time = $latest_episode->firstAired->format('D - d/m/Y');

        $today = new \DateTimeImmutable();
        // If we're printing entries for today then we add some additional
        // formatting to make it easier to see.
        if ($latest_episode->firstAired->format('D') == $today->format('D')) {
          // If the episode comes out today, then make it green.
          if ($latest_episode->firstAired->format('d/m/y') === $today->format('d/m/y')) {
            $date_time = "<info>$date_time</info>";
          }
          elseif ($latest_episode->firstAired->getTimestamp() > $today->getTimestamp()) {
            // Episode in the future.
            $date_time = "<comment>$date_time</comment>";
          }
          else {
            // Episode in the past.
            $date_time = "<red>$date_time</red>";
          }
        }
      }

      // Store the results keyed by the $day so we can sort them by day of the
      // week.
      $results[$day][] = [
        $latest_episode->firstAired->getTimestamp(),
        $serie->name,
        $latest_episode->name,
        sprintf('S%02d E%02d', $latest_episode->season, $latest_episode->number),
        $date_time,
        $this->linkProvider->getLink($serie, $latest_episode),
      ];
    }

    // Sort the results by day of the week.
    $rows = [];
    ksort($results);

    foreach ($results as $day => $episodes) {

      // Sort the episodes as well.
      uasort($episodes, function($a, $b) {
        return $a[0] <=> $b[0];
      });

      foreach ($episodes as $episode) {
        unset($episode[0]);
        $rows[] = $episode;
      }
    }

    // Output the table.
    $header = ['Show', 'Episode Title', 'Season/Episode', 'Date', 'Link'];
    $io->table($header, $rows);
  }

  protected function getSerie(string $id) : Serie {
    if ($serie = $this->cache->fetch($id)) {
      return $serie;
    }

    $serie = $this->tvdb->getSerieByRemoteId(['imdbid' => $id]);
    $this->cache->save($id, $serie);
    return $serie;
  }

  protected function getEpisodes(int $serie_id) : array {
    if ($episodes = $this->cache->fetch("$serie_id:episodes")) {
      return $episodes;
    }
    $episodes = $this->tvdb->getSerieEpisodes($serie_id);
    $this->cache->save("$serie_id:episodes", $episodes['episodes'], static::CACHE_TIME);
    return $episodes['episodes'];
  }

  protected function getLatestEpisode(int $serie_id) : Episode {
    $episodes = $this->getEpisodes($serie_id);
    $episodes = array_values($episodes);

    // Get the latest episode which was aired after today.
    $today = strtotime('-2 days');
    $i = 0;
    do {
      $current_episode = $episodes[$i];

      // If we don't have a date, doesn't mean it's the latest, just keep
      // looking.
      if($current_episode->firstAired) {
        // Great, we've found one.
        if ($current_episode->firstAired->getTimestamp() > $today) {
          break;
        }
      }
    } while (isset($episodes[++$i]));

    return $current_episode;
  }

}
