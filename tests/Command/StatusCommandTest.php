<?php

/**
 * @file
 * Contains \tv\Tests\Command\StatusCommandTest.
 */

namespace tv\Tests\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use tv\Command\StatusCommand;

/**
 * @coversDefaultClass \tv\Command\StatusCommand
 */
class StatusCommandTest extends \PHPUnit_Framework_TestCase {

  public function testFormattedOutput() {
    $today = new \DateTimeImmutable();
    // Show 1 has an upcoming episode.
    for ($i = 1; $i <= 3; $i++) {
      $serie = $this->getMockBuilder('Moinax\TvDb\Serie')
        ->disableOriginalConstructor()
        ->getMock();
      $serie->id = 1;
      $serie->name = "Serie $i";
      $series[] = $serie;
    }

    $show_1 = [
      $this->getEpisode('S1E1', $today->modify("-7 days")),
      $this->getEpisode('S1E2', $today->modify('+7 days')),
    ];
    // Show 2 only has episodes in the past.
    $show_2 = [
      $this->getEpisode('S2E1', $today->modify("-21 days")),
      $this->getEpisode('S2E2', $today->modify('-14 days')),
    ];
    // Show 3 has an episode coming out today.
    $show_3 = [
      $this->getEpisode('S3E1', $today->modify("-7 days")),
      $this->getEpisode('S3E2', $today),
    ];
    $episodes = [['episodes' => $show_1], ['episodes' => $show_2], ['episodes' => $show_3]];

    $shows = [
      'show1' => 'imdb1',
      'show2' => 'imdb2',
      'show3' => 'imdb3',
    ];

    $application = new Application();
    $application->add($this->getCommand($series, $episodes, $shows));

    $command = $application->find('status');
    $commandTester = new CommandTester($command);
    $commandTester->execute(['command' => $command->getName()], [
      'decorated' => TRUE,
    ]);

//    $this->assertEquals('', $commandTester->getDisplay());

//    $this->assertTrue(stripos($commandTester->getDisplay(), 'red') !== FALSE);
    $a = $commandTester->getDisplay();
    $b = $commandTester->getOutput();
  }

  protected function getEpisode($name, \DateTimeImmutable $date) {
    $mock = $this->getMockBuilder('Moinax\TvDb\Episode')
      ->disableOriginalConstructor()
      ->getMock();
    $mock->firstAired = $date;
    $mock->name = $name;
    return $mock;
  }

  protected function getCommand($series, $episodes, $shows) {
    $client = $this->getMockBuilder('Moinax\TvDb\Client')
      ->disableOriginalConstructor()
      ->getMock();
    $client
      ->expects($this->any())
      ->method('getSerieByRemoteId')
      ->willReturnOnConsecutiveCalls(...$series);
    $client
      ->expects($this->any())
      ->method('getSerieEpisodes')
      ->willReturnOnConsecutiveCalls(...$episodes);

    $cache = $this->getMockBuilder('Doctrine\Common\Cache\FilesystemCache')
      ->disableOriginalConstructor()
      ->getMock();
    $cache
      ->expects($this->any())
      ->method('fetch')
      ->willReturn(FALSE);
    $link = $this->getMock('tv\Link\LinkProvider');
    $link
      ->expects($this->any())
      ->method('getLink')
      ->willReturn('test link');

    return new StatusCommand(NULL, $client, $cache, $link, $shows);
  }

  protected function generateRandomDate($is_future, $day_of_week) {
    // Generate a random timestamp.
    if ($is_future) {
      $time = rand(time() + 86400, getrandmax());
    }
    else {
      $time = rand(0, time() - 86400);
    }
    $date = new \DateTimeImmutable($time);
    if ($day_of_week) {
      $days_diff = 7 - $day_of_week;
      $time = $date->getTimestamp() - ($days_diff * 86400);
      return new \DateTimeImmutable($time);
    }
    return $date;
  }

  /**
   * @covers ::getLatestEpisode
   */
  public function testGetLatestEpisode() {
    $this->assertTrue(TRUE);
  }

  public function latestEpisodeProvider() {
    return [
      'latest episode is today' => ['ep1', 'ep2'],
      'latest episode is tomorrow' => ['ep1', 'ep2'],
      'latest episode is in the past' => ['ep1', 'ep2'],
      'latest episode is in future' => ['ep1', 'ep2'],
    ];
  }
}
