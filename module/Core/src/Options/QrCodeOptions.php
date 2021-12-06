<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use Laminas\Stdlib\AbstractOptions;

use const Shlinkio\Shlink\DEFAULT_QR_CODE_ERROR_CORRECTION;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_FORMAT;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_MARGIN;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_ROUND_BLOCK_SIZE;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_SIZE;

class QrCodeOptions extends AbstractOptions
{
    private int $size = DEFAULT_QR_CODE_SIZE;
    private int $margin = DEFAULT_QR_CODE_MARGIN;
    private string $format = DEFAULT_QR_CODE_FORMAT;
    private string $errorCorrection = DEFAULT_QR_CODE_ERROR_CORRECTION;
    private bool $roundBlockSize = DEFAULT_QR_CODE_ROUND_BLOCK_SIZE;

    public function size(): int
    {
        return $this->size;
    }

    protected function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function margin(): int
    {
        return $this->margin;
    }

    protected function setMargin(int $margin): void
    {
        $this->margin = $margin;
    }

    public function format(): string
    {
        return $this->format;
    }

    protected function setFormat(string $format): void
    {
        $this->format = $format;
    }

    public function errorCorrection(): string
    {
        return $this->errorCorrection;
    }

    protected function setErrorCorrection(string $errorCorrection): void
    {
        $this->errorCorrection = $errorCorrection;
    }

    public function roundBlockSize(): bool
    {
        return $this->roundBlockSize;
    }

    protected function setRoundBlockSize(bool $roundBlockSize): void
    {
        $this->roundBlockSize = $roundBlockSize;
    }
}
