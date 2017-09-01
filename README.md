php-apidoc
==========

fork from [calinrada/php-apidoc](https://github.com/calinrada/php-apidoc)

Generate documentation for php API based application. No dependency. No framework required.

* [Requirements](#requirements)
* [Installation](#installation)
* [Usage](#usage)
* [Available Methods](#methods)
* [Preview](#preview)
* [Tips](#tips)
* [Known issues](#known-issues)
* [TODO](#todo)

### <a id="requirements"></a>Requirements

PHP >= 5.3.2

### <a id="installation"></a>Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist lesha724/yii2-notification "*"
```

or add

```
"lesha724/yii2-notification": "*"
```

to the require section of your `composer.json` file.

### <a id="usage"></a>Usage

```php
<?php

namespace Some\Namespace;

class User
{
    /**
     * @ApiDescription(section="User", description="Get information about user")
     * @ApiMethod(type="get")
     * @ApiRoute(name="/user/get/{id}")
     * @ApiParams(name="id", type="integer", nullable=false, description="User id")
     * @ApiParams(name="data", type="object", sample="{'user_id':'int','user_name':'string','profile':{'email':'string','age':'integer'}}")
     * @ApiReturnHeaders(sample="HTTP 200 OK")
     * @ApiReturn(type="object", sample="{
     *  'transaction_id':'int',
     *  'transaction_status':'string'
     * }")
     */
    public function get()
    {

    }

    /**
     * @ApiDescription(section="User", description="Create's a new user")
     * @ApiMethod(type="post")
     * @ApiRoute(name="/user/create")
     * @ApiParams(name="username", type="string", nullable=false, description="Username")
     * @ApiParams(name="email", type="string", nullable=false, description="Email")
     * @ApiParams(name="password", type="string", nullable=false, description="Password")
     * @ApiParams(name="age", type="integer", nullable=true, description="Age")
     */
    public function create()
    {

    }
}
```

Create an apidoc.php file in your project root folder as follow:


```php
# apidoc.php
<?php

use lesha724\Apidoc\Builder;
use lesha724\Apidoc\Exception;

$classes = array(
    'Some\Namespace\User',
    'Some\Namespace\OtherClass',
);

$output_dir  = __DIR__.'/apidocs';
$output_file = 'api.html'; // defaults to index.html
$template_path = __DIR__.'/Resources/views/template/index.html';

try {
    $config =  new Config();
    $config->version = '0.0.1';
    $config->output_dir = $output_dir;
    $config->title = "ApiTitle";
    $config->st_classes = $classes;
    $config->output_file = $output_file;
    $config->template_path = $template_path;

    $builder = new Builder($config);
    $builder->generate();
} catch (Exception $e) {
    echo 'There was an error generating the documentation: ', $e->getMessage();
}

```

Then, execute it via CLI

```php
$ php apidoc.php
```

### <a id="methods"></a>Available Methods

Here is the list of methods available so far :

* @ApiDescription(section="...", description="...")
* @ApiMethod(type="(get|post|put|delete|patch")
* @ApiRoute(name="...")
* @ApiParams(name="...", type="...", nullable=..., description="...", [sample=".."])
* @ApiHeaders(name="...", type="...", nullable=..., description="...")
* @ApiReturnHeaders(sample="...")
* @ApiReturn(type="...", sample="...")
* @ApiBody(sample="...")

### <a id="preview"></a>Preview

You can see a dummy generated documentation on http://calinrada.github.io/php-apidoc/

### <a id="tips"></a>Tips

To generate complex object sample input, use the ApiParam "type=(object|array(object)|array)":

```php
* @ApiParams(name="data", type="object", sample="{'user_id':'int','profile':{'email':'string','age':'integer'}}")
```

### <a id="knownissues"></a>Known issues

I don't know any, but please tell me if you find something. PS: I have tested it only in Chrome !

### <a id="todo"></a>TODO

* Implement options for JSONP
* Implement "add fields" option

