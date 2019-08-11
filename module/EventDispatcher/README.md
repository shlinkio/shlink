# Shlink Event Dispatcher

This library provides a PSR-14 EventDispatcher which is capable of dispatching both regular listeners and async listeners which are run using [swoole]'s task system.

Most of the elements it provides require a [PSR-11] container, and it's easy to integrate on [expressive] applications thanks to the `ConfigProvider` it includes.

## Install

Install this library using composer:

    composer require shlinkio/shlink-event-dispatcher

> This library is also an expressive module which provides its own `ConfigProvider`. Add it to your configuration to get everything automatically set up.
