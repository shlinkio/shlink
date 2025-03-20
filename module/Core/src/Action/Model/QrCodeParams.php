<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action\Model;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Color\ColorInterface;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\WriterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Config\Options\QrCodeOptions;

use function ctype_xdigit;
use function hexdec;
use function ltrim;
use function max;
use function min;
use function Shlinkio\Shlink\Core\ArrayUtils\contains;
use function strlen;
use function strtolower;
use function substr;
use function trim;

use const Shlinkio\Shlink\DEFAULT_QR_CODE_BG_COLOR;
use const Shlinkio\Shlink\DEFAULT_QR_CODE_COLOR;

final readonly class QrCodeParams
{
    private const int MIN_SIZE = 50;
    private const int MAX_SIZE = 1000;
    private const array SUPPORTED_FORMATS = ['png', 'svg'];

    private function __construct(
        public int $size,
        public int $margin,
        public WriterInterface $writer,
        public array $writerOptions,
        public ErrorCorrectionLevel $errorCorrectionLevel,
        public RoundBlockSizeMode $roundBlockSizeMode,
        public ColorInterface $color,
        public ColorInterface $bgColor,
        public bool $disableLogo,
    ) {
    }

    public static function fromRequest(ServerRequestInterface $request, QrCodeOptions $defaults): self
    {
        $query = $request->getQueryParams();
        [$writer, $writerOptions] = self::resolveWriterAndWriterOptions($query, $defaults);

        return new self(
            size: self::resolveSize($query, $defaults),
            margin: self::resolveMargin($query, $defaults),
            writer: $writer,
            writerOptions: $writerOptions,
            errorCorrectionLevel: self::resolveErrorCorrection($query, $defaults),
            roundBlockSizeMode: self::resolveRoundBlockSize($query, $defaults),
            color: self::resolveColor($query, $defaults),
            bgColor: self::resolveBackgroundColor($query, $defaults),
            disableLogo: isset($query['logo']) && $query['logo'] === 'disable',
        );
    }

    private static function resolveSize(array $query, QrCodeOptions $defaults): int
    {
        $size = (int) ($query['size'] ?? $defaults->size);
        if ($size < self::MIN_SIZE) {
            return self::MIN_SIZE;
        }

        return min($size, self::MAX_SIZE);
    }

    private static function resolveMargin(array $query, QrCodeOptions $defaults): int
    {
        $margin = $query['margin'] ?? (string) $defaults->margin;
        $intMargin = (int) $margin;
        if ($margin !== (string) $intMargin) {
            return 0;
        }

        return max($intMargin, 0);
    }

    /**
     * @return array{WriterInterface, array}
     */
    private static function resolveWriterAndWriterOptions(array $query, QrCodeOptions $defaults): array
    {
        $qFormat = self::normalizeParam($query['format'] ?? '');
        $format = contains($qFormat, self::SUPPORTED_FORMATS) ? $qFormat : self::normalizeParam($defaults->format);

        return match ($format) {
            'svg' => [new SvgWriter(), []],
            default => [new PngWriter(), [PngWriter::WRITER_OPTION_NUMBER_OF_COLORS => null]],
        };
    }

    private static function resolveErrorCorrection(array $query, QrCodeOptions $defaults): ErrorCorrectionLevel
    {
        $errorCorrectionLevel = self::normalizeParam($query['errorCorrection'] ?? $defaults->errorCorrection);
        return match ($errorCorrectionLevel) {
            'h' => ErrorCorrectionLevel::High,
            'q' => ErrorCorrectionLevel::Quartile,
            'm' => ErrorCorrectionLevel::Medium,
            default => ErrorCorrectionLevel::Low, // 'l'
        };
    }

    private static function resolveRoundBlockSize(array $query, QrCodeOptions $defaults): RoundBlockSizeMode
    {
        $doNotRoundBlockSize = isset($query['roundBlockSize'])
            ? $query['roundBlockSize'] === 'false'
            : ! $defaults->roundBlockSize;
        return $doNotRoundBlockSize ? RoundBlockSizeMode::None : RoundBlockSizeMode::Margin;
    }

    private static function resolveColor(array $query, QrCodeOptions $defaults): ColorInterface
    {
        $color = self::normalizeParam($query['color'] ?? $defaults->color);
        return self::parseHexColor($color, DEFAULT_QR_CODE_COLOR);
    }

    private static function resolveBackgroundColor(array $query, QrCodeOptions $defaults): ColorInterface
    {
        $bgColor = self::normalizeParam($query['bgColor'] ?? $defaults->bgColor);
        return self::parseHexColor($bgColor, DEFAULT_QR_CODE_BG_COLOR);
    }

    private static function parseHexColor(string $hexColor, string|null $fallback): Color
    {
        $hexColor = ltrim($hexColor, '#');
        if (! ctype_xdigit($hexColor) && $fallback !== null) {
            return self::parseHexColor($fallback, null);
        }

        if (strlen($hexColor) === 3) {
            return new Color(
                (int) hexdec(substr($hexColor, 0, 1) . substr($hexColor, 0, 1)),
                (int) hexdec(substr($hexColor, 1, 1) . substr($hexColor, 1, 1)),
                (int) hexdec(substr($hexColor, 2, 1) . substr($hexColor, 2, 1)),
            );
        }

        return new Color(
            (int) hexdec(substr($hexColor, 0, 2)),
            (int) hexdec(substr($hexColor, 2, 2)),
            (int) hexdec(substr($hexColor, 4, 2)),
        );
    }

    private static function normalizeParam(string $param): string
    {
        return strtolower(trim($param));
    }
}
