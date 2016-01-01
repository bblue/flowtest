# Ruby CMS

[![Build Status](https://travis-ci.org/bblue/ruby.svg?branch=develop)](https://travis-ci.org/bblue/ruby)
[![Coverage status](https://api.codacy.com/project/badge/coverage/53d54419f0a54001a1755731a5e9693b)](https://www.codacy.com/app/aleksander-lanes/ruby/php-codacy-coverage)
[![Code quality](https://api.codacy.com/project/badge/grade/53d54419f0a54001a1755731a5e9693b)](https://www.codacy.com/app/aleksander-lanes/ruby/php-codacy-coverage)
[![Current release](https://img.shields.io/github/release/bblue/ruby.svg)](https://github.com/bblue/ruby/releases/latest)

**Attention**: This framework is considered to be alpha quality and currently under development. Documentation might not be up-to-date, as well as functionality missing or things simply not working as intended or expected. Also for now, you'll have to install and configure this framework manually via git. You have been warned.

## Summary

 * Highly modular CMS framework built with PHP
 * Influenced by Symfony architecture (although I never read the Symfony code, just studied their implementation strategy)
 * PSR-3 compliant logging mechanisms
 * PSR-4 autoloading comliant
 * Domain driven design (DDD)

#### Table of contents

* [Quick start](#quick-start)
* [Installation](#installation)
* [Usage](#usage)
* [Configuration](#configuration)
* [Dependencies](#dependencies)
* [Troubleshooting](#troubleshooting)
	+ [Reporting bugs](#troubleshooting-reporting-bugs)
* [Say thanks](#say-thanks)
* [References / Links](#references-links)
* [Changes](#changes)

<a name="quick-start" />

## Quick start

to be added....

Read on for detailed installation, usage, configuration and customization instructions.

<a name="installation" />

## Installation

to be added....


<a name="usage" />

## Usage

to be added....

<a name="configuration" />

## Configuration

to be added....

Note that a separate configuration file and bootstrp file is requried. Example files will be added.

<a name="configuration-key-bindings" />

<a name="dependencies" />
## Dependencies

_Required_
 
 * Doctrine
 * Twig
 * PHPmailer
 
_Optional_

 * Symfony (for management of doctrine)

<a name="troubleshooting" />

## Troubleshooting

The framework is compliant with PSR-3 loggers and ships with a file logger and an echo logger by default. In order to enable additional diagnostic messages, adjust user setting "sLogLevelThreshold" as follows:

| Level                     | Description                                                                                       |
| ------------------------- | ------------------------------------------------------------------------------------------------- |
| `1`, `debug`              | Detailed debug information                                                                        |
| `2`, `info`               | Interesting events                                                                                |
| `3`, `notice`             | Normal but significant events. This is the default logging level                                  |
| `4`, `warning`            | Exceptional occurrences that are not error                                                        |
| `5`, `error`              | Runtime errors that do not require immediate action but should typically be logged and monitored  |
| `6`, `critical`           | Critical conditions|                                                                              |
| `7`, `alert`              | Action must be taken immediately                                                                  |
| `8`, `emergency`          | System is unusable                                                                                |

<a name="reporting-bugs" />

## Reporting bugs

In order to make bug hunting easier, please ensure, that you always run the *latest* version of *Ruby CMS*. Apart from this, please ensure, that you've set log level to maximum (`"log_level": "debug"` in configuration file), in order to get all debugging information possible. Also please include the following information, when submitting an issue:

* Operating system name (i.e. "Windows", **not** "Windows")

* Web server

* The number in the VERSION file that came with Ruby CMS

* Output from logging file



<a name="say-thanks" />

## Say thanks

to be added....

<a name="changes" />

## References / Links

* [Symfony](https://github.com/Symfony)

## Changes

None yet. Plugin not released.
