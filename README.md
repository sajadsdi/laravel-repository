![Advanced Laravel Repository](https://sajadsdi.github.io/images/laravel-advanced-repository.jpg)

# Advanced Laravel Repository

Advanced helper for implement repository pattern for Laravel to provides a robust set of tools to enhance your Eloquent
models. Aimed to abstract database layer complexity and allows you to standardize and reuse your database query logics.

## Features

- **Model Agnosticism**: Can be implemented for any Eloquent model.
- **Method Forwarding**: Enables dynamic method calls on the model or query builder.
- **Auto Query Scope** : You can define scope methods on your repository without any param depend on. 
- **Flexible Search**: Search across one or more model attributes easily.
- **Advanced Filtering**: Apply complex filtering logic including range and pattern matching.
- **Dynamic Sorting**: Apply Order by multiple columns with different sorting strategies.
- **Pagination**: Integrate Laravel's pagination with additional query features.
- **Advanced Joining**: Easy defined relations without use model relations for joining.
- **CRUD tools** : Easy implementation `CRUD` methods.

## Installation

To use this package, require it via Composer:

```bash
composer require sajadsdi/laravel-repository
```

After installing, you should extend the main `Repository` class in each repository that you wish to create a repository for Eloquent model.

## Usage

### Extending the Repository

Create a new repository by extending the `Repository` class:

```php
use Sajadsdi\LaravelRepository\Repository;

class UserRepository extends Repository implements UserRepositoryInterface
{
    // Implement abstract methods
}
```

### Implementing Abstract Methods

Each child repository must implement the following methods to define the model and its searchable, filterable, and
sortable attributes:

```php
public function getModelName(): string;
public function getSearchable(): array;
public function getFilterable(): array;
public function getSortable(): array;
```

For example:

```php
class UserRepository extends Repository implements UserRepositoryInterface
{
    public function getModelName(): string 
    {
        return User::class;
    }

    public function getSearchable(): array 
    {
        return ['name', 'email'];
    }
    
    public function getFilterable(): array 
    {
        return ['name', 'email', 'created_at'];
    }
    
    public function getSortable(): array 
    {
        return ['name', 'email', 'created_at'];
    }

    // other methods...
}
```
### Auto Query Scope
In your repository, it is possible to implement scope methods that are independent of any parameters.

for example : 
```php
class UserRepository extends Repository implements UserRepositoryInterface
{
    // abstract methods...
    
    public function getVerified()
    {
        return $this->whereNotNull('verified_at');
    }
    
    public function getByName(string $name)
    {
        return $this->where('name','LIKE' , '%'.$name.'%');
    }
    
    public function getVerifiedUsersByName(string $name)
    {
        return $this->getVerified()->getByName($name);
    }
    
    // other methods...
}
```

### Searching, Sorting, and Filtering

Execute search, sort, and filter operations:

```php

//That's right, you need to inject your repository with the
//interface in the controller or each class you need.

//In this example, we create an instance of the repository
//to convey the concept.
//But it is not the right thing for real projects!
$userRepo = new UserRepository();

// Search by name or email
$users = $userRepo->search('John')->get();

// Sort by name in descending order
$users = $userRepo->sort('name:desc')->get();

// Filter between IDs
$users = $userRepo->filter('id:between_1,10')->get();
```

### The filter string :
uses a special syntax, such as:
- `id:equal_1` for equality.
- `name:like_john` for like condition.
- `price:between_100,200` for range filtering.
- `id:in_2,3,4` for checking if a column value equal 2 or 3 or 4.
- `price:upper_200` for upper range filtering.
- `price:lower_200` for lower range filtering.
- `status:is_null` for checking if a column is NULL.
- `status:is_not-null` for checking if a column is NOT NULL,
- `id:not_in_2,3,4` for checking if a column value not equal 2 and 3 and 4.
- `id:not_between_2,6` for checking if a column is not in range 2 to 6.
- `name:not_like_john` for not like condition.
- `id:not_equal_2` for not equal condition.
- `price:not_upper_500` for not upper range filtering. 
- `price:not_lower_200` for not lower range filtering. 

### Use Multiple Filters and Sort
`@` is used for separating multiple filter and sort conditions.
```php
// multiple sort 
$users = $userRepo->sort('name:desc@id:asc')->get();

// multiple filter
$users = $userRepo->filter('id:in_1,10@status:is_null')->get();
```

### Pagination

Leverage Laravel's pagination with added query capabilities:

```php
$users = $userRepo->search('John')->filter('status:is_null')->sort('id:desc')->paginate(10);
```

### Advanced Joining

The join feature is designed to allow complex chaining of tables with precision and flexibility. Here's how to utilize the `joinable` property to define the relationships within your repository:

```php
protected $joinable = [
    'relationName' => [
        'rel' => [
            'table1.field1' => 'table2.field2',
            'table2.field3' => 'table3.field4',
        ],
        'select'     => ['table2.field_x as x', 'table3.field_y as y'],
        'filterable' => ['x', 'y', 'field_z'],
        'sortable'   => ['x', 'y', 'field_z'],
        'soft_delete'=> ['table2', 'table3']
    ],
];
```
You don’t need to fill in all options; only configure them based on your needs.
Customize the `joinable` property to suit the needs of your application by adjusting each component:

#### `rel`:
The join conditions between your primary table and related tables are specified within the `rel` array. It's the cornerstone of setting up your joins, determining how tables are interrelated throughout your query.

- **Single Join:**
  To relate two tables, specify the field from the primary table and the corresponding field from the table you wish to join.

```php
'rel' => [
    'table1.field1' => 'table2.field2',
],
```

- **Multiple Joins:**
  When your query involves multiple tables, chain the joins by listing the field relationships consecutively. The key represents the field from the primary table, or the last joined table, while the value represents the field from the next table to join.

```php
'rel' => [
    'table1.field1' => 'table2.field2',
    'table2.field3' => 'table3.field4',
    // Extend the chain with additional table joins as necessary
],
```
This pattern facilitates the creation of a series of joins, where `table1` is the primary table related to your repository, and `table2`, `table3`, etc., are the tables being joined in sequence. Each join extends the capability for further filtering, selection, and sorting across multiple tables, giving you substantial control over the final query output.

#### `select`:
Determine which columns to select from the joined tables. Aliases help distinguish between columns when names are shared across tables or when a more descriptive name is preferred.
```php
'select' => [
    // All visible fields from the main table are automatically selected.
    
    'table2.field_x as x',  // Specific field with a clear alias
    'table3.field_y as y',  // Another field with its own alias
    // Add more fields and aliases accordingly
],
```

#### `filterable` and `sortable`:
These arrays specify which fields or aliases from the ‘select’ clause can be used in filtering and sorting operations. The fields listed here should either be aliases defined in ‘select’ or belong to the final table in the join sequence.
```php
'filterable' => [
    'x',        // Alias defined in `select`
    'y',        // Another alias defined in `select`
    'field_z',  // Field from the final table in the join chain
    // Add more filterable fields as needed
],
'sortable' => [
    'x',        // Alias that's sortable
    'y',        // Another sortable alias
    'field_z',  // Field from the final table (`table3`) that's sortable
    // Add more sortable fields as required
],
```
#### `softDeletes`:
Specifies table, other than the base repository’s model table, that should exclude soft deleted records in join operations. The base table’s soft delete status is acknowledged inherently by the model and does not need to be listed.
```php
'softDeletes' => [
    'table2',  // Related table with soft-delete enabled
    'table3',  // Another related table with soft-delete
    // List additional related tables with soft-delete enabled as necessary
],
```
This approach ensures a cohesive querying experience, allowing for powerful querying capabilities while respecting soft delete states.
#### Usage of filter and sort with `joinable` relationships

Once you have defined your relationships in the `joinable` configuration, you can effortlessly filter and sort through related models using the `filter` and `sort` methods. Here's an example of how to use these methods to query user data with conditions and sorting:

```php

// Apply filters and sorting on the related models
$users = $userRepo->filter('relationName.x:is_null@relationName.y:lower_100')
                   ->sort('relationName.field_z:desc')
                   ->paginate(10);

// This will fetch users with the following conditions applied:
// - For the related model under 'relationName':
//   - Field 'x' should be `null` (is_null condition).
//   - Field 'y' should be less than or equal to 100 (lower_100 condition).
// - The resulting users will then be sorted in descending order
//   based on field 'field_z' from the related model.
// - The results will be paginated, returning 10 users per page.
```
Make sure that your relations are properly defined in the joinable array and the associated fields are mentioned in filterable and sortable configurations. This ensures that the filtering and sorting logic is applied correctly across your database queries.

### CRUD tools
Methods used for CRUD operations are usually repeated in repositories! To prevent this repetition, you can use the interfaces and traits that are available in the package And if necessary, you can override the methods.

#### use `CrudRepositoryInterface` and `Crud` trait:
These tools use for all `Read` and `Write` operations.
You can implement crud interface in repository:
```php
class UserRepository extends Repository implements CrudRepositoryInterface,UserRepositoryInterface
{
    use Crud;
    
    // other methods...
}
```
You can extend crud interface: 

```php
interface UserRepositoryInterface extends CrudRepositoryInterface
{
    //you methods...
}


class UserRepository extends Repository implements UserRepositoryInterface
{
    use Crud;
    
    // other methods...
}
```
Or you can create a base repository class to apply on all repositories.

Of course, you can use the interfaces and traits that are special for write or read separately.
```php
interface UserReadRepositoryInterface extends ReadCrudRepositoryInterface
{
    //you methods...
}


class UserReadRepository extends Repository implements UserReadRepositoryInterface
{
    use ReadCrud;
    
    // other methods...
}

//OR

interface UserWriteRepositoryInterface extends WriteCrudRepositoryInterface
{
    //you methods...
}


class UserWriteRepository extends Repository implements UserWriteRepositoryInterface
{
    use WriteCrud;
    
    // other methods...
}
```

### Method Naming in Repository Classes
When you need to define a method in your repository with the same name as an Eloquent method, use `$this->query()` to avoid conflicts. This approach allows you to safely leverage Eloquent’s functionality.

For a create method:
```php
public function create(array $data)
{
    // Call the `create` method on the query builder provided by `$this->query()`
    return $this->query()->create($data);
}
```
This will use the query builder’s create method directly.


### A simple implementation of repository pattern for Eloquent :
Suppose we define a repository and interface as follows

```php
interface UserRepositoryInterface
{
    public function getAll(string $search = null, string $filter = null, string $sort = null, int $perPage = 15);
    
    public function getProfilePic(int $userId);
    
    public function getUserWithAllRelations(int $userId);
}



class UserRepository extends Repository implements UserRepositoryInterface
{

    protected $joinable = [
        'activities' => [
            'rel' => [
                'users.id' => 'user_activities.user_id',
            ],
            'select'     => ['user_activities.created_at as activity_time', 'user_activities.type as activity_name'],
            'filterable' => ['activity_time', 'activity_name'],
            'sortable'   => ['activity_time', 'activity_name'],
            'soft_delete'=> ['user_activities']
        ],
        
        'profile' => [
            'rel' => [
                'users.pic_id' => 'user_pictures.id',
            ],
            'select'     => ['user_pictures.path as photo'],
            'filterable' => ['photo'],
            'sortable'   => ['photo'],
            'soft_delete'=> ['user_pictures']
        ],
    ];
    
    public function getModelName(): string 
    {
        return User::class;
    }

    public function getSearchable(): array 
    {
        return ['name', 'email'];
    }
    
    public function getFilterable(): array 
    {
        return ['name', 'email', 'created_at'];
    }
    
    public function getSortable(): array 
    {
        return ['name', 'email', 'created_at'];
    }

    //you can use this method for index api on controller
    //if needed, filter and sort methods call automatically join method.
    
    public function getAll(string $search = null, string $filter = null, string $sort = null, int $perPage = 15)
    {
        return $this->search($search)->filter($filter)->sort($sort)->paginate($perPage);
        
        // if you need join in all results,you can use `join` or `joins` in begin of the query like:
        //return $this->joins(['activities','profile'])->search($search)->filter($filter)->... 
    }
    
    //you can use join method with relation name, without filter or sort method
    public function getProfilePic(int $userId)
    {
        $user = $this->join('profile')->where('users.id',$userId)->first();
        
        return $user?->photo ?? 'path/to/no-profile.png';
    }
    
    //You can use multiple join on relations defied on joinable, without filter or sort method
    public function getUserWithAllRelations(int $userId)
    {
        return $this->joins(['activities','profile'])->find($userId);
    }
}
```
After defined repository and interface You need to bind these files in the `AppServiceProvider` :
```php
    public function register():void
    {
        //other bindings ...
        //..
        //.
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }
```

Now you can use this repository in your controller like below:

```php
class UserController extends Controller
{

    private UserRepositoryInterface $repo;
    
    public function __construct(UserRepositoryInterface $userRepo) 
    {
        $this->repo = $userRepo;
    }
    
    public function index(Request $request)
    {
        $users = $this->repo->getAll(
            $request?->search,
            $request?->filter,
            $request?->sort
        );
        
        return response($users);
    }

} 
```
After set controller and index method on your router (e.g. GET http://127.0.0.1/api/v1/admin/users)

Now your front-end can call API with `search` , `filter` , `sort` query param like this:

```bash
http://127.0.0.1/api/v1/admin/users/?search=john&filter=id:upper_5@activities.activity_name:equal_comment@profile.photo:is_not-null&sort=activities.activity_time:desc
```
This is very simple...

### Advanced implementation of repository pattern for Eloquent :
Some individuals consider using the repository pattern for Eloquent Laravel to be superfluous or even mistaken. They argue that this pattern undermines SOLID principles, and indeed this is true.

To implement this pattern for Eloquent, we must disregard the notion that “the ORM may change later.” The next step is to separate reads and writes! We do this by creating one repository for methods intended for reads and another for methods intended for writes.
In this way, each repository is created for a specific purpose, and it also brings us benefits, one of which is:
Imagine a project with a large scale where we need to separate database connections for reads and writes. With this pattern, it’s quite simple to define these connections within the repository itself so that each repository has its own corresponding connection!

### Contributing

We welcome contributions from the community to improve and extend this library. If you'd like to contribute, please follow these steps:

1. Fork the repository on GitHub.
2. Clone your fork locally.
3. Create a new branch for your feature or bug fix.
4. Make your changes and commit them with clear, concise commit messages.
5. Push your changes to your fork on GitHub.
6. Submit a pull request to the main repository.

### Reporting Bugs and Security Issues

If you discover any security vulnerabilities or bugs in this project, please let us know through the following channels:

- **GitHub Issues**: You can [open an issue](https://github.com/sajadsdi/laravel-repository/issues) on our GitHub repository to report bugs or security concerns. Please provide as much detail as possible, including steps to reproduce the issue.

- **Contact**: For sensitive security-related issues, you can contact us directly through the following contact channels

### Contact

If you have any questions, suggestions, financial, or if you'd like to contribute to this project, please feel free to contact the maintainer:

- Email: thunder11like@gmail.com

We appreciate your feedback, support, and any financial contributions that help us maintain and improve this project.


## License

The Advanced Laravel Repository Package is open-sourced software licensed under the [MIT license](LICENSE).
