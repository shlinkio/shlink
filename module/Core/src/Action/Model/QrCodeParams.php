<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action\Model;

use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelInterface;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelQuartile;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeInterface;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeNone;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\WriterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Options\QrCodeOptions;

use function Functional\contains;
use function strtolower;
use function trim;

final class QrCodeParams
{
    private const MIN_SIZE = 50;
    private const MAX_SIZE = 1000;
    private const SUPPORTED_FORMATS = ['png', 'svg'];

    private function __construct(
        private int $size,
        private int $margin,
        private WriterInterface $writer,
        private ErrorCorrectionLevelInterface $errorCorrectionLevel,
        private RoundBlockSizeModeInterface $roundBlockSizeMode,
    ) {
    }

    public static function fromRequest(ServerRequestInterface $request, QrCodeOptions $defaults): self
    {
        $query = $request->getQueryParams();

        return new self(
            self::resolveSize($query, $defaults),
            self::resolveMargin($query, $defaults),
            self::resolveWriter($query, $defaults),
            self::resolveErrorCorrection($query, $defaults),
            self::resolveRoundBlockSize($query, $defaults),
        );
    }

    private static function resolveSize(array $query, QrCodeOptions $defaults): int
    {
        $size = (int) ($query['size'] ?? $defaults->size());
        if ($size < self::MIN_SIZE) {
            return self::MIN_SIZE;
        }

        return $size > self::MAX_SIZE ? self::MAX_SIZE : $size;
    }

    private static function resolveMargin(array $query, QrCodeOptions $defaults): int
    {
        $margin = $query['margin'] ?? (string) $defaults->margin();
        $intMargin = (int) $margin;
        if ($margin !== (string) $intMargin) {
            return 0;
        }

        return $intMargin < 0 ? 0 : $intMargin;
    }

    private static function resolveWriter(array $query, QrCodeOptions $defaults): WriterInterface
    {
        $qFormat = self::normalizeParam($query['format'] ?? '');
        $format = contains(self::SUPPORTED_FORMATS, $qFormat) ? $qFormat : self::normalizeParam($defaults->format());

        return match ($format) {
            'svg' => new SvgWriter(),
            default => new PngWriter(),
        };
    }

    private static function resolveErrorCorrection(array $query, QrCodeOptions $defaults): ErrorCorrectionLevelInterface
    {
        $errorCorrectionLevel = self::normalizeParam($query['errorCorrection'] ?? $defaults->errorCorrection());
        return match ($errorCorrectionLevel) {
            'h' => new ErrorCorrectionLevelHigh(),
            'q' => new ErrorCorrectionLevelQuartile(),
            'm' => new ErrorCorrectionLevelMedium(),
            default => new ErrorCorrectionLevelLow(), // 'l'
        };
    }

    private static function resolveRoundBlockSize(array $query, QrCodeOptions $defaults): RoundBlockSizeModeInterface
    {
        $doNotRoundBlockSize = isset($query['roundBlockSize'])
            ? $query['roundBlockSize'] === 'false'
            : ! $defaults->roundBlockSize();
        return $doNotRoundBlockSize ? new RoundBlockSizeModeNone() : new RoundBlockSizeModeMargin();
    }

    private static function normalizeParam(string $param): string
    {
        return strtolower(trim($param));
    }

    public function size(): int
    {
        return $this->size;
    }

    public function margin(): int
    {
        return $this->margin;
    }

    public function writer(): WriterInterface
    {
        return $this->writer;
    }

    public function errorCorrectionLevel(): ErrorCorrectionLevelInterface
    {
        return $this->errorCorrectionLevel;
    }

    public function roundBlockSizeMode(): RoundBlockSizeModeInterface
    {
        return $this->roundBlockSizeMode;
    }
}
