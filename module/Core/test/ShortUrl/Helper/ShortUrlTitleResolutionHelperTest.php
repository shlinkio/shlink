<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Helper;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlTitleResolutionHelper;
use Shlinkio\Shlink\Core\Util\UrlValidatorInterface;

class ShortUrlTitleResolutionHelperTest extends TestCase
{
    use ProphecyTrait;

    private ShortUrlTitleResolutionHelper $helper;
    private ObjectProphecy $urlValidator;

    protected function setUp(): void
    {
        $this->urlValidator = $this->prophesize(UrlValidatorInterface::class);
        $this->helper = new ShortUrlTitleResolutionHelper($this->urlValidator->reveal());
    }

    /**
     * @test
     * @dataProvider provideTitles
     */
    public function urlIsProperlyShortened(?string $title, int $validateWithTitleCallsNum, int $validateCallsNum): void
    {
        $longUrl = 'http://foobar.com/12345/hello?foo=bar';
        $this->helper->processTitleAndValidateUrl(
            ShortUrlMeta::fromRawData(['longUrl' => $longUrl, 'title' => $title]),
        );

        $this->urlValidator->validateUrlWithTitle($longUrl, false)->shouldHaveBeenCalledTimes(
            $validateWithTitleCallsNum,
        );
        $this->urlValidator->validateUrl($longUrl, false)->shouldHaveBeenCalledTimes($validateCallsNum);
    }

    public function provideTitles(): iterable
    {
        yield 'no title' => [null, 1, 0];
        yield 'title' => ['link title', 0, 1];
    }
}
