# Message Store Export Demo
## Setup and run
```
git clone https://github.com/ringcentral-tutorials/message-store-report-php
cd message-store-report-php

$ curl -sS https://getcomposer.org/installer | php
$ php composer.phar require ringcentral/ringcentral-php
$ php composer.phar require vlucas/phpdotenv
```
Rename the dotenv to .env and provide your app credentials, RingCentral sandbox or production account's username and password

```
$ php report.php
```
