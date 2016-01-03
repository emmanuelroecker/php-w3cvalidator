# php-w3cvalidator

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/emmanuelroecker/php-w3cvalidator/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/emmanuelroecker/php-w3cvalidator/?branch=master)
[![Build Status](https://travis-ci.org/emmanuelroecker/php-w3cvalidator.svg?branch=master)](https://travis-ci.org/emmanuelroecker/php-w3cvalidator)
[![Coverage Status](https://coveralls.io/repos/emmanuelroecker/php-w3cvalidator/badge.svg?branch=master&service=github)](https://coveralls.io/github/emmanuelroecker/php-w3cvalidator?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/66e60d74-1f4c-489e-8d9d-4e5bd78c7cbc/mini.png)](https://insight.sensiolabs.com/projects/66e60d74-1f4c-489e-8d9d-4e5bd78c7cbc)

Validate html and css files using [w3c markup validation](http://validator.w3.org/) and [w3c css validation](http://jigsaw.w3.org/css-validator/).

## Installation

This library can be found on [Packagist](https://packagist.org/packages/glicer/w3c-validator).

The recommended way to install is through [composer](http://getcomposer.org).

Edit your `composer.json` and add:

```json
{
    "require": {
      "glicer/w3c-validator": "dev-master"
    }
}
```

And install dependencies:

```bash
php composer.phar install
```

## Example

```php
     <?php
     // Must point to composer's autoload file.
     require 'vendor/autoload.php';

    use Symfony\Component\Finder\SplFileInfo;
    use Symfony\Component\Finder\Finder;
    use GlValidator\GlW3CValidator;

    //create validator with directory destination of reports
    $validator = new GlW3CValidator(__DIR__ . "/result");

    //list of files to validate, it can be a Finder Symfony Object
    $finder = new Finder();

    //all files in entry directory
    $files  = $finder->files()->in(__DIR__ . "/entry/");

     //add glicer.css and glicer.html
    $files  = [$files, __DIR__ . "/glicer.css", __DIR__ . "/glicer.html"];

    //return array of reports path in html format
    $results = $validator->validate(
                                    $files,
                                    ['html', 'css'],  //validate html and css files
                                    function (SplFileInfo $file) { //callback function
                                            echo $file->getRealpath();
                                    }
                                    );

```

In this example, you can view reports result/w3c_css_glicer.html, result/w3c_html_glicer.html, result/... in your browser.


## Use html validator offline

Docker must be installed

```bash
    docker pull magnetikonline/html5validator
    docker run -d -p 8080:80 -p 8888:8888 magnetikonline/html5validator
```

Validator nu Java server on port 8888

Pass url of validator nu to constructor :

```php
    $validator = new GlW3CValidator(__DIR__ . "/result","http://127.0.0.1:8888");
```

## Running Tests

You must be online

Launch from command line :

```console
vendor\bin\phpunit
```

## License MIT

## Contact

Authors : Emmanuel ROECKER & Rym BOUCHAGOUR

[Web Development Blog - http://dev.glicer.com](http://dev.glicer.com/)

