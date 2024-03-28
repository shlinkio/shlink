<?php

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

enum OrderableField: string
{
    case LONG_URL = 'longUrl';
    case SHORT_CODE = 'shortCode';
    case DATE_CREATED = 'dateCreated';
    case TITLE = 'title';
    case VISITS = 'visits';
    case NON_BOT_VISITS = 'nonBotVisits';
}
