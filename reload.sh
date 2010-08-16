#!/bin/sh
php mysql/console-dev doctrine:database:drop &&
php mysql/console-dev doctrine:database:create &&
php mysql/console-dev doctrine:schema:create &&
php mysql/console-dev doctrine:generate:proxies &&
php mysql/console-dev doctrine:data:load &&
php mysql/console-test doctrine:database:drop &&
php mysql/console-test doctrine:database:create &&
php mysql/console-test doctrine:schema:create &&
php mysql/console-test doctrine:generate:proxies &&
php mysql/console-test doctrine:data:load &&
echo "You're good to go!"
