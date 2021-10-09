[![Packagist][packagist-shield]][packagist-uri]
[![License][license-shield]][license-uri]
[![Stargazers][stars-shield]][stars-url]
[![Donate][donate-shield]][donate-uri]
[![Quality Gate Status][sonar-shield]][sonar-uri]

<br />
<p align="center">
      <img src="https://fireflyiiiwebsite.z6.web.core.windows.net/assets/logo/small.png" alt="Firefly III" width="120" height="178">
  </a>
</p>

# Firefly III bunq 🌈 Importer

> ⚠️ Active maintenance on the Firefly III bunq importer **has stopped** per January 1st, 2022. [Read more in the announcement](https://github.com/firefly-iii/firefly-iii/issues/5161).

<!-- MarkdownTOC autolink="true" -->

- [Introduction](#introduction)
	- [Purpose](#purpose)
	- [Features](#features)
	- [Who's it for?](#whos-it-for)
- [Installation](#installation)
	- [Upgrade](#upgrade)
- [Usage](#usage)
- [Known issues and problems](#known-issues-and-problems)
- [Other stuff](#other-stuff)
	- [Contribute](#contribute)
	- [Versioning](#versioning)
	- [License](#license)
- [Need help?](#need-help)
	- [Support](#support)

<!-- /MarkdownTOC -->

## Introduction

This is a tool to import from bunq 🌈 into [Firefly III](https://github.com/firefly-iii/firefly-iii). It works by using your bunq API token and a Firefly III personal access token to access your Firefly III installation's API.

> ⚠️ Active maintenance on the Firefly III bunq importer **has stopped** per January 1st, 2022. [Read more in the announcement](https://github.com/firefly-iii/firefly-iii/issues/5161).

### Purpose

Use this tool to (automatically) import your bunq transactions into Firefly III. If you're a bit of a developer, feel free to use this code to generate your own import tool.

> ⚠️ Active maintenance on the Firefly III bunq importer **has stopped** per January 1st, 2022. [Read more in the announcement](https://github.com/firefly-iii/firefly-iii/issues/5161).

### Features

* This tool will let you download or generate a configuration file, so the next import will go faster.
* 
### Who's it for?

Anybody who uses Firefly III and wants to automatically import bunq transactions.

## Installation

> ⚠️ Active maintenance on the Firefly III bunq importer **has stopped** per January 1st, 2022. [Read more in the announcement](https://github.com/firefly-iii/firefly-iii/issues/5161).

You can use this tool in several ways.

1. [Install it on your server using composer](https://docs.firefly-iii.org/other-data-importers/install/self_hosted/).
2. [Use the Docker-image](https://docs.firefly-iii.org/other-data-importers/install/docker/).

Generally speaking, it's easiest to use and install this tool the same way as you use Firefly III. And although it features an excellent web-interface, you can also use the command line to import your data.

### Upgrade

> ⚠️ Active maintenance on the Firefly III bunq importer **has stopped** per January 1st, 2022. [Read more in the announcement](https://github.com/firefly-iii/firefly-iii/issues/5161).

There are [upgrade instructions](https://docs.firefly-iii.org/other-data-importers/upgrade/) for boths methods of installation.

## Usage

The [full usage instructions](https://docs.firefly-iii.org/other-data-importers/) can be found in the documentation. Basically, this is the workflow.

1. [Set up and configure your tokens](https://docs.firefly-iii.org/other-data-importers/install/configure/).
2. [Upload your configuration file (optional)](https://docs.firefly-iii.org/other-data-importers/usage/upload/).
3. [Configure the import](https://docs.firefly-iii.org/other-data-importers/usage/configure/).
5. [Map values from bunq to existing values in your database](https://docs.firefly-iii.org/other-data-importers/usage/map/).
6. [Enjoy the result in Firefly III](https://github.com/firefly-iii/firefly-iii).

## Known issues and problems

Most people run into the same problems when importing data into Firefly III. Read more about those on the following pages:

1. [Issues with your Personal Access Token](https://docs.firefly-iii.org/other-data-importers/errors/token_errors/)
2. [Often seen errors and issues](https://docs.firefly-iii.org/other-data-importers/errors/freq_errors/).
3. [Frequently asked questions](https://docs.firefly-iii.org/other-data-importers/errors/freq_questions/).

## Other stuff

> ⚠️ Active maintenance on the Firefly III bunq importer **has stopped** per January 1st, 2022. [Read more in the announcement](https://github.com/firefly-iii/firefly-iii/issues/5161).

### Contribute

> ⚠️ Active maintenance on the Firefly III bunq importer **has stopped** per January 1st, 2022. [Read more in the announcement](https://github.com/firefly-iii/firefly-iii/issues/5161).


Of course, there are some [contributing guidelines](https://github.com/firefly-iii/bunq-importer/blob/main/.github/contributing.md) and a [code of conduct](https://github.com/firefly-iii/bunq-importer/blob/main/.github/code_of_conduct.md), which I invite you to check out.

For all other contributions, see below.

### Versioning

The Firefly III bunq Importer uses [SemVer](https://semver.org/) for versioning. For the versions available, see [the tags](https://github.com/firefly-iii/bunq-importer/tags) on this repository.

### License

This work [is licensed](https://github.com/firefly-iii/bunq-importer/blob/main/LICENSE) under the [GNU Affero General Public License v3](https://www.gnu.org/licenses/agpl-3.0.html).

<!-- HELP TEXT -->
## Need help?

> ⚠️ Active maintenance on the Firefly III bunq importer **has stopped** per January 1st, 2022. [Read more in the announcement](https://github.com/firefly-iii/firefly-iii/issues/5161).

If you need support using Firefly III or the associated tools, come find us!

- [GitHub Discussions for questions and support](https://github.com/firefly-iii/firefly-iii/discussions/)
- [Gitter.im for a good chat and a quick answer](https://gitter.im/firefly-iii/firefly-iii)
- [GitHub Issues for bugs and issues](https://github.com/firefly-iii/firefly-iii/issues)
- [Follow me around for news and updates on Twitter](https://twitter.com/Firefly_iii)

<!-- END OF HELP TEXT -->

### Support

If you like this tool and if it helps you save lots of money, why not send me a dime for every dollar saved!

OK that was a joke. You can donate using [Patreon](https://www.patreon.com/jc5). I am very proud to be a part of the [GitHub Sponsors Program](https://github.com/sponsors/JC5).

Thank you for considering donating to Firefly III, and the bunq Importer.

[![Scrutinizer][scrutinizer-shield]][scrutinizer-url]
[![Requires PHP7.4][php-shield]][php-uri]

[scrutinizer-shield]: https://img.shields.io/scrutinizer/g/firefly-iii/bunq-importer.svg?style=flat-square
[scrutinizer-url]: https://scrutinizer-ci.com/g/firefly-iii/bunq-importer/
[php-shield]: https://img.shields.io/badge/php-7.4-red.svg?style=flat-square
[php-uri]: https://secure.php.net/downloads.php
[packagist-shield]: https://img.shields.io/packagist/v/firefly-iii/bunq-importer.svg?style=flat-square
[packagist-uri]: https://packagist.org/packages/firefly-iii/bunq-importer
[license-shield]: https://img.shields.io/github/license/firefly-iii/bunq-importer.svg?style=flat-square
[license-uri]: https://www.gnu.org/licenses/agpl-3.0.html
[stars-shield]: https://img.shields.io/github/stars/firefly-iii/bunq-importer.svg?style=flat-square
[stars-url]: https://github.com/firefly-iii/bunq-importer/stargazers
[donate-shield]: https://img.shields.io/badge/donate-%24%20%E2%82%AC-brightgreen?style=flat-square
[donate-uri]: #support
[sonar-shield]: https://sonarcloud.io/api/project_badges/measure?project=firefly-iii_bunq-importer&metric=alert_status
[sonar-uri]: https://sonarcloud.io/dashboard?id=firefly-iii_bunq-importer
