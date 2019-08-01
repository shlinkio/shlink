<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\IpGeolocation\GeoLite2;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use PharData;
use Shlinkio\Shlink\Common\Exception\RuntimeException;
use Symfony\Component\Filesystem\Exception as FilesystemException;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

use function sprintf;

class DbUpdater implements DbUpdaterInterface
{
    private const DB_COMPRESSED_FILE = 'GeoLite2-City.tar.gz';
    private const DB_DECOMPRESSED_FILE = 'GeoLite2-City.mmdb';

    /** @var ClientInterface */
    private $httpClient;
    /** @var Filesystem */
    private $filesystem;
    /** @var GeoLite2Options */
    private $options;

    public function __construct(ClientInterface $httpClient, Filesystem $filesystem, GeoLite2Options $options)
    {
        $this->httpClient = $httpClient;
        $this->filesystem = $filesystem;
        $this->options = $options;
    }

    /**
     * @throws RuntimeException
     */
    public function downloadFreshCopy(?callable $handleProgress = null): void
    {
        $tempDir = $this->options->getTempDir();
        $compressedFile = sprintf('%s/%s', $tempDir, self::DB_COMPRESSED_FILE);

        $this->downloadDbFile($compressedFile, $handleProgress);
        $tempFullPath = $this->extractDbFile($compressedFile, $tempDir);
        $this->copyNewDbFile($tempFullPath);
        $this->deleteTempFiles([$compressedFile, $tempFullPath]);
    }

    private function downloadDbFile(string $dest, ?callable $handleProgress = null): void
    {
        try {
            $this->httpClient->request(RequestMethod::METHOD_GET, $this->options->getDownloadFrom(), [
                RequestOptions::SINK => $dest,
                RequestOptions::PROGRESS => $handleProgress,
            ]);
        } catch (Throwable | GuzzleException $e) {
            throw new RuntimeException(
                'An error occurred while trying to download a fresh copy of the GeoLite2 database',
                0,
                $e
            );
        }
    }

    private function extractDbFile(string $compressedFile, string $tempDir): string
    {
        try {
            $phar = new PharData($compressedFile);
            $internalPathToDb = sprintf('%s/%s', $phar->getBasename(), self::DB_DECOMPRESSED_FILE);
            $phar->extractTo($tempDir, $internalPathToDb, true);

            return sprintf('%s/%s', $tempDir, $internalPathToDb);
        } catch (Throwable $e) {
            throw new RuntimeException(
                sprintf('An error occurred while trying to extract the GeoLite2 database from %s', $compressedFile),
                0,
                $e
            );
        }
    }

    private function copyNewDbFile(string $from): void
    {
        try {
            $this->filesystem->copy($from, $this->options->getDbLocation(), true);
        } catch (FilesystemException\FileNotFoundException | FilesystemException\IOException $e) {
            throw new RuntimeException('An error occurred while trying to copy GeoLite2 db file to destination', 0, $e);
        }
    }

    private function deleteTempFiles(array $files): void
    {
        try {
            $this->filesystem->remove($files);
        } catch (FilesystemException\IOException $e) {
            // Ignore any error produced when trying to delete temp files
        }
    }

    public function databaseFileExists(): bool
    {
        return $this->filesystem->exists($this->options->getDbLocation());
    }
}
