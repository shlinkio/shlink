<?php
namespace ShlinkioTest\Shlink\CLI\Command;

use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Shortcode\GenerateShortcodeCommand;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Zend\I18n\Translator\Translator;

class GenerateShortcodeCommandTest extends TestCase
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
        $command = new GenerateShortcodeCommand($this->urlShortener->reveal(), Translator::factory([]), [
            'schema' => 'http',
            'hostname' => 'foo.com'
        ]);
        $app = new Application();
        $app->add($command);
        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function properShortCodeIsCreatedIfLongUrlIsCorrect()
    {
        $this->urlShortener->urlToShortCode(Argument::any())->willReturn('abc123')
                                                            ->shouldBeCalledTimes(1);

        $this->commandTester->execute([
            'command' => 'shortcode:generate',
            'longUrl' => 'http://domain.com/foo/bar'
        ]);
        $output = $this->commandTester->getDisplay();
        $this->assertTrue(strpos($output, 'http://foo.com/abc123') > 0);
    }

    /**
     * @test
     */
    public function exceptionWhileParsingLongUrlOutputsError()
    {
        $this->urlShortener->urlToShortCode(Argument::any())->willThrow(new InvalidUrlException())
                                                            ->shouldBeCalledTimes(1);

        $this->commandTester->execute([
            'command' => 'shortcode:generate',
            'longUrl' => 'http://domain.com/invalid'
        ]);
        $output = $this->commandTester->getDisplay();
        $this->assertTrue(
            strpos($output, 'Provided URL "http://domain.com/invalid" is invalid. Try with a different one.') === 0
        );
    }
}
