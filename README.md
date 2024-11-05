# Silverstripe Google Config

[![Latest Stable Version](https://poser.pugx.org/iliain/silverstripe-google-config/v)](https://packagist.org/packages/iliain/silverstripe-google-config) 
[![Total Downloads](https://poser.pugx.org/iliain/silverstripe-google-config/downloads)](https://packagist.org/packages/iliain/silverstripe-google-config) 
[![Latest Unstable Version](https://poser.pugx.org/iliain/silverstripe-google-config/v/unstable)](https://packagist.org/packages/iliain/silverstripe-google-config) 
[![License](https://poser.pugx.org/iliain/silverstripe-google-config/license)](https://packagist.org/packages/iliain/silverstripe-google-config) 
[![PHP Version Require](https://poser.pugx.org/iliain/silverstripe-google-config/require/php)](https://packagist.org/packages/iliain/silverstripe-google-config)

Provides an admin interface separate from the Settings panel that lets users manage things like their GTM scripts, schema, reviews, etc.

## Installation (with composer)

	composer require iliain/silverstripe-google-config

## Config

Enable/disable specific tabs in the CMS by adding the following yaml config:

```yml
---
Name: myproject-google-config
After: 'google-config'
---
Iliain\GoogleConfig\Models\GoogleConfig:
  enabled_panels:
    GTM: true
    Schema: true
    Places: false # setting to false will hide the tab
```

Depending on which APIs you're using, you may need to include environment variables for your keys, like so:

```
GOOGLE_MAPS_API_KEY="xxxxxxxxxxxxxxxxxxxxx"
```

Currently this module uses the following APIs:

* Google Places

## Usage

You can call data from the Google settings on the frontend via `$GoogleConfig`, like so:

```
{$GoogleConfig.SchemaCode.RAW}

{$GoogleConfig.HeadScripts.RAW}

<% with $GoogleConfig %>
    <% if $Places %>
        <% loop $Places %>
            ...
        <% end_loop>
    <% end_if %>
<% end_with %>
```

### GTM Scripts

You can render the GTM scripts in your template with the following: 

* `$HeadScripts.RAW`
* `$BodyStartScripts.RAW`
* `$BodyEndScripts.RAW`

## Schema

You can configure the global schema data in the CMS, and render it in your template with `$GoogleConfig.SchemaCode.RAW`.

When editing the schema, you have the option to add Objects and/or Properties. Properties are single name/value pairs, while Objects can contain multiple Properties (good for addresses, etc.).

A tab named Output will display the expected output of your configured schema.

## Places 

Setting up a Place in the CMS, with an example of the Review data

![Example of setting up a Place](docs/images/place-fields.png)

With a selected Place, you can render the badge and feed in your template with `$ReviewBadge` and `$ReviewsList` respectively.

## TODO

* Fix the issue of the CMS needing to be reloaded for the map to appear when going back and viewing another map
* Add more APIs
* Update CSS to properly render as-is on the frontend (like a widget)
* Page specific schema?
