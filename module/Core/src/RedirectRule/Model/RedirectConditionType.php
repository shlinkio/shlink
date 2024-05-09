<?php

namespace Shlinkio\Shlink\Core\RedirectRule\Model;

enum RedirectConditionType: string
{
    case DEVICE = 'device';
    case LANGUAGE = 'language';
    case QUERY_PARAM = 'query-param';
    case IP = 'ip';
}
