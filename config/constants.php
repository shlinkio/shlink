<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Shlinkio\Shlink\Core\Util\RedirectStatus;

const DEFAULT_DELETE_SHORT_URL_THRESHOLD = 15;
const DEFAULT_SHORT_CODES_LENGTH = 5;
const MIN_SHORT_CODES_LENGTH = 4;
const DEFAULT_REDIRECT_STATUS_CODE = RedirectStatus::STATUS_302;
const DEFAULT_REDIRECT_CACHE_LIFETIME = 30;
const DEFAULT_REDIRECT_CACHE_VISIBILITY = 'private';
const LOCAL_LOCK_FACTORY = 'Shlinkio\Shlink\LocalLockFactory';
const LOOSE_URI_MATCHER = '/(.+)\:(.+)/i'; // Matches anything starting with a schema.
const IP_ADDRESS_REQUEST_ATTRIBUTE = 'remote_address';
const REDIRECT_URL_REQUEST_ATTRIBUTE = 'redirect_url';

/**
 * List of ISO 3166-1 alpha-2 two-letter country codes https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
 */
const ISO_COUNTRY_CODES = [
    'AF', 'AX', 'AL', 'DZ', 'AS', 'AD', 'AO', 'AI', 'AQ', 'AG', 'AR', 'AM', 'AW', 'AU', 'AT', 'AZ',
    'BS', 'BH', 'BD', 'BB', 'BY', 'BE', 'BZ', 'BJ', 'BM', 'BT', 'BO', 'BQ', 'BA', 'BW', 'BV', 'BR',
    'IO', 'BN', 'BG', 'BF', 'BI', 'CV', 'KH', 'CM', 'CA', 'KY', 'CF', 'TD', 'CL', 'CN', 'CX', 'CC',
    'CO', 'KM', 'CG', 'CD', 'CK', 'CR', 'CI', 'HR', 'CU', 'CW', 'CY', 'CZ', 'DK', 'DJ', 'DM', 'DO',
    'EC', 'EG', 'SV', 'GQ', 'ER', 'EE', 'SZ', 'ET', 'FK', 'FO', 'FJ', 'FI', 'FR', 'GF', 'PF', 'TF',
    'GA', 'GM', 'GE', 'DE', 'GH', 'GI', 'GR', 'GL', 'GD', 'GP', 'GU', 'GT', 'GG', 'GN', 'GW', 'GY',
    'HT', 'HM', 'VA', 'HN', 'HK', 'HU', 'IS', 'IN', 'ID', 'IR', 'IQ', 'IE', 'IM', 'IL', 'IT', 'JM',
    'JP', 'JE', 'JO', 'KZ', 'KE', 'KI', 'KP', 'KR', 'KW', 'KG', 'LA', 'LV', 'LB', 'LS', 'LR', 'LY',
    'LI', 'LT', 'LU', 'MO', 'MG', 'MW', 'MY', 'MV', 'ML', 'MT', 'MH', 'MQ', 'MR', 'MU', 'YT', 'MX',
    'FM', 'MD', 'MC', 'MN', 'ME', 'MS', 'MA', 'MZ', 'MM', 'NA', 'NR', 'NP', 'NL', 'NC', 'NZ', 'NI',
    'NE', 'NG', 'NU', 'NF', 'MK', 'MP', 'NO', 'OM', 'PK', 'PW', 'PS', 'PA', 'PG', 'PY', 'PE', 'PH',
    'PN', 'PL', 'PT', 'PR', 'QA', 'RE', 'RO', 'RU', 'RW', 'BL', 'SH', 'KN', 'LC', 'MF', 'PM', 'VC',
    'WS', 'SM', 'ST', 'SA', 'SN', 'RS', 'SC', 'SL', 'SG', 'SX', 'SK', 'SI', 'SB', 'SO', 'ZA', 'GS',
    'SS', 'ES', 'LK', 'SD', 'SR', 'SJ', 'SE', 'CH', 'SY', 'TW', 'TJ', 'TZ', 'TH', 'TL', 'TG', 'TK',
    'TO', 'TT', 'TN', 'TR', 'TM', 'TC', 'TV', 'UG', 'UA', 'AE', 'GB', 'US', 'UM', 'UY', 'UZ', 'VU',
    'VE', 'VN', 'VG', 'VI', 'WF', 'EH', 'YE', 'ZM', 'ZW',
];

/** @deprecated */
const DEFAULT_QR_CODE_SIZE = 300;
/** @deprecated */
const DEFAULT_QR_CODE_MARGIN = 0;
/** @deprecated */
const DEFAULT_QR_CODE_FORMAT = 'png';
/** @deprecated */
const DEFAULT_QR_CODE_ERROR_CORRECTION = 'l';
/** @deprecated */
const DEFAULT_QR_CODE_ROUND_BLOCK_SIZE = true;
/** @deprecated */
const DEFAULT_QR_CODE_ENABLED_FOR_DISABLED_SHORT_URLS = true;
/** @deprecated */
const DEFAULT_QR_CODE_COLOR = '#000000'; // Black
/** @deprecated */
const DEFAULT_QR_CODE_BG_COLOR = '#ffffff'; // White
