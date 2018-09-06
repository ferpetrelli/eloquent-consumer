# About

Eloquent API Consumer will allow you to solve two main problems regarding API's in Laravel:

1. Generate and execute API calls in a clear and simple way
2. Process API responses and generate Eloquent like models and collections (including paginated ones)


## Development Note

This package is still on alpha state, and under heavy development. Things might change in the near future.


## Motivation to create this package

Our CMS was designed to use Eloquent like models as the data source.

So we needed a way to integrate an API origin to build some listings in a seamless way, without having to modify the CMS.

This library creates models who's interface will be compatible with Eloquent, so building queries, pagination, scopes, filtering, and mostly everything related to Eloquent will be available.

With this package you will be able to manage your API endpoints, caching strategies, low level API query configuration, model attributes, scopes and functions, and basically every element involved in the process of generating a query, to getting the processed end result.

# Table of contents

- [Overview](#overview)
- [Core Concepts](#core-concepts)

    - [Endpoint](#endpoints)
    - [Consumer](#consumers)
    - [Grammar](#grammar)

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Extended Reference](#extended-reference)
- [License](#license)

# Overview

Let's imagine we have a `Book` API model and we want to read some data from the API.
Let's perform some calls right now:

```php

// Call to the collection endpoint, and return a collection of Books
\App\Book::query()->get();

// Call to the collection endpoint, and return a paginated collection of 10 elements
\App\Book::query()->paginate(10);

// Call to the resource endpoint, {id} will be replaced with the value of $id and will return a Test object.
\App\Book::query()->find($id);

// Call to the resource endpoint, {id} will be replaced with the value of $id and will return a Test object. Throw a 404 if not found.
\App\Book::query()->findOrFail($id);

// Let's get a collection of search results for a term. This will simply add a 'q=Julio Cortazar' parameter to the query by default.
`\App\Book::query()->search('Julio Cortazar')->get()`

// Call to the collection endpoint, and let's just add a customized parameter to this call to search books by ISBN.
`\App\Book::query()->rawQuery(['ISBN' => 123456])->get()`

// Call to the collection endpoint, and return a collection of Books with only id, and title columns
\App\Book::query()->get(['id, 'title']);

```

As you can see this syntax is very familiar.

Let's say you want to add your own Eloquent like scope to get only published elements. Just follow the same syntax as you usually do with Laravel:


```php
<?php

// ...
class Book extends ApiModel
{
    //...

    public function scopePublished($query)
    {
        return $query->rawQuery(['published' => true]);
    }

    //...
}
```


```php
// Call to the published scope and then call to the collection endpoint
\App\Book::query()->published()->get();
```


This will pass a `published=true` parameter when performing the API call (because we simply used the rawQuery function as we saw before)

To configure this model we have to create an `Endpoint` class for our specific API. You can do that manually or using the following command:

```
php eloquent-consumer:endpoint Main
```

Let's edit some options:

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

Now that we have the endpoint configured, we should create a the actual Book Model. This model must inherit from our faux Eloquent like class.

```php
<?php

namespace App;

use \Petrelli\EloquentConsumer\Models\ApiModel;

class Book extends ApiModel
{

    protected $endpointClass = \App\ApiConsumer\Endpoints\Main::class;

    protected $endpoints = [
        'collection' => '/books',
        'resource'   => '/books/{id}',
    ];

}
```

Here we just have to configure two things:

* Endpoint class to be used
* Actual URL's used by this type of resource

Collection and resource are the default endpoints that the package uses. Of course you can define your own and use them later, but these are enough.

This will be enough to get you started.

If you continue reading you will learn to modify how calls are performed, and how responses will be  processed.


# Core concepts

You will have a default configuration file for you project defining everything we will show here. But of course, you can override these values configuring each class separately.

It's recommended to use the default namespace `ApiConsumer` when creating new elements.

We will mention the following entities:

* Endpoint
* Consumer
* Grammar



## Endpoint

This one will be your main entity.

You can have as many as you want per project, allowing you to use multiple API's and/or multiple configurations within the same API.

Here you can define the following options:

```php

use \Petrelli\EloquentConsumer\Endpoints\BaseEndpoint;

class MainEndpoint extends BaseEndpoint
{

    //Mandadory if not added at the general config file
    //This will be your baseline URL. E.g. http://apibase.com
    protected $baseUri;

    //Mandadory if not added at the general config file. Number of seconds each call will be cached.
    protected $defaultTTL;

      //Custom Grammar or Consumer classes
    protected $grammarClass;
    protected $consumerClass;

    //---
}
```

* Base URI ()
* Default TTL when caching (mandatory if not at the config file)

And more importantly, you could replace the default entities as well:

* Consumer Class
* Grammar Class


## Consumer

Consumers are the clients that will execute the actual API calls.
This package includes a default client that uses [Guzzle](http://docs.guzzlephp.org/en/stable/).

Usually using the default one with Guzzle will be more than enough.

Creating a new Consumer class could be useful if you had to add new options like authentication headers, timeout configurations, basically, any configuration you might want supported by Guzzle or your chosen client.



## Grammar

A Grammar class basically a collector that transforms all the information you gave to the Query Builder into fields sent out in your API request.

It's a very simple class that you can extend and adjust to your API specs.

Let's see this Grammar Class example function:

```php
protected function compileSearchText($query, $text)
{
    if ($text)
        return ['q' => $text];
    else
        return [];
}
```

Here you see that the function search() will be transformed into a parameter named `q`.

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

Here you can see it's a very basic Endpoint. No grammar or consumer customized.

You can add a `$grammarClass` variable in case you want to extend the default one:

```php
protected $connectionClass = \App\ApiConsumer\Grammar\MyOwnGrammar::class;
```


And we will need to create that Grammar class:

```php
<?php

namespace App\ApiConsumer\Grammar;

use \Petrelli\EloquentConsumer\Grammar\BaseGrammar;

class MyOwnGrammar extends BaseGrammar
{

      protected function compilePage($query, $page)
    {
        return ['page_number' => $page];
    }

}
```

So here, we redefine `compilePage` which will send a `page_number` parameter on the API query, instead of the `page` default.


# Usage

After everything is configured we can start using this model almost as if it was an eloquent entity.

```php

// Call to the collection endpoint, and return a collection of Books
\App\Book::query()->get();

// Call to the collection endpoint, and return a paginated collection of 10 elements
\App\Book::query()->paginate(10);

// Call to the resource endpoint, {id} will be replaced with the value of $id and will return a Test object.
\App\Book::query()->find($id);

// Call to the resource endpoint, {id} will be replaced with the value of $id and will return a Test object. Throw a 404 if not found.
\App\Book::query()->findOrFail($id);

// Let's get a collection of search results for a term. This will simply add a 'q=Julio Cortazar' parameter to the query by default.
`\App\Book::query()->search('Julio Cortazar')->get()`

// Call to the collection endpoint, and let's just add a customized parameter to this call to search books by ISBN.
`\App\Book::query()->rawQuery(['ISBN' => 123456])->get()`

// Call to the collection endpoint, and return a collection of Books with only id, and title columns
\App\Book::query()->get(['id, 'title']);

```

As you can see this syntax is very familiar.

Let's say you want to add your own Eloquent like scope to get only published elements. Just follow the same syntax as you usually do with Laravel:


```php
<?php

// ...
class Book extends ApiModel
{
    //...

    public function scopePublished($query)
    {
        return $query->rawQuery(['published' => true]);
    }

    //...
}
```


```php
// Call to the published scope and then call to the collection endpoint
\App\Book::query()->published()->get();
```


This will pass a `published=true` parameter when performing the API call (because we simply used the rawQuery function as we saw before)


## Relationships

TODO

# Transformer classes

So we generated the query, and now we are getting a correct response.

We have now to refactor this response to fit the library standards so everything works as expected.

This will be done easily with a TransformerClass.

```
<?php

namespace App\ApiConsumer\Transformers;

use Petrelli\EloquentConsumer\Transformers\BaseTransformer;


class Base extends BaseTransformer
{

    /**
     * Transform Grants API response to a format we can read
     */

    public function transform()
    {
        $original = $this->response->body;

        $original->pagination = (object) [
         'total' => $original->recordsTotal,
         'page'  => $original->draw,
        ];

        return $this->response;

    }


}
```


Transformer classes have only one function: `transform()`.
There you have available `$this->response` to use it and transform your responses to our proper format.

This will enable you to use any API transparently of the underline response format.

The expected format looks like the following:

```json
{
    pagination: {
        total: 100,
        limit: 12,
        offset: 0,
        total_pages: 520,
        current_page: 1,
    },
    data: [
        {},
        {},
        {},
        {},
        ...
    ]
}
```


So in order to be able to use full power, you will have to adapt your response to look like this one.


# Extended Reference
TODO

# License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
