<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Geolocation;

interface GeolocationDownloadProgressHandlerInterface
{
    /**
     * Invoked right before starting to download a geolocation DB file, and only if it needs to be downloaded.
     * @param $olderDbExists - Indicates if an older DB file already exists when this method is called
     */
    public function beforeDownload(bool $olderDbExists): void;

    /**
     * Invoked every time a new chunk of the new DB file is downloaded, with the total size of the file and how much has
     * already been downloaded.
     * @param $olderDbExists - Indicates if an older DB file already exists when this method is called
     */
    public function handleProgress(int $total, int $downloaded, bool $olderDbExists): void;
}
