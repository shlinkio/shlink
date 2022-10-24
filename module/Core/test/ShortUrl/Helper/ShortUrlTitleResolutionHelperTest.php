<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Helper;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlTitleResolutionHelper;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\Util\UrlValidatorInterface;

class ShortUrlTitleResolutionHelperTest extends TestCase
{
    private ShortUrlTitleResolutionHelper $helper;
    private MockObject & UrlValidatorInterface $urlValidator;

    protected function setUp(): void
    {
        $this->urlValidator = $this->createMock(UrlValidatorInterface::class);
        $this->helper = new ShortUrlTitleResolutionHelper($this->urlValidator);
    }

    /**
     * @test
     * @dataProvider provideTitles
     */
    public function urlIsProperlyShortened(?string $title, int $validateWithTitleCallsNum, int $validateCallsNum): void
    {
        $longUrl = 'http://foobar.com/12345/hello?foo=bar';
        $this->urlValidator->expects($this->exactly($validateWithTitleCallsNum))->method('validateUrlWithTitle')->with(
            $longUrl,
            $this->isFalse(),
        );
        $this->urlValidator->expects($this->exactly($validateCallsNum))->method('validateUrl')->with(
            $longUrl,
            $this->isFalse(),
        );

        $this->helper->processTitleAndValidateUrl(
            ShortUrlCreation::fromRawData(['longUrl' => $longUrl, 'title' => $title]),
        );
    }

    public function provideTitles(): iterable
    {
        yield 'no title' => [null, 1, 0];
        yield 'title' => ['link title', 0, 1];
    }
}
