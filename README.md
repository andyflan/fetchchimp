# Fetchchimp

A Wordpress plugin that augments *Autochimp* to pull data from Mailchimp. Supports *Cimy Extra Fields* field mappings as set in *Autochimp*. Currently at a very early stage of development but operational, bugs aside. Better docs will be provided as development progresses.

## Installation

```shell
cd wp-content/plugins/
git clone git@github.com:andyflan/fetchchimp.git
```

...which should create a folder in your plugins directory called 'fetchchimp' with all the plugin files installed. Then just activate the plugin in Wordpress admin and you should be set.

## Usage

The idea is that you set a CRON job to trigger an import by hitting the url `/fetchchimp/trigger` something like every hour, which you might do by adding this to your crontab:

```
0 * * * * wget -O - http://yoursite.com/fetchchimp/trigger >/dev/null 2>&1
```

Edit your crontab by typeing this at the cmd prompt:

```shell
crontab -e
```

## Roadmap

* Lock trigger down to IP
