<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink;

const API_TESTS_HOST = '127.0.0.1';
const API_TESTS_PORT = 9999;

const ANDROID_USER_AGENT = 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 (KHTML, like Gecko) '
    . 'Chrome/109.0.5414.86 Mobile Safari/537.36';
const IOS_USER_AGENT = 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_2 like Mac OS X) AppleWebKit/605.1.15 '
    . '(KHTML, like Gecko) FxiOS/109.0 Mobile/15E148 Safari/605.1.15';
const WINDOWS_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) '
    . 'Chrome/138.0.0.0 Safari/537.36 Edg/138.0.3351.95';
const LINUX_USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) '
    . 'HeadlessChrome/81.0.4044.113 Safari/537.36';
const MACOS_USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 15_5) AppleWebKit/605.1.15 (KHTML, like Gecko) '
    . 'Version/18.4 Safari/605.1.15';
const CHROMEOS_USER_AGENT = 'Mozilla/5.0 (X11; CrOS x86_64 16181.61.0) AppleWebKit/537.36 (KHTML, like Gecko) '
    . 'Chrome/134.0.6998.198 Safari/537.36';

const DYNAMIC_ENV_VARS_FILE = __DIR__ . '/../../config/test/dynamic_test_env.php';
