# BOSS - BSZ One Stop Search

## Introduction
BOSS is a fork of the VuFind project with adaptions for the German library system.
BOSS is currently based on VuFind 6. BOSS is designed to support many local view
with only one theme to keep it simple and reduce the cost of maintenance. 

VuFind is an open source discovery environment for searching a collection of
records.  To learn more, visit https://vufind.org.


## Installation
Because we maintain many installations, we needed store the configurations in Git,
too. For security reasons, the configs are in their own private repository and you 
need to set up symlinks to `config` and `local` dirs. 

## Upgrade

* `git remote add vufind https://github.com/vufind-org/vufind`
* Remove `config` and `local` symlinks temporarily
* `git pull vufind master`
* resolve conflicts if any
* look for new config options with `diff` and move them to our own repo. 
* delete the `local` and `config` dirs and restore the Symlinks again
* Run unit tests


