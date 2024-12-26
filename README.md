# SiteBase - Yet another base for websites

## Important notices

The main repository for this code is github. If you have received the file
from another location please check this URL for the latest version:

    https://github.com/degami/sitebase

For bug reports, feature requests, or support requests, please start a case
on the GitHub case tracker:

    https://github.com/degami/sitebase/issues

This project is open source licenses using GPL. See LICENSE.txt for more info.

## Requirements:

  * PHP 8.3+
  * composer
  * npm + gulp
  * compass
  * elasticsearch (optional)
  * redis (optional)
  * an unix environment (i've tested it on Linux and Mac)

## Roadmap

 * **Finish the Documentation!** - you can also use phpdoc to generate a nice one ( bin/console generate:docs )
 * check code / find bugs
 * add useful features

## Introduction

Yet another website base? Yes.
I've been developing websites since 15 years from now, using the most used projects (aka Drupal and Wordpress, or Symfony and Laravel) and also trying something "new" (like ).

Till now i didn't found a project that has **all** this requirements:

- supports multilanguage in the core
- supports multisites in the core
- supports CDN in the core
- supports Full Page Cache in the core
- supports migrations in the core
- supports SMTP/SES in the core
- supports 2FA in the core
- has users/roles permission logics
- supports queues (long running processes) and crontabs (scheduled actions) in the core
- monolithic by design (you need to develop your site needed features, not to install something made by someone else that does "almost" the same as your needs and then override its behaviour)
- has the minimum CMS features
- has configurable contact forms in the core
- is also a good base for a "custom" website
- is relatively easy to understand
- has a simple and builtin html forms handling library
- does not change its core in every major release.

I know - there is plently of project that have this requirements (and also a strong community behind their shoulders), but what i wanted was a base that has all this basic features by default, and developing it was also a good point to renew my basic knowledges.

## Installation

you can install by the web installer "/setup.php" or by the console installer "/bin/setup.sh". The latter is the preferred one, as you won't have server related problems (eg, timeouts during script running)

if you want to do it by hand, you can run (in the main project directory):

- composer install
- composer dump-autoload
- bin/console app:deploy
- bin/console generate:rsa_key
- bin/console app:mod_env
- bin/console db:migrate
- bin/console db:migrate_optionals # (if you want to fill the site with fake data to see the basic features)
- add the following to your crontab
```
    * * * * * php <site root folder>/bin/console cron:run > /dev/null 2>&1
    * * * * * php <site root folder>/bin/console queue:process > /dev/null 2>&1
```
- touch .install_done

## Usage

using bin/console should be quite familiar ( and auto explicative ).

In the .env file you can change the basic informations (eg. the administration location or the database / elasticsearch / redis / smtp / ses credentials )

## Help Wanted

If you think that this project can be useful, contributing in any way is welcome. Just fork and drop me a merge request!

## If you wish to use vue theme

- bin/docker sh php-fpm
- cd templates/frontend/vue_theme/
- npm install
- npm run build
- bin/console config:edit -p app/frontend/themename --value vue_theme --no-interaction
