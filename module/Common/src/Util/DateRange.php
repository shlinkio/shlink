<?php
namespace Shlinkio\Shlink\Common\Util;

class DateRange
{
    /**
     * @var \DateTimeInterface
     */
    private $startDate;
    /**
     * @var \DateTimeInterface
     */
    private $endDate;

    public function __construct(\DateTimeInterface $startDate = null, \DateTimeInterface $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return is_null($this->startDate) && is_null($this->endDate);
    }
}
