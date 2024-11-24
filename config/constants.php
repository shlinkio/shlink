<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Shlinkio\Shlink\Core\Util\RedirectStatus;

const DEFAULT_DELETE_SHORT_URL_THRESHOLD = 15;
const DEFAULT_SHORT_CODES_LENGTH = 5;
const MIN_SHORT_CODES_LENGTH = 4;
const DEFAULT_REDIRECT_STATUS_CODE = RedirectStatus::STATUS_302;
const DEFAULT_REDIRECT_CACHE_LIFETIME = 30;
const LOCAL_LOCK_FACTORY = 'Shlinkio\Shlink\LocalLockFactory';
const LOOSE_URI_MATCHER = '/(.+)\:(.+)/i'; // Matches anything starting with a schema.
const DEFAULT_QR_CODE_SIZE = 300;
const DEFAULT_QR_CODE_MARGIN = 0;
const DEFAULT_QR_CODE_FORMAT = 'png';
const DEFAULT_QR_CODE_ERROR_CORRECTION = 'l';
const DEFAULT_QR_CODE_ROUND_BLOCK_SIZE = true;
const DEFAULT_QR_CODE_ENABLED_FOR_DISABLED_SHORT_URLS = true;
const DEFAULT_QR_CODE_COLOR = '#000000'; // Black
const DEFAULT_QR_CODE_BG_COLOR = '#ffffff'; // White
const IP_ADDRESS_REQUEST_ATTRIBUTE = 'remote_address';
const REDIRECT_URL_REQUEST_ATTRIBUTE = 'redirect_url';
