# Squiz Debugger for Laravel

*squiz* : colloquial (originally Australian and New Zealand).  
A look; a glance. Frequently (and in earliest use) in to have (also take) a squiz (at): to have a look (at someone or something).

Squiz is a debugger for Laravel.

It allows you to make use of the Symfony VarDumper component output on a single screen dedicated for debugging and for observing your application.

It works with Laravel versions 9 to 12.  
It has not been tested on previous versions.

![screenshot](https://github.com/chisnall/squiz/blob/main/screenshot.png "Screenshot")

## Installation

Install with composer:

````
composer require chisnall/squiz --dev
````

## Default URL

The debugging page will default to this route in your application:

/squiz

This can be customised. See **Custom Settings** below.

## Usage

### Helper function

To add an entry to the log use the helper function:

````
squiz($var)
````

Providing a string or variable.

Multiple arguments are supported:

````
squiz($var1, $var2, $var3)
````

Any variable is supported: scalars, arrays, objects and resources. Even methods in objects.

This will render the output in the same style as the `dump()` function in VarDumper.

You can use the helper function anywhere in your code. Even in unit testing, feature testing and blade views.

### Log and terminate

If you want to terminate code execution immediately after logging, use this helper function:

````
squizd()
````

That is similar to the `dd()` function in VarDumper.

### Comments

An optional comment can also be supplied.
Prefix with `comment:`

````
squiz($var, 'comment:Comment goes here')
````

### Blade directives

These are provided:

````
@squiz
@squizd
````

### Backtrace alias

A backtrace alias is provided for convenience: `__backtrace__`

Example:

````
squiz($var, '__backtrace__')
````

### Production environments

If a production environment is detected, the helper functions do not log anything. They simply return.  
So it is safe to leave helper function code in your project.

## Log Entries

When the helper function is called, a new entry will be displayed on the debugging page.

Each entry shows:

1. date and time
2. the name of the script the helper function was called from
3. the line number the helper function was called from
4. an optional comment
5. the output of VarDumper

## Custom Settings

Put these in your .env file to customise settings.

````
SQUIZ_TOKEN=
SQUIZ_POLLING_INTERVAL=1000
SQUIZ_STORAGE_PATH=[your Laravel storage path]
SQUIZ_ROUTE_PATH=/squiz
SQUIZ_TITLE="Squiz"
SQUIZ_HEADING="Squiz"
 ````

The above values are the defaults if they are not specified in the .env file.

`SQUIZ_TOKEN` optional token to protect the debugging page route and API routes. Ignored on local environments.

`SQUIZ_POLLING_INTERVAL` the polling interval in ms.

`SQUIZ_STORAGE_PATH` the Laravel storage path. **[1]**

`SQUIZ_ROUTE_PATH` the route path for the debugging page and API routes.

`SQUIZ_TITLE` title for the debugging page.

`SQUIZ_HEADING` heading text for the debugging page.

**[1]** set this if you are running unit or feature tests and you are using a temporary storage path while testing, yet 
still want to use the squiz() helper function in your tests.

## Token

If a token is specified in your .env file, specify the token as follows when visiting the debugging page:

````
/squiz?token=[token goes here]
````

If a token is specified, it is not required for local environments. It will only be used for staging and production environments.
