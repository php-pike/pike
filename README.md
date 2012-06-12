### Welcome to the *PiKe 1.4* Release!

[![Build Status](https://secure.travis-ci.org/php-pike/Pike.png?branch=master)](http://travis-ci.org/php-pike/Pike)

## RELEASE INFORMATION

PiKe 1.4 is mainly the same as the last 1.2 release except that the 
file and folder structure changed according to PSR-0 standards and unit-tests
are added, which is a good thing. 


### SYSTEM REQUIREMENTS
PiKe 1.4 is tested on Zend Framework 1.11.1, Doctrine 2.2 and strongly 
relies on jQuery 1.7. Cause of these libraries it requires at least 
PHP 5.3.3. 

### INSTALLATION
Installation is just as simple by adding PiKe to your vendors in your 
Zend Framework project and make sure the autoloading works correct. 

One side-node is that, when using Pike_View_Stream you have to
turn php_short_open_tag off! This is required by a small bug in ZF1. When
you enable Pike_View_Stream it will fix the short_open_tag automaticly for you.

### HELPING US
We greatly appreciate any people that want to help us by providing unit-tests
doing bug reports, adding documentation etc. Just fork as and do a pull request
and the rest will follow. 

We'll hope you enjoy our library!

With dutch greetings,
Pieter Vogelaar & Kees Schepers


