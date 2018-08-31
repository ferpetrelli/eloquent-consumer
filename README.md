# About

Eloquent Consumer will allow you to solve two main problems regarding API's in Laravel:

1. Generate API calls in a clear and simple way
2. Process API responses and generate Eloquent like models and collections (including paginated ones)


## Note

This package is on an alpha state, and under heavy development. Things might change drastically as we are working to simplify the code to make the package easier to use.


## Motivation to create this package

Our CMS system was designed to use Eloquent as data source.

So we needed a way to manage data from mix sources (DB and APIs), without having to modify the CMS.

This library creates models who's interface will be compatible with Eloquent, so building queries, pagination, scopes, filtering, and mostly everything related to Eloquent will be available.

With this package you will be able to manage your API endpoints, caching strategies, low level API query configuration, model attributes, scopes and functions, and basically every element involved at the API interaction.

# Table of contents

- [Quick Overview](#quick-overview)
- [Core Concepts](#core-concepts)

    - [Endpoints](#endpoints)
    - [Connections](#connections)
    - [Consumers](#consumers)
    - [Grammar](#grammar)

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Extended Reference](#extended-reference)
- [License](#license)

# Overview

Let's first describe briefly the configuration. And then see how this will look afterwards.

Firstly we create an `Endpoint` class, here we will configure everything related to the API interaction (you can have as many endpoints as you want)


```php
<?php

namespace App\ApiConsumer\Endpoints;

use \Petrelli\EloquentConsumer\Endpoints\BaseEndpoint;

class Main extends BaseEndpoint
{

    protected $baseUri = 'https://baseapi.com';

    protected $defaultTTL = 200;


}
```

As you can see, we only defined here a `$baseUri` and a default TTL for caching (200 seconds).

After having the endpoint configured, we should create a Test Model. This model should inherit from our faux Eloquent like class.

```php
<?php

namespace App;

use \Petrelli\EloquentConsumer\Models\ApiModel;

class Test extends ApiModel
{

    protected $endpointClass = \App\ApiConsumer\Endpoints\Main::class;

    protected $endpoints = [
        'collection' => '/test_collection/index',
        'resource'   => '/test_collection/{id}',
    ];

}
```

Here we define which endpoint class it will use, and the actual URI endpoints to perform API calls against.
Collection and resource are the default endpoints that the package uses. Of course you can define your own and use them later, but these are enough.

Without getting into much more detail this will be enough to get you started.

Let's perform some calls:

```php
// Call to the collection endpoint, and return a collection of Test models
\App\Test::query()->get();

// Call to the collection endpoint, and return a paginated collection with 10 elements
\App\Test::query()->paginate(10);

// Call to the resource endpoint, {id} will be replaced with the value of $id and will return a Test object.
\App\Test::query()->findOrFail($id);
```

Let's say you want to add your own scope to get only published elements, you can simply add an eloquent like scope to the model like this:


```php
<?php

// ...
class Test extends ApiModel
{
    //...

    public function scopePublished($query)
    {
        $params = [
            "published" => true
        ];

        return $query->rawQuery($params);
    }

    //...
}
```


```php
// Call to the published scope and then call to the collection endpoint
\App\Test::query()->published()->get();
```

This will pass a `published=true` parameter when performing the API call.

# Core concepts

Configuration elements are separated to allow flexibility.
If you don't want to use the default configuration you will have to create new classes within your project and inherit from our base entities.

It's recommended to use the default namespace `ApiConsumer`

We will mention the following entities:

* Endpoint
* Connection
* Consumer
* Grammar



## Endpoints

This is the main collector entity. Here you can define the following options:

* Base URI (mandadory if not at the config file)
* Default TTL when caching (mandatory if not at the config file)

And more importantly, you could replace the default used entities as well:

* Connection Class
* Consumer Class
* Grammar Class

You will be able to define and use any number of endpoints, so you can mix different configurations to different models. Meaning, you can have multiple API's, or multiple caching strategies, a different consumer per endpoint with different headers, etc.



## Connections

Connections are in charge of:

* Organize the execution
* Organize caching
* Print logs
* Transform results format if needed (to be properly parsed by our package)

Possible options:

* Cache key name (used usually for versioning)
* Transformer Class

If you overload the `printLog` function you could change the format used in our logs.



## Consumers

Consumers are the clients that will execute the actual API queries.
This package includes a default client that uses Guzzle.

Usually this one is more than enough but you could overload this class to create your own. This is useful if you need to add new options like authentication headers.



## Grammar

A Grammar class is used to generate a query from all the information you gave to the Query Builder.

It will basically compile and transform all options used when calling a query into parameters ready to be used by our consumer.

It's a very simple class that you can extend and adjust to your API specs.

For example:

```php
protected function compileLimit($query, $limit)
{
    return [
        'size'  => $limit   // Elasticsearch search parameter for limiting
    ];
}
```

Here you see that the option `limit` is transformed into a parameter named `size`.

Please refer to our default grammar class code, and you will observe there how we process all options.

# Installation

1. Install the package via composer:

```
composer require petrelli/eloquent-consumer
```

2. Export configuration file:

```
php artisan vendor:publish --provider="Petrelli\EloquentConsumer\EloquentConsumerServiceProvider"
```

In there you can define default options and entities (endpoint, grammar and connection).

# Configuration

Let's generate an Endpoint:

```
php artisan eloquent-consumer:endpoint Base
```

Or in case you want to use your own namespace:

```
php artisan eloquent-consumer:endpoint EndpointName Namespace1/Namespace2
```

Now let's add some configuration values to the endpoint:

```php
<?php

namespace App\ApiConsumer\Endpoints;

use \Petrelli\EloquentConsumer\Endpoints\BaseEndpoint;

class Base extends BaseEndpoint
{

    protected $baseUri = 'https://baseapi.com';

    protected $defaultTTL = 200;


}
```

Here you can see it's a very basic endpoint that will use a default Connection.
You can add a `$connectionClass` variable in case you want to extend the defaults:

```php
protected $connectionClass = \App\ApiConsumer\Connections\Base::class;
```


And we will need to create that connection:

```php
<?php

namespace App\ApiConsumer\Connections;

use \Petrelli\EloquentConsumer\Connections\BaseConnection;

class Base extends BaseConnection
{

    protected $cacheKeyName = 'random-string-cache';

}
```

This is recommended so you have more control over caching, given that any time that you modify your `cacheKeyName` and deploy, all elements cached from this entity will be reload.

FINALLY, let's create the actual new model (named Test):


```php
<?php

namespace App;

use \Petrelli\EloquentConsumer\Models\ApiModel;

class Test extends ApiModel
{

    protected $endpointClass = \App\ApiConsumer\Endpoints\Base::class;

    protected $endpoints = [
        'collection' => '/test_collection/index',
        'resource'   => '/test_collection/{id}',
    ];

}
```

Here we have the default endpoints for this resource and the Endpoint class `\App\ApiConsumer\Endpoints\Base` that will hold all necessary configurations for this entity.


# Usage

After everything is configured we can start using this model almost as if it was an eloquent entity. All attributes will be mapped and parsed as specified inside the object.

```php
// Call to the collection endpoint, and return a collection of Test models
\App\Test::query()->get();

// Call to the collection endpoint, and return a paginated collection with 10 elements
\App\Test::query()->paginate(10);

// Call to the resource endpoint, {id} will be replaced with the value of $id and will return a Test object.
\App\Test::query()->findOrFail($id);

// Call to the published scope and then call to the collection endpoint
\App\Test::query()->published()->get();

// The scope called previously will be something like the following:
public function scopePublished($query)
{
    $params = [
        "published" => true
    ];

    return $query->rawQuery($params);
}

```

All of the functions used previously will be specified at the etended documentation.


# Extended Reference
TODO

# License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
