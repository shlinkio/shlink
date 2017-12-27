<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Util;

class DateRange
{
    /**
     * @var \DateTimeInterface|null
     */
    private $startDate;
    /**
     * @var \DateTimeInterface|null
     */
    private $endDate;

    public function __construct(\DateTimeInterface $startDate = null, \DateTimeInterface $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->startDate === null && $this->endDate === null;
    }
}
