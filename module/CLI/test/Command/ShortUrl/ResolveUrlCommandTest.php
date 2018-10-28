<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\ShortUrl\ResolveUrlCommand;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Zend\I18n\Translator\Translator;
use const PHP_EOL;

class ResolveUrlCommandTest extends TestCase
{
    /**
     * @var CommandTester
     */
    protected $commandTester;
    /**
     * @var ObjectProphecy
     */
    protected $urlShortener;

    public function setUp()
    {
        $this->urlShortener = $this->prophesize(UrlShortener::class);
        $command = new ResolveUrlCommand($this->urlShortener->reveal(), Translator::factory([]));
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function correctShortCodeResolvesUrl()
    {
        $shortCode = 'abc123';
        $expectedUrl = 'http://domain.com/foo/bar';
        $shortUrl = (new ShortUrl())->setLongUrl($expectedUrl);
        $this->urlShortener->shortCodeToUrl($shortCode)->willReturn($shortUrl)
                                                       ->shouldBeCalledTimes(1);

        $this->commandTester->execute([
            'command' => 'shortcode:parse',
            'shortCode' => $shortCode,
        ]);
        $output = $this->commandTester->getDisplay();
        $this->assertEquals('Long URL: ' . $expectedUrl . PHP_EOL, $output);
    }

    /**
     * @test
     */
    public function incorrectShortCodeOutputsErrorMessage()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willThrow(EntityDoesNotExistException::class)
                                                       ->shouldBeCalledTimes(1);

        $this->commandTester->execute([
            'command' => 'shortcode:parse',
            'shortCode' => $shortCode,
        ]);
        $output = $this->commandTester->getDisplay();
        $this->assertContains('Provided short code "' . $shortCode . '" could not be found.', $output);
    }

    /**
     * @test
     */
    public function wrongShortCodeFormatOutputsErrorMessage()
    {
        $shortCode = 'abc123';
        $this->urlShortener->shortCodeToUrl($shortCode)->willThrow(new InvalidShortCodeException())
                                                       ->shouldBeCalledTimes(1);

        $this->commandTester->execute([
            'command' => 'shortcode:parse',
            'shortCode' => $shortCode,
        ]);
        $output = $this->commandTester->getDisplay();
        $this->assertContains('Provided short code "' . $shortCode . '" has an invalid format.', $output);
    }
}
