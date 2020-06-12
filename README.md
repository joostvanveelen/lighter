# Lighter

Lighter is a little script to start your development environments. It allows you to start your application with all the
services it depends on, in one simple command.

## 1. Features

* Start/stop docker networks, the Traefik router and docker-compose projects
* Build docker-compose projects
* Manage run-time dependencies between projects

## 2. Installation

* Download Lighter: https://bitbucket.org/dokkie/lighter/downloads/lighter.phar
* (optional, but recommended) Place "lighter.phar" in a folder that's in your path.
  like "/usr/local/bin" or "~/bin". You can also rename the file or create a
  symlink to the file in a bin folder.

You can also build Lighter yourself:

* Clone the repository
* Build the phar file by running "php build.php". The phar file will be created in "build/lighter.phar"

## 3. Configuration

In order to start using Lighter, it needs to know where your environments are. You can download a predefined
configuration file from the Downloads section, or create your own. The two sections below describe each option. Pick
one of these options to configure Lighter.   

### 3.1. Predefined configuration

This section describes how to use the predefined configuration file. If you want to create your own configuration file,
skip this section and read section 3.2 instead. 

* Download the predefined configuration file from https://bitbucket.org/dokkie/lighter/downloads/.lighter.yaml
* Move the file to .lighter.phar in your home directory.
* Edit the file and replace all instances of "/my-projects-folder/" with the actual path to your projects.

Enjoy Lighter!

### 3.2. Create your own configuration

This section describes how to create your own configuration file. If you want to use the predefined configuration file,
skip this section and read section 3.1 instead. 

You can use the "environment:add" command to register your environments with Lighter. All settings are stored in
~/.lighter.yaml. You can use your favorite editor to make manual modifications to this file if needed. See
See https://bitbucket.org/dokkie/lighter/src/master/.lighter.yaml.dist for a explanation of the configuation's file
options. 

Enjoy Lighter!

## 4. Usage

Lighter is a Symfony console application. The way it works is very similar to
"console" from Symfony projects and "composer".

Use "lighter.phar" to get an overview of all the commands

To start all registered environments: `lighter.phar on` ("on", "start" and "up" all
 do the exact same thing)

To stop all registered environments: `lighter.phar off` ("off", "stop", "down" and
"halt" all do the exact same thing)

You can also select one or more environments to start (or stop). Note that the
dependencies of the specified environments will also be started: `lighter.phar start
env1 env2`   