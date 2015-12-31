# Ruby CMS

[![Build Status:master](https://travis-ci.org/bblue/ruby.svg?branch=master)](https://travis-ci.org/bblue/ruby)
[![Build Status:develop](https://travis-ci.org/bblue/ruby.svg?branch=develop)](https://travis-ci.org/bblue/ruby)

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


<a name="troubleshooting" />

## Troubleshooting

The framework is compliant with PSR-3 loggers and ships with a file logger and an echo logger by default. In order to enable additional diagnostic messages, adjust user setting "log_level" as follows:

| Level                     | Description                                                    |
| ------------------------- | -------------------------------------------------------------- |
| `0`, `error`              | Errors.                                                        |
| `1`, `warn`, `warning`    | Warnings.                                                      |
| `2`, `notice`             | Notices. This is the default logging level.                    |
| `3`, `info`               | Informational messages.                                        |
| `4`, `debug`              | Debugging messages.                                            |
| `5`, `trace`              | Tracing. Using this level might noticeably slow down plugin.   |

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
