# Update env vars behavior to have precedence over installer options

* Status: Accepted
* Date: 2022-01-15

## Context and problem statement

Shlink supports providing configuration via the installer tool that generates a config file that gets merged with the rest of the config, or via environment variables.

It is potentially possible to combine both, but if you do so, you will find out the installer tool config has precedence over env vars, which is not very intuitive.

A [Twitter survey](https://twitter.com/shlinkio/status/1480614855006732289) has also showed up all participants also found the behavior should be the opposite.

## Considered option

* Move the logic to read env vars to another config file which always overrides installer options.
* Move the logic to read env vars to a config post-processor which overrides config dynamically, only if the appropriate env var had been defined.
* Make the installer generate a config file which also includes the logic to load env vars on it.
* Make the installer no longer generate the config structure, and instead generate a map with env vars and their values. Then Shlink would define those env vars if not defined already.

## Decision outcome

The most viable option was finally to re-think the installer tool, and make it generate a map of env vars and their values.

Then Shlink reads this as the first config file, which sets the values as env vars if not yet defined, and later on, the values are read as usual wherever needed.

## Pros and Cons of the Options

### Read all env vars in a single config file

* Bad: This option had to be discarded, as it would always override the installer config no matter what.

### Read all env vars in a config post-processor

* Good because it would not require any change in the installer.
* Bad because it requires moving all env var reading logic somewhere else, while having it together with their contextual config is quite convenient.
* Bad because it requires defining a map between the config path from the installer and the env var to set.

### Make the installer generate a config file which also reads env vars

* Good because it would not require changing Shlink.
* Bad because it requires looking for a new way to generate the installer config.
* Bad because it would mean reading the env vars in multiple places.

### Re-think the installer to no longer generate internal config, and instead, just define values for regular env vars

* Bad because it requires changes both in Shlink and the installer.
* Bad because it's more error-prone, and the option with higher chances to introduce a regression.
* Good because it finally decouples Shlink internal config (which is an implementation detail) from any external tool, including the installer, allowing to change it at will.
* Good because it opens the door to eventually simplify the installer. For the moment, it requires a bit of extra logic to support importing the old config.
* Good because it allows keeping the logic to read env vars next to the config where it applies.
