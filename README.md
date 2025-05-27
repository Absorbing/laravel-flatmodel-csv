# FlatModel - CSV

Laravel FlatModel - CSV is an Eloquent-inspired data modeling system for Laravel that works with CSV files.

It provides an expressive, familiar API for reading and writing flat data sources without relying on a database.

## Installation

Install with Composer into a new or existing Laravel project

```bash
composer require flatmodel/laravel-csv-flatmodel
```

## Usage

A new CSV model can be made by running the artisan command which will prompt for a few details.

```bash
php artisan make:csv-model CsvModel
```

Once the model is generated, it can be used similarly to standard models within Laravel

```php
use App\Models\CsvModel

$model = (new CsvModel)
        ->where('active', true)
        ->pluck('id');
```

The methods are returned as instances of the `Illuminate\Support\Collection` class that those familiar with Laravel will
be comfortable with. This also means that working with the data returned is simple and straightforward.

For interacting and querying data from the model, the following are available;

- `where()`
- `first()`
- `pluck()`
- `get()`
- `select()`
- `value()`

The models also have a series of configurable parameters that will enable or disable functionality.

| Property         | Type    | Description                                                                                                                                                                    | Default |
|------------------|---------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------|
| `$path`          | string  | The path of the file                                                                                                                                                           |         |
| `$delimiter`     | string  | The delimiter used within the file                                                                                                                                             | `,`     |
| `$enclosure`     | string  | The enclosure character used to wrap field values in the file                                                                                                                  | `"`     |
| `$escape`        | string  | The escape character used in the file                                                                                                                                          | `\`     |
| `$stream`        | boolean | Indicates whether the model operates in stream mode                                                                                                                            | `false` |
| `$headers`       | array   | Array of column headers from the CSV file, if not provided will try to autodetect from file                                                                                    | `[]`    |
| `$strictHeaders` | boolean | Enables or disables strict header checking                                                                                                                                     | `false` |
| `$cast`          | array   | Defines type casting rules for columns                                                                                                                                         | `[]`    |
| `$writable`      | boolean | Indicates whether the model is writable, if true the model can be used to write data back to the file. If false, the model is read-only                                        | `false` |
| `$appendOnly`    | boolean | Indicates whether the model is append-only. If true the model will only have data added to the end of the file, updates cannot be written to models configured for append-only | `false` |
| `$enableBackup`  | boolean | Enables or disables automatic backups on modification of the file                                                                                                              | `false` |
| `$autoFlush`     | boolean | Indicates whether the model should flush the data to the CSV file on every modification                                                                                        | `false` |

> [!IMPORTANT]
> If `$autoFlush` is set to `false` (as it is by default), `flush()` should be manually called to persist the data to
> the file.

## Writing, Mutating & Flushing Data

Writable models can be modified and saved back to the file using `flush()` or `save()` if `$autoFlush` is disabled by
using `false`.

```php
$model = new CsvModel;

$model->insert(['id' => 5, 'name' => 'Alex']);
$model->flush(); // Writes changes to file
```

Updates and deletes are also possible using similarly named methods using functional filtering.

```php
$model = new CsvModel;

// Update a matching row
$model->update(
    fn($row) => $row['id'] === 5,
    fn($row) => [...$row, 'name' => 'Alexa']
);

// Upsert: update if found, insert if not
$model->upsert(
    fn($row) => $row['id'] === 10,
    fn($row) => ['id' => 10, 'name' => 'New User']
);

// Delete matching rows
$model->delete(fn($row) => $row['id'] === 2);

// Save changes to disk
$model->flush();
```

Models can also be updated using more familiar array-based parameters.

```php
$model->updateWhere(['id' => 5], ['name' => 'Alexa']);
$model->upsertWhere(['id' => 10], ['id' => 10, 'name' => 'New User']);
$model->deleteWhere(['id' => 2]);
```

> [!WARNING]
> If the model is in append-only mode, updates, upserts and deletes will throw an exception.

## Exception Handling

FlatModel uses custom exceptions to provide clear and understandable error context all extending a common base exception
of `FlatModelException`.

| Exception                      | Description                                                      |
|--------------------------------|------------------------------------------------------------------|
| `AppendOnlyViolationException` | When updating or deleting a model flagged as append-only         |
| `BackupFailedException`        | When a backup fails prior to committing any changes to the file  |
| `CastingException`             | When a type casting operation fails for a column value           |
| `ColumnNotFoundException`      | When attempting to access a column that doesn't exist in the CSV |
| `FileNotFoundException`        | When the specified CSV file cannot be found                      |
| `FileWriteException`           | When writing changes to the CSV file fails                       |
| `HeaderMismatchException`      | When headers don't match expected values in strict mode          |
| `InvalidHandleException`       | When attempting to read or write to an invalid file handle       |
| `InvalidRowFormatException`    | When a row doesn't match the expected format                     |
| `MissingHeaderException`       | When required headers are missing from the CSV                   |
| `PrimaryKeyMissingException`   | When a primary key operation is attempted without a defined key  |
| `StreamOpenException`          | When opening the CSV file in stream mode fails                   |
| `WriteNotAllowedException`     | When attempting to write to a read-only model                    |

## Testing

Run PHPUnit from the root of your Laravel project or your package repo:

```bash
php artisan test
```

## License

FlatModel is open-sourced software licensed under the [MIT license](LICENSE).