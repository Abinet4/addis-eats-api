# Toolchain verification (Day 52)

| Command | Expected | Output on the build machine |
|---|---|---|
| `php -v` | PHP 8.3.x | PHP 8.3.6 (cli) (NTS) |
| `composer -V` | Composer 2.7 or newer | Composer version 2.8.12 |
| `laravel -V` | Laravel Installer 5.x | not used: the project was scaffolded with `composer create-project laravel/laravel:^11.0`, which is equivalent |
| `php -m \| grep mbstring` | mbstring | mbstring |
| `php -m \| grep pdo` | PDO, pdo_sqlite | PDO, pdo_mysql, pdo_pgsql, pdo_sqlite |

Paste your own machine's output here before submitting; the course asks for the three version lines in this file or the README.
