# DCP

This repo contains a collection of Drupal components which can be installed via composer.

As this package is not hosted on packagist the following needs to be added to the projects composer.json file:

`    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/pragmapartners/DCP"
        }
    ],
`

Additionally add the following to the require section:

`    "require": {
        "pragmapartners/dcp": "^0.0"
    },
`

Finally run the following to install the module

`composer require pragmapartners/dcp`


