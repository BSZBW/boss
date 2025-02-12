# BOSS - BSZ One Stop Search

## Introduction
BOSS is a fork of the VuFind project with adaptions for the German library system.
BOSS is currently based on VuFind 10. BOSS is designed to support many local view
with only one theme to keep it simple and reduce the cost of maintenance. 

VuFind is an open source discovery environment for searching a collection of
records.  To learn more, visit https://vufind.org.


## Installation
Because we maintain many installations, we needed store the configurations in Git,
too. For security reasons, the configs are in their own private repository and you 
need to set up symlinks to `config` and `local` dirs. 

See our [online installation documentation](https://vufind.org/wiki/installation) 
for step-by-step instructions for installing from packaged releases to popular 
platforms.

VuFind's [packaged releases](http://vufind-org.github.io/vufind/downloads.html) have
all dependencies included. If you are installing directly from a Git checkout, 
you will need to load these dependencies manually using the [Composer](https://getcomposer.org) tool by running `composer install` from the VuFind home directory.


## Documentation and Support
The VuFind community maintains a detailed [wiki](http://vufind.org/wiki) containing
 information on using and customizing the software. The VuFind website also lists [sources of community and commercial support](http://vufind-org.github.io/vufind/support.html).


## Contributing
See our [developers handbook](https://vufind.org/wiki/development) for more
information.
