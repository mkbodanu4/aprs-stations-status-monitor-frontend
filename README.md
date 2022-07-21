# Simple iGate Status Monitor

(Very) simple application to collect iGate/Digipeaters status on APRS network.

Inspired by IZ7BOJ's [APRS-dashboard](https://github.com/IZ7BOJ/APRS_dashboard)

Demo: [at MicroApp](https://apps.manko.pro/igate-status/)

Feel free to use this application alone or with my [WordPress Plugin](https://github.com/mkbodanu4/simple-igate-status-plugin).

## Installation

1. Upload code to your server or hosting, that supports PHP7 or PHP8.
2. Rename .env_example to .env
3. Change all configurations in .env to your own.
4. Set full access permission (777) for file .lock
5. Add a task to crontab to run cron.php periodically
6. Install WordPress Plugin to your WordPress site and configure this application API URL.
7. Done!

## Data source

This application relies on [aprs.fi](https://aprs.fi) API. Please read carefully their [API doc](https://aprs.fi/page/api) before usage.

## License

GPL v3

