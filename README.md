# [RoadRunner][link_roadrunner] and [Ubiquity][link_ubiquity] Integration
[![Version][badge_packagist_version]][link_packagist]

Makes [the fastest PHP framework][link_php_bench] even faster.

## Installation
```shell
$ composer require lapinskas/roadrunner-ubiquity
```

## Dependencies
### Composer
Get [Composer](https://getcomposer.org/download/) if you have not done it yet

### PHP-CGI
php-cgi 7.4 is required for this package. 
> Please note it's php-cgi, not php, so most probably you have not it installed by default.

#### php-cgi installation on Ubuntu 18.04 LTS
As of today, PHP 7.4 is not available in Ubuntu default repositories. In order to install it, you will have to get it from third-party repositories.
```shell
$ sudo add-apt-repository ppa:ondrej/php
```

Then update and upgrade to PHP 7.4
```shell
$ sudo apt update
```

and install php-cgi
```shell
$ sudo apt-get install php7.4-cgi
```

### Ubiquity Framework
[Install Ubiquity Framework](https://micro-framework.readthedocs.io/en/latest/quickstart/quickstart.html
) using Composer

## Usage
As an example of a basic usage, let's create default Ubiquity project and run it using RoadRunner

### Create sample Ubiquity project
Let's create new project called 'firstProject' in a folder of your choice.
Flag -a adds rather powerful MyUbiquityAdmin application that we can use for usage testing.
```shell
$ Ubiquity new firstProject -a
$ cd firstProject
```
> Note: if Ubiquity is not in your path, you can find it at ~/.composer/vendor/phpmv/ubiquity-devtools/src/Ubiquity

### Add sample project requirements
Edit composer.json of your 'firstProject' and add requirement for this package
```
"require": {
    ...
    "lapinskas\/roadrunner-ubiquity": "^1.0"
},
```

### Update composer
```shell
$ composer update
```

### RoadRunner
The following command will automatically download latest binary executable to the project folder
```shell
$ vendor/bin/rr get
```

### Copy RoadRunner configuration
Copy RoadRunner sample configuration to the project root. Edit it if you need to change port of other settings
```shell
$ cp vendor/lapinskas/roadrunner-ubiquity/sample/.rr.yml .rr.yml
```

### Copy default Worker
Worker is the main entry point of the application and the replacement of traditional index.php file. Copy sample worker.php to the project root.
```shell
$ cp vendor/lapinskas/roadrunner-ubiquity/sample/worker.php worker.php
```

### Run RoadRunner
Start RoadRunner in debug mode
```
$ ./rr serve -v -d
```

### Open MyUbiquityAdmin page
Open admin page of Ubiquity application in your browser
[http://127.0.0.1:8090/Admin](http://127.0.0.1:8090/Admin)

Ubiquity application is exactly the same and could be run using command "Ubiquity serve", NGINX or Apache servers. The only change is the entry point of the application.

### Benchmarking
Each worker takes some time for the bootstraping / initialization for the very first request.
The consecutive requests do not require the bootstraping that results in much faster processing after all workers have been initialized.

Please feel free to run some benchmarking tests of RoadRunner+Ubiquity vs NGINX+Ubiquity or Apache+Ubiquity.
Preliminary tests have shown more than 100% increase in the number of requests per second and twice quicker response time.

## Changelog
[![Version][badge_packagist_version]][link_packagist]
[![Release date][badge_release_date]][link_releases]

Changelog can be [found here][link_changes_log].

## License
[![License](https://poser.pugx.org/lapinskas/roadrunner-ubiquity/license)](https://packagist.org/packages/lapinskas/roadrunner-ubiquity)

This is open-sourced software licensed under the [MIT License][link_license].

[badge_packagist_version]:https://img.shields.io/packagist/v/lapinskas/roadrunner-ubiquity.svg?maxAge=180
[badge_release_date]:https://img.shields.io/github/release-date/Lapinskas/roadrunner-ubiquity.svg?style=flat-square&maxAge=180
[link_roadrunner]:https://github.com/spiral/roadrunner
[link_ubiquity]:https://github.com/phpMv/ubiquity
[link_packagist]:https://packagist.org/packages/lapinskas/roadrunner-ubiquity
[link_php_bench]:http://www.phpbenchmarks.com/en/
[link_releases]:https://github.com/Lapinskas/roadrunner-ubiquity/releases
[link_changes_log]:https://github.com/Lapinskas/roadrunner-ubiquity/blob/master/CHANGELOG.md
[link_license]:https://github.com/Lapinskas/roadrunner-ubiquity/blob/master/LICENSE