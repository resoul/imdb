<p align="center">
    <a href="https://www.boxofficemojo.com" target="_blank">
        <img src="https://m.media-amazon.com/images/S/sash/l6pNvrD703JE4jf.png" height="100px">
    </a>
    <h1 align="center">IMDB parser</h1>
    <br>
</p>

Box Office Mojo parser for demo purpose only.

Installation
------------

The preferred way to install this extension is through [composer](https://getcomposer.org/download/):

```
composer require resoul/imdb
```

Basic Usage
-----------

```php
use resoul\imdb\Parser;

try {
    list ($weekend, $movies) = (new Parser())
        ->setCacheFolder('/path/to/cache/')
        ->byWeekend('2024W48')
        ->run();
} catch (Exception $e) {
    echo $e->getMessage();
}
```
