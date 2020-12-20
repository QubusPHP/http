Qubus Cookies
===========

Managing Cookies for PSR-7 Requests and Responses.

Concepts
--------

Qubus Cookies tackles two problems, managing **Cookie** *Request* headers and
managing **Set-Cookie** *Response* headers. It does this by way of introducing
a `Cookies` class to manage collections of `CookieCollection` instances and a
`SetCookies` class to manage collections of `SetCookieCollection` instances.

Instantiating these collections looks like this:

```php
// Get a collection representing the cookies in the Cookie headers
// of a PSR-7 Request.
$cookies = Qubus\Http\Cookies\Cookies::fromRequest($request);

// Get a collection representing the cookies in the Set-Cookie headers
// of a PSR-7 Response
$setCookies = Qubus\Http\Cookies\SetCookies::fromResponse($response);
```

After modifying these collections in some way, they are rendered into a
PSR-7 Request or PSR-7 Response like this:

```php
// Render the Cookie headers and add them to the headers of a
// PSR-7 Request.
$request = $cookies->renderIntoCookieHeader($request);

// Render the Set-Cookie headers and add them to the headers of a
// PSR-7 Response.
$response = $setCookies->renderIntoSetCookieHeader($response);
```

Like PSR-7 Messages, `CookieCollection`, `Cookies`, `SetCookieCollection`, and `SetCookies`
are all represented as immutable value objects and all mutators will
return new instances of the original with the requested changes.

While this style of design has many benefits it can become fairly
verbose very quickly. In order to get around that, Qubus Cookies provides
two facades in an attempt to help simply things and make the whole process
less verbose.


Basic Usage
-----------

The easiest way to start working with Qubus Cookies is by using the
`CookiesRequest` and `CookiesResponse` classes. They are facades to the
primitive Qubus Cookies classes. Their jobs are to make common cookie related
tasks easier and less verbose than working with the primitive classes directly.

There is overhead on creating `Cookies` and `SetCookies` and rebuilding
requests and responses. Each of the `Cookies` methods will go through this
process, so be wary of using too many of these calls in the same section of
code. In some cases it may be better to work with the primitive Qubus Cookies
classes directly rather than using the facades.


### Request Cookies

Requests include cookie information in the **Cookie** request header. The
cookies in this header are represented by the `CookieCollection` class.

```php
use Qubus\Http\Cookies\CookieCollection;

$cookie = CookieCollection::create('theme', 'blue');
```

To easily work with request cookies, use the `CookiesRequest` facade.

#### Get a Request Cookie

The `get` method will return a `CookieCollection` instance. If no cookie by the specified
name exists, the returned `CookieCollection` instance will have a `null` value.

The optional third parameter to `get` sets the value that should be used if a
cookie does not exist.

```php
use Qubus\Http\Cookies\CookiesRequest;

$cookie = CookiesRequest::get($request, 'theme');
$cookie = CookiesRequest::get($request, 'theme', 'default-theme');
```

#### Set a Request Cookie

The `set` method will either add a cookie or replace an existing cookie.

The `CookieCollection` primitive is used as the second argument.

```php
use Qubus\Http\Cookies\CookiesRequest;

$request = CookiesRequest::set($request, CookieCollection::create('theme', 'blue'));
```

#### Modify a Request Cookie

The `modify` method allows for replacing the contents of a cookie based on the
current cookie with the specified name. The third argument is a `callable` that
takes a `CookieCollection` instance as its first argument and is expected to return a
`CookieCollection` instance.

If no cookie by the specified name exists, a new `CookieCollection` instance with a
`null` value will be passed to the callable.

```php
use Qubus\Http\Cookies\CookiesRequest;

$modify = function (CookieCollection $cookie) {
    $value = $cookie->getValue();

    // ... inspect current $value and determine if $value should
    // change or if it can stay the same. in all cases, a cookie
    // should be returned from this callback...

    return $cookie->withValue($value);
}

$request = CookiesRequest::modify($request, 'theme', $modify);
```

