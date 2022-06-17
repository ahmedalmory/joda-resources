# Very short description of the package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ahmedalmory/joda-resources.svg?style=flat-square)](https://packagist.org/packages/ahmedalmory/joda-resources)
[![Total Downloads](https://img.shields.io/packagist/dt/ahmedalmory/joda-resources.svg?style=flat-square)](https://packagist.org/packages/ahmedalmory/joda-resources)

a trait that generates resources methods for controller.

## Installation

You can install the package via composer:

```bash
composer require ahmedalmory/joda-resources
```

## Usage
### JodaResource

web.php
```php
Route::resource('/users', 'UserController');
```

UserController.php
```php
<?php

// any thing after "controllers" in namespace would be prefixed to view and route properties
// Ex. namespace App\Http\Controllers\Admin;
// View would be admin.user
// route would be admin.users
namespace App\Http\Controllers;

use AhmedAlmory\JodaResources\JodaResource;
use App\Models\User;

class UserController extends Controller
{

     use JodaResource;

     // model that will be used for crud operations
     protected $model = User::class;
     // JodaResources will try to find a model with the name User in App\Models, App\ or App\Model


     // will be used in store and update validation in case storeRules or updateRules are not set
     protected $rules = ['name' => 'required', 'email' => 'sometimes'];
     // required either in the controller or the model

    // will be used for store validation, if set
    // public static $storeRules =[];

    // will be used for update validation , if set
    // public static $updateRules =[];


     // optional
     // will be the name of the model in lower case if not set in this example 'user'
     protected $name = 'user';
     // name of the model that will be used in views ans routes in case there are not set


     // optional
     // will be the name of the model (in kebab case in case more than one word) if not set in this example 'user'
     protected $view = 'user';
     // name of the model that will be used in returned views


     /// optional
     // will be plural of the name property (in kebab case in case more than one word) if not set in this example 'users'
     protected $route = 'users';
     // name of the model that will be used in returned routes after finishing the operation


     // optional
     protected $files = ['photo'];
     // items will be uploaded from the request in case there is file with the same name
     // files will be saved in /uploads/{pluralNameOfTheModel} with name {user_id}-{time}.{ext}
     // ex uploads/users/1-1624479228.jpg
     // file will be deleted automatically upon deleting the object
     
     // true by default
     prtected $filterQueryString = true;

     // add custom query
     public function query($query)
     {
         return $query->whereNotNull('another_filed')->get();
     }
}

//methods will be provided

//index => will return view($view) with three variables, 'route' route of the resource to be used in actions, 'index' (all users) and plural name of the model in this example 'users' you can use either of them

//create => will return view($view.create)

//store => will save all cols from request then return to $route.index

//show => will return view($view.show ) with two variables, 'show' and name of the model in this example 'user' you can use either of them

//edit => will return view($view.edit) with tow variables, 'edit' and name of the model in this example 'user' you can use either of them

//update => will update all cols from request then return to $route.index

//destroy => will save all cols from request then return to $route.index
```

### JodaApiResource

JodaApiResource has the same options that JodaResource has above

api.php
```php
Route::resource('/examples', 'ExampleController');
```
ExampleController.php
```php
namespace App\Http\Controllers\Api;

use AhmedAlmory\JodaResources\JodaApiResource;

class ExampleController extends Controller
{
 use JodaApiResource;

    protected $rules = [
        'filed' =>'required',
        'another_filed' => 'sometimes'
    ];
}
```

index => get => example.com/api/examples?filed=queryStringExample

store => post => example.com/api/examples

show => get => example.com/api/examples/1

update => put => example.com/api/examples/1

destroy => delete => example.com/api/examples/1

## For customisation

There are methods for customisation like
beforeStore() that be fired right before storing data to data base,
afterStore() that be fired right after storing data, for instance you could change flash message or redirect to some other page,
and the same for update and destroy,
beforeUpdate(),
afterUpdate(),
beforeDestroy(),
afterDestroy(),

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email ahmedalmory02@gmail.com instead of using the issue tracker.

## Credits

- [Ahmed Joda](https://github.com/ahmedalmory)
- [Ahmed Tofaha](https://github.com/ahmedtofaha10)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
