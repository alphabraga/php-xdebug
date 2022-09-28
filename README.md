# Debugging PHP-APACHE with XDebug v3 inside Docker using VSCode #

## Assumptions / Prerequisites ##

- XDebug v3+ inside Docker (e.g. php:7.4-apache Docker image)
- Running Docker
- VSCode with *PHP Debug* Extension
- Using Docker Compose for orchestration

## Objective ##

I want to debug my running dockerized PHP web application code from within VSCode using XDebug.

## Setup ##

My PHP application code runs inside the official Docker PHP container, [php:7.4-apache](https://hub.docker.com/_/php).  I use the [Docker PHP extension installer](https://github.com/mlocati/docker-php-extension-installer) to install my extensions, XDebug and Composer.

## What I discovered trying to make this work ##

- XDebug v3 has breaking changes from v2.  The config file and behavior of v3 is very different from v2.  Most of the internet resources I found on the internet pertain to XDebug v2.  Once I realized I was running XDebug v3, it became easier to get this working.  Basically, forget what you know about xDebug 2 configuration files and [go read the docs](https://xdebug.org/docs/all_settings).
- Normally, processes inside a Docker container shouldn't need to connect to services on the host.  The way XDebug is designed (as I understand it), XDebug inside of the Docker container is more like a "client" that connects to a debug "xdb server" running in VSCode.  This connection is established from inside the Docker container out to the host.  Thus, it's not necessary to expose any ports to the container, rather, the container needs to know how to connect to the host.
- Docker v20.10+ includes a special `host.docker.internal` hostname that containers can access when enabled.  Some earlier versions of Docker have OS-specific ways of enabling this.  Best to stick with Docker 20.10+

## Steps ##

1. Install VSCode and the PHP Debug extension
2. Build a Docker image from the [official PHP](https://hub.docker.com/_/php) image.  e.g. php:7.4-apache
4. In your image, install XDebug v3 using the [Docker PHP extension installer](https://github.com/mlocati/docker-php-extension-installer)
5. In your image, copy in your application code
6. Create a `info.php` that includes the line `xdebug_info();` which you can access in the host's web browser to verify what Xdebug settings are enabled
7. Create a VSCode debug configuration for XDebug like this:

```
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
              "/var/www/html/": "${workspaceRoot}/app/"
            }
        },
    ]
}
```
Note that the port must match what XDebug is configured to connect to, and the pathMappings may be different for your application

8. in your docker-compose.yml add:
```
    extra_hosts:
      - "host.docker.internal:host-gateway"
```

9. You can control XDebug configuration via environment variables.  This could be helpful for dev/prod setups.  In my setup, I chose to enable XDebug via an environment variable in my `docker-compose.yml` file:
```
    environment:
      - XDEBUG_MODE=develop,debug
```

10. Using the docker-php-extension-installer with the official PHP image, a blank configuration file lives in `/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini`.
I customized mine like the following, including the default mode set to `off` (which my environment variable above overrides to `develop,debug`
```
[xdebug]
zend_extension=xdebug
xdebug.mode=off
xdebug.start_with_request = yes
xdebug.client_host = "host.docker.internal"
xdebug.idekey="VSCODE"
xdebug.log=/tmp/xdebug_remote.log
```

11. Now, when you start your container, you should be able to start up the VSCode debugger and get it to stop on breakpoints in your web application code.

## Tips ##

- XDebug 3 defaults to port 9003, while the old XDebug 2 defaults to port 9000.  Watch out for this in examples on the internet.
- Once I got the pathMappings variable correctly setup in VSCode, I was able to step through my PHP application running in Docker!  Unfortunately, my Composer dependencies only exist inside the container, not on the host.  Thus, VSCode encountered errors e.g. `"could not find file /var/www/html/vendor/blah"` referring to a PHP dependency managed by Composer when stepping into vendor libraries.  In my situation, I don't want Composer dependencies to exist outside of Docker, so I'm not able to debug/step through the dependent libraries.  If you want this ability, make sure that you have a copy of the dependencies on your host (or do the composer install step on your host) before debugging.
- Turn off `Breakpoints: Everything` in the VSCode debugger.  This was causing VSCode to want to step inside vendor libraries. 

## How to Debug your XDebug setup :) ##

- I used `netcat` and `ping` inside my Docker container to verify that I could talk to the host from inside the Docker container. (`apt install -y inetutils-ping netcat`)  Here's a netcat example to verify that port 9003 is open on the host and I can talk to it from inside the Docker container:
```
root@44eabbdd0967:/var/www/html# nc -vz host.docker.internal 9003
host.docker.internal [172.18.0.1] 9003 (?) open
```

If instead of `open` you get a message like `Connection refused` then you know something isn't setup correct yet, OR you haven't clicked the green triangle in VSCode to run the debugger (so it can listen for connections on port 9003).  On my setup the VSCode status bar changes colors to orange when the debugger is running

## Internet References ##

These references were helpful to me in putting this together for my project:

- https://xdebug.org/docs/all_settings#xdebug.mode
- https://xdebug.org/docs/install
- https://tighten.co/blog/configure-vscode-to-debug-phpunit-tests-with-xdebug/
- https://stackoverflow.com/questions/43360282/docker-and-xdebug-not-reading-breakpoints-vscode/43365142
- https://github.com/sillsdev/web-languageforge/pull/936