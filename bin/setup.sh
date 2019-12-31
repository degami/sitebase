#!/bin/bash

root_dir=`pwd`
php_bin=`which php`
composer_bin=`which composer`
npm_bin=`which npm`
console_bin="$root_dir/bin/console"

function install_cron {
    crontab_command=$1 
    cron_found=$(crontab -l | grep "$crontab_command" | wc -l)
    if [ $cron_found -lt 1 ]; then
        echo "adding '* * * * * $crontab_command > /dev/null 2>&1'"
        crontab -l | { cat; echo "* * * * * $crontab_command > /dev/null 2>&1"; } | crontab -
    else
        echo "$crontab_command is already installed"
    fi
} 


if ! [ -e $console_bin ]; then
    echo "This script should be run from the root dir"
    exit
fi

if [ -e $root_dir/.install_done ]; then
    echo "installation is already done"
    exit
fi

echo "installing dependencies..."
$composer_bin install && $composer_bin dump-autoload

echo "install site..."
touch .env
$php_bin $console_bin app:mod_env
$php_bin $console_bin app:deploy
$php_bin $console_bin db:migrate

unset REPLY
echo ""
read -p "Migrate also optional data? " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    $php_bin $console_bin db:migrate_optionals
fi

unset REPLY
echo ""
read -p "Install crontabs? " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "installing crontabs"
    install_cron "$php_bin $console_bin cron:run"
    install_cron "$php_bin $console_bin queue:process"
else
    echo ""
    echo ""
    echo "Remember to add the following to the right crontab."
    echo "* * * * * $php_bin $console_bin cron:run > /dev/null 2>&1"
    echo "* * * * * $php_bin $console_bin queue:process > /dev/null 2>&1"    
    echo ""
fi


echo "installation done."
touch .install_done