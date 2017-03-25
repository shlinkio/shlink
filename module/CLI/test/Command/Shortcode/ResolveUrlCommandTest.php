<?php
namespace ShlinkioTest\Shlink\CLI\Command\Shortcode;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Shortcode\ResolveUrlCommand;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Zend\I18n\Translator\Translator;

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
        $this->urlShortener->shortCodeToUrl($shortCode)->willReturn($expectedUrl)
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
        $this->urlShortener->shortCodeToUrl($shortCode)->willReturn(null)
                                                       ->shouldBeCalledTimes(1);

        $this->commandTester->execute([
            'command' => 'shortcode:parse',
            'shortCode' => $shortCode,
        ]);
        $output = $this->commandTester->getDisplay();
        $this->assertEquals('No URL found for short code "' . $shortCode . '"' . PHP_EOL, $output);
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
        $this->assertEquals('Provided short code "' . $shortCode . '" has an invalid format.' . PHP_EOL, $output);
    }
}
