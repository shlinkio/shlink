### Installation steps

- Define ENV vars in apache or nginx:
    - SHORTENED_URL_SCHEMA: http|https
    - SHORTENED_URL_HOSTNAME: Short domain
    - SHORTCODE_CHARS: The char set used to generate short codes (defaults to **123456789bcdfghjkmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ**, but a new one can be generated with the `config:generate-charset` command)
    - DB_USER: MySQL database user
    - DB_PASSWORD: MySQL database password
    - REST_USER: Username for REST authentication
    - REST_PASSWORD: Password for REST authentication
    - DB_NAME: MySQL database name (defaults to **shlink**)
    - DEFAULT_LOCALE: Language in which web requests (browser and REST) will be returned if no `Accept-Language` header is sent (defaults to **en**)
    - CLI_LOCALE: Language in which console command messages will be displayed (defaults to **en**)
- Create database (`vendor/bin/doctrine orm:schema-tool:create`)
- Add write permissions to `data` directory
- Create doctrine proxies (`vendor/bin/doctrine orm:generate-proxies`)
- Create symlink to bin/cli as `shlink` in /usr/local/bin (linux only. Optional)

Supported languages: es and en
