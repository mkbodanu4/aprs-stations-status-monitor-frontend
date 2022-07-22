# Simple IGate Status Monitor

(Very) simple PHP application to monitor IGate/Digipeaters status on APRS network.
Requires [Python IGate Status Monitor](https://github.com/mkbodanu4/python-igate-status-monitor) for APRS-IS packet
processing. Feel free to use this application alone or with
my [WordPress Plugin](https://github.com/mkbodanu4/simple-igate-status-plugin).

Inspired by IZ7BOJ's [APRS-dashboard](https://github.com/IZ7BOJ/APRS_dashboard)

Demo: [at MicroApp](https://apps.manko.pro/igate-status/)

## Installation

1. Upload code to your server or hosting, that supports PHP7 or higher.
2. Create MySQL database and import *database.sql* file.
3. Rename *.env_example* to *.env*
4. Change all configurations in *.env* file to your own.
5. Install [Python IGate Status Monitor](https://github.com/mkbodanu4/python-igate-status-monitor)
6. Install WordPress Plugin to your WordPress site and configure this application API URL.
7. Done!

## License

GPL v3

