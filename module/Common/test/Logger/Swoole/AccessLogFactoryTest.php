<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Logger\Swoole;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionObject;
use Shlinkio\Shlink\Common\Logger\Swoole\AccessLogFactory;
use Zend\Expressive\Swoole\Log\AccessLogFormatter;
use Zend\Expressive\Swoole\Log\AccessLogFormatterInterface;
use Zend\Expressive\Swoole\Log\Psr3AccessLogDecorator;
use Zend\Expressive\Swoole\Log\StdoutLogger;
use Zend\ServiceManager\ServiceManager;
use function is_string;

class AccessLogFactoryTest extends TestCase
{
    /** @var AccessLogFactory */
    private $factory;

    public function setUp()
    {
        $this->factory = new AccessLogFactory();
    }

    /**
     * @test
     */
    public function createsService()
    {
        $service = ($this->factory)(new ServiceManager(), '');
        $this->assertInstanceOf(Psr3AccessLogDecorator::class, $service);
    }

    /**
     * @test
     * @dataProvider provideLoggers
     * @param array $config
     * @param string|LoggerInterface $expectedLogger
     */
    public function wrapsProperLogger(array $config, $expectedLogger)
    {
        $service = ($this->factory)(new ServiceManager(['services' => $config]), '');

        $ref = new ReflectionObject($service);
        $loggerProp = $ref->getProperty('logger');
        $loggerProp->setAccessible(true);
        $logger = $loggerProp->getValue($service);

        if (is_string($expectedLogger)) {
            $this->assertInstanceOf($expectedLogger, $logger);
        } else {
            $this->assertSame($expectedLogger, $logger);
        }
    }

    public function provideLoggers(): iterable
    {
        yield 'without-any-logger' => [[], StdoutLogger::class];
        yield 'with-standard-logger' => (function () {
            $logger = new NullLogger();
            return [[LoggerInterface::class => $logger], $logger];
        })();
        yield 'with-custom-logger' => (function () {
            $logger = new NullLogger();
            return [[
                'config' => [
                    'zend-expressive-swoole' => [
                        'swoole-http-server' => [
                            'logger' => [
                                'logger_name' => 'my-logger',
                            ],
                        ],
                    ],
                ],
                'my-logger' => $logger,
            ], $logger];
        })();
    }

    /**
     * @test
     * @dataProvider provideFormatters
     * @param array $config
     * @param string|AccessLogFormatterInterface $expectedFormatter
     */
    public function wrappsProperFormatter(array $config, $expectedFormatter, string $expectedFormat)
    {
        $service = ($this->factory)(new ServiceManager(['services' => $config]), '');

        $ref = new ReflectionObject($service);
        $formatterProp = $ref->getProperty('formatter');
        $formatterProp->setAccessible(true);
        $formatter = $formatterProp->getValue($service);

        $ref = new ReflectionObject($formatter);
        $formatProp = $ref->getProperty('format');
        $formatProp->setAccessible(true);
        $format = $formatProp->getValue($formatter);

        if (is_string($expectedFormatter)) {
            $this->assertInstanceOf($expectedFormatter, $formatter);
        } else {
            $this->assertSame($expectedFormatter, $formatter);
        }
        $this->assertSame($expectedFormat, $format);
    }

    public function provideFormatters(): iterable
    {
        yield 'with-registered-formatter-and-default-format' => (function () {
            $formatter = new AccessLogFormatter();
            return [[AccessLogFormatterInterface::class => $formatter], $formatter, AccessLogFormatter::FORMAT_COMMON];
        })();
        yield 'with-registered-formatter-and-custom-format' => (function () {
            $formatter = new AccessLogFormatter(AccessLogFormatter::FORMAT_AGENT);
            return [[AccessLogFormatterInterface::class => $formatter], $formatter, AccessLogFormatter::FORMAT_AGENT];
        })();
        yield 'with-no-formatter-and-not-configured-format' => [
            [],
            AccessLogFormatter::class,
            AccessLogFormatter::FORMAT_COMMON,
        ];
        yield 'with-no-formatter-and-configured-format' => [[
            'config' => [
                'zend-expressive-swoole' => [
                    'swoole-http-server' => [
                        'logger' => [
                            'format' => AccessLogFormatter::FORMAT_COMBINED_DEBIAN,
                        ],
                    ],
                ],
            ],
        ], AccessLogFormatter::class, AccessLogFormatter::FORMAT_COMBINED_DEBIAN];
    }
}