#### Remove a Request Cookie

The `remove` method removes a cookie if it exists.

```php
use Qubus\Http\Cookies\CookiesRequest;

$request = CookiesRequest::remove($request, 'theme');
```

Note that this does not cause the client to remove the cookie. Take a look at
`CookiesResponse::expire` to do that.

### Response Cookies

Responses include cookie information in the **Set-Cookie** response header. The
cookies in these headers are represented by the `SetCookieCollection` class.

```php
use Qubus\Http\Cookies\Modifier\SameSite;
use Qubus\Http\Cookies\SetCookie;

$setCookie = SetCookieCollection::create('lu')
    ->withValue('Rg3vHJZnehYLjVg7qi3bZjzg')
    ->withExpires('Tue, 15-Jan-2013 21:47:38 GMT')
    ->withMaxAge(500)
    ->rememberForever()
    ->withPath('/')
    ->withDomain('.example.com')
    ->withSecure(true)
    ->withHttpOnly(true)
    ->withSameSite(SameSite::lax());
```

To easily work with response cookies, use the `CookiesResponse` facade.

#### Get a Response Cookie

The `get` method will return a `SetCookieCollection` instance. If no cookie by the
specified name exists, the returned `SetCookieCollection` instance will have a `null`
value.

The optional third parameter to `get` sets the value that should be used if a
cookie does not exist.

```php
use Qubus\Http\Cookies\CookiesResponse;

$setCookie = CookiesResponse::get($response, 'theme');
$setCookie = CookiesResponse::get($response, 'theme', 'simple');
```

#### Set a Response Cookie

The `set` method will either add a cookie or replace an existing cookie.

The `SetCookieCollection` primitive is used as the second argument.

```php
use Qubus\Http\Cookies\CookiesResponse;

$response = CookiesResponse::set($response, SetCookieCollection::create('token')
    ->withValue('a9s87dfz978a9')
    ->withDomain('example.com')
    ->withPath('/firewall')
);
```

#### Modify a Response Cookie

The `modify` method allows for replacing the contents of a cookie based on the
current cookie with the specified name. The third argument is a `callable` that
takes a `SetCookieCollection` instance as its first argument and is expected to return a
`SetCookieCollection` instance.

If no cookie by the specified name exists, a new `SetCookieCollection` instance with a
`null` value will be passed to the callable.

```php
use Qubus\Http\Cookies\CookiesResponse;

$modify = function (SetCookieCollection $setCookie) {
    $value = $setCookie->getValue();

    // ... inspect current $value and determine if $value should
    // change or if it can stay the same. in all cases, a cookie
    // should be returned from this callback...

    return $setCookie
        ->withValue($newValue)
        ->withExpires($newExpires);
}

$response = CookiesResponse::modify($response, 'theme', $modify);
```

#### Remove a Response Cookie

The `remove` method removes a cookie from the response if it exists.

```php
use Qubus\Http\Cookies\CookiesResponse;

$response = CookiesResponse::remove($response, 'theme');
```

#### Expire a Response Cookie

The `expire` method sets a cookie with an expiry date in the far past. This
causes the client to remove the cookie.

```php
use Qubus\Http\Cookies\CookiesResponse;

$response = CookiesResponse::expire($response, 'session_cookie');
```


FAQ
---

### Do you call `setcookies`?

No.

Delivery of the rendered `SetCookieCollection` instances is the responsibility of the
PSR-7 client implementation.


### Do you do anything with sessions?

No.

It would be possible to build session handling using cookies on top of Qubus
Cookies but it is out of scope for this package.


### Do you read from `$_COOKIES`?

No.

Qubus Cookies only pays attention to the `Cookie` headers on
[PSR-7](https://packagist.org/packages/psr/http-message) Request
instances. In the case of `ServerRequestInterface` instances, PSR-7
implementations should be including `$_COOKIES` values in the headers
so in that case Qubus Cookies may be interacting with `$_COOKIES`
indirectly.


License
-------

MIT, see LICENSE.
