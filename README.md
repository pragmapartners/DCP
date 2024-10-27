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


## HOW TO DEVELOP

Run the following script from your projects root dir.

`chmod +x web/modules/contrib/dcp/setup-dcp-dev.sh | 
web/modules/contrib/dcp/setup-dcp-dev.sh`

The script basically runs through the following steps:

Git clone the repo as its own project and checks out to the latest release (the same release as composer ) 

Run this symlink command to connect the newly made DCP dir to the current project modules/contrib dir 

Update the composer.json file to run off the newly made DCP dir rather than the latest release via github

now you can develop while seeing live updates - push - pull - run composer commands without the fear of things getting wiped.

once you have finished developing, commit, make a latest tag, push and finally release the tag.
you can then switch back to using composer setup.
