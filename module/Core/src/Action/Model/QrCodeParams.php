<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action\Model;

use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelInterface;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelQuartile;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\WriterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

use function strtolower;
use function strtoupper;
use function trim;

final class QrCodeParams
{
    private const DEFAULT_SIZE = 300;
    private const MIN_SIZE = 50;
    private const MAX_SIZE = 1000;

    private function __construct(
        private int $size,
        private int $margin,
        private WriterInterface $writer,
        private ErrorCorrectionLevelInterface $errorCorrectionLevel
    ) {
    }

    public static function fromRequest(ServerRequestInterface $request): self
    {
        $query = $request->getQueryParams();

        return new self(
            self::resolveSize($request, $query),
            self::resolveMargin($query),
            self::resolveWriter($query),
            self::resolveErrorCorrection($query),
        );
    }

    private static function resolveSize(Request $request, array $query): int
    {
        // FIXME Size attribute is deprecated. After v3.0.0, always use the query param instead
        $size = (int) $request->getAttribute('size', $query['size'] ?? self::DEFAULT_SIZE);
        if ($size < self::MIN_SIZE) {
            return self::MIN_SIZE;
        }

        return $size > self::MAX_SIZE ? self::MAX_SIZE : $size;
    }

    private static function resolveMargin(array $query): int
    {
        $margin = $query['margin'] ?? null;
        if ($margin === null) {
            return 0;
        }

        $intMargin = (int) $margin;
        if ($margin !== (string) $intMargin) {
            return 0;
        }

        return $intMargin < 0 ? 0 : $intMargin;
    }

    private static function resolveWriter(array $query): WriterInterface
    {
        $format = strtolower(trim($query['format'] ?? 'png'));
        return match ($format) {
            'svg' => new SvgWriter(),
            default => new PngWriter(),
        };
    }

    private static function resolveErrorCorrection(array $query): ErrorCorrectionLevelInterface
    {
        $errorCorrectionLevel = strtoupper(trim($query['errorCorrection'] ?? ''));
        return match ($errorCorrectionLevel) {
            'H' => new ErrorCorrectionLevelHigh(),
            'Q' => new ErrorCorrectionLevelQuartile(),
            'M' => new ErrorCorrectionLevelMedium(),
            default => new ErrorCorrectionLevelLow(), // 'L'
        };
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
}
