<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\IpGeolocation\GeoLite2;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Exception\RuntimeException;
use Shlinkio\Shlink\Common\IpGeolocation\GeoLite2\DbUpdater;
use Shlinkio\Shlink\Common\IpGeolocation\GeoLite2\GeoLite2Options;
use Symfony\Component\Filesystem\Exception as FilesystemException;
use Symfony\Component\Filesystem\Filesystem;
use Zend\Diactoros\Response;

class DbUpdaterTest extends TestCase
{
    /** @var DbUpdater */
    private $dbUpdater;
    /** @var ObjectProphecy */
    private $httpClient;
    /** @var ObjectProphecy */
    private $filesystem;
    /** @var GeoLite2Options */
    private $options;

    public function setUp(): void
    {
        $this->httpClient = $this->prophesize(ClientInterface::class);
        $this->filesystem = $this->prophesize(Filesystem::class);
        $this->options = new GeoLite2Options([
            'temp_dir' => __DIR__ . '/../../../test-resources',
            'db_location' => '',
            'download_from' => '',
        ]);

        $this->dbUpdater = new DbUpdater($this->httpClient->reveal(), $this->filesystem->reveal(), $this->options);
    }

    /**
     * @test
     */
    public function anExceptionIsThrownIfFreshDbCannotBeDownloaded()
    {
        $request = $this->httpClient->request(Argument::cetera())->willThrow(ClientException::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage(
            'An error occurred while trying to download a fresh copy of the GeoLite2 database'
        );
        $request->shouldBeCalledOnce();

        $this->dbUpdater->downloadFreshCopy();
    }

    /**
     * @test
     */
    public function anExceptionIsThrownIfFreshDbCannotBeExtracted()
    {
        $this->options->tempDir = '__invalid__';

        $request = $this->httpClient->request(Argument::cetera())->willReturn(new Response());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage(
            'An error occurred while trying to extract the GeoLite2 database from __invalid__/GeoLite2-City.tar.gz'
        );
        $request->shouldBeCalledOnce();

        $this->dbUpdater->downloadFreshCopy();
    }

    /**
     * @test
     * @dataProvider provideFilesystemExceptions
     */
    public function anExceptionIsThrownIfFreshDbCannotBeCopiedToDestination(string $e)
    {
        $request = $this->httpClient->request(Argument::cetera())->willReturn(new Response());
        $copy = $this->filesystem->copy(Argument::cetera())->willThrow($e);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('An error occurred while trying to copy GeoLite2 db file to destination');
        $request->shouldBeCalledOnce();
        $copy->shouldBeCalledOnce();

        $this->dbUpdater->downloadFreshCopy();
    }

    public function provideFilesystemExceptions(): array
    {
        return [
            [FilesystemException\FileNotFoundException::class],
            [FilesystemException\IOException::class],
        ];
    }

    /**
     * @test
     */
    public function noExceptionsAreThrownIfEverythingWorksFine()
    {
        $request = $this->httpClient->request(Argument::cetera())->willReturn(new Response());
        $copy = $this->filesystem->copy(Argument::cetera())->will(function () {
        });
        $remove = $this->filesystem->remove(Argument::cetera())->will(function () {
        });

        $this->dbUpdater->downloadFreshCopy();

        $request->shouldHaveBeenCalledOnce();
        $copy->shouldHaveBeenCalledOnce();
        $remove->shouldHaveBeenCalledOnce();
    }
}
