<?php

namespace TrueFrame\AI;

use TrueFrame\Application;
use TrueFrame\Console\Application as ConsoleApplication;
use TrueFrame\Database\Schema\Blueprint;
use TrueFrame\Database\Schema;
use RuntimeException;
use Exception;

class NullScaffolder implements ScaffolderInterface
{
    protected Application $app;
    protected ConsoleApplication $console;

    public function __construct(Application $app, ConsoleApplication $console)
    {
        $this->app = $app;
        $this->console = $console;
    }

    /**
     * Scaffold a resource (model, controller, migration, views, routes).
     * This is a basic stub implementation without actual AI.
     *
     * @param string $name The name of the resource (e.g., 'Post').
     * @param array<string, string> $fields Associative array of field names and types (e.g., ['title' => 'string', 'body' => 'text']).
     * @param array<string, mixed> $flags Command line options/flags (e.g., ['--crud' => true, '--api' => true]).
     * @return void
     */
    public function scaffold(string $name, array $fields, array $flags): void
    {
        $singularName = ucfirst($name);
        $pluralName = strtolower($name) . 's'; // Simple pluralization

        $this->console->line("Scaffolding {$singularName}...");

        // Generate FormRequest name if CRUD/API
        $formRequestName = $singularName . 'Request';
        if (isset($flags['--crud']) || isset($flags['--api'])) {
            $this->makeRequest($formRequestName, $fields);
        }

        // 1. Make Model
        $this->makeModel($singularName, $pluralName, $fields);

        // 2. Make Migration
        $migrationName = 'create_' . strtolower($pluralName) . '_table';
        $this->makeMigration($migrationName, $singularName, $pluralName, $fields);

        // 3. Make Controller (if --crud or --api is present)
        if (isset($flags['--crud']) || isset($flags['--api'])) {
            $this->makeController($singularName, $pluralName, $formRequestName);
        }

        // 4. Make Views (if --views is present)
        if (isset($flags['--views'])) {
            $this->makeViews($singularName, $pluralName, $fields);
        }

        // 5. Add Routes (if --crud or --api is present)
        if (isset($flags['--crud'])) {
            $this->addWebRoutes($singularName, $pluralName);
        }
        if (isset($flags['--api'])) {
            $this->addApiRoutes($singularName, $pluralName);
        }

        $this->console->info("Scaffolding for '{$singularName}' completed (using NullScaffolder).");
    }

    /**
     * Generate a FormRequest file.
     *
     * @param string $name
     * @param array $fields
     * @return void
     */
    protected function makeRequest(string $name, array $fields): void
    {
        $rules = [];
        foreach ($fields as $field => $type) {
            $rule = ['required']; // Default to required
            if ($type === 'string' || $type === 'text' || $type === 'uuid') {
                $rule[] = 'string';
            }
            if ($type === 'int' || $type === 'integer' || $type === 'unsignedBigInteger') {
                $rule[] = 'int';
            }
            if ($type === 'email') {
                $rule[] = 'email';
            }
            if ($type === 'uuid') {
                $rule[] = 'uuid';
            }
            // Add other basic rules as needed
            $rules[$field] = implode('|', $rule);
        }

        $rulesString = var_export($rules, true);
        $rulesString = str_replace("'", "\'", $rulesString); // Escape single quotes for stub
        $rulesString = preg_replace("/^array\s+\(/", "[", $rulesString);
        $rulesString = preg_replace("/\)$/", "]", $rulesString);
        $rulesString = preg_replace("/\n\s+/", "\n            ", $rulesString);


        $stub = file_get_contents(__DIR__ . "/../Console/Commands/stubs/request.stub");
        $content = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ rules }}'],
            ['App\\Http\\Requests', $name, $rulesString],
            $stub
        );

        $path = $this->app->basePath("app/Http/Requests/{$name}.php");
        file_put_contents($path, $content);
        $this->console->line("Created App/Http/Requests/{$name}.php");
    }

    /**
     * Generate a model file.
     *
     * @param string $name
     * @param string $pluralName
     * @param array $fields
     * @return void
     */
    protected function makeModel(string $name, string $pluralName, array $fields): void
    {
        $table = strtolower($pluralName);
        $fillable = array_keys($fields);
        $casts = [];

        foreach ($fields as $field => $type) {
            $casts[$field] = match ($type) {
                'int', 'integer', 'unsignedBigInteger' => 'int',
                'float', 'double' => 'float',
                'boolean' => 'bool',
                'datetime', 'timestamp' => 'datetime',
                'date' => 'date',
                default => null,
            };
            if (is_null($casts[$field])) {
                unset($casts[$field]); // Remove null casts
            }
        }

        $fillableString = "'" . implode("', '", $fillable) . "'";
        $castsString = var_export($casts, true);
        $castsString = preg_replace("/^array\s+\(/", "[", $castsString);
        $castsString = preg_replace("/\)$/", "]", $castsString);
        $castsString = preg_replace("/\n\s+/", "\n        ", $castsString);


        $stub = file_get_contents(__DIR__ . "/../Console/Commands/stubs/model.stub");
        $content = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ table }}', '{{ fillable }}', '{{ casts }}'],
            ['App\\Models', $name, $table, $fillableString, $castsString],
            $stub
        );

        $path = $this->app->basePath("app/Models/{$name}.php");
        file_put_contents($path, $content);
        $this->console->line("Created App/Models/{$name}.php");
    }

    /**
     * Generate a migration file.
     *
     * @param string $migrationName
     * @param string $modelName
     * @param string $tableName
     * @param array $fields
     * @return void
     */
    protected function makeMigration(string $migrationName, string $modelName, string $tableName, array $fields): void
    {
        $date = new \DateTime();
        $fileName = $date->format('Y_m_d_His') . '_' . $migrationName;
        $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $migrationName)));

        $stub = file_get_contents(__DIR__ . "/../Console/Commands/stubs/migration.stub");
        $content = str_replace(['{{ class }}', '{{ table }}'], [$className, $tableName], $stub);

        // Add fields to migration
        $fieldSchema = [];
        foreach ($fields as $field => $type) {
            $fieldSchema[] = $this->mapFieldToSchema($field, $type);
        }
        $fieldSchemaString = implode("\n            ", $fieldSchema);
        $content = str_replace('// $table->string(\'name\');', $fieldSchemaString, $content);

        $path = $this->app->basePath("database/migrations/{$fileName}.php");
        file_put_contents($path, $content);
        $this->console->line("Created database/migrations/{$fileName}.php");
    }

    /**
     * Map field type to Schema Blueprint method.
     *
     * @param string $field
     * @param string $type
     * @return string
     */
    protected function mapFieldToSchema(string $field, string $type): string
    {
        $schemaLine = match ($type) {
            'string' => "\$table->string('{$field}')",
            'text' => "\$table->text('{$field}')",
            'int', 'integer' => "\$table->integer('{$field}')",
            'unsignedBigInteger' => "\$table->unsignedBigInteger('{$field}')",
            'uuid' => "\$table->uuid('{$field}')",
            'boolean' => "\$table->boolean('{$field}')",
            'float' => "\$table->float('{$field}')",
            'double' => "\$table->double('{$field}')",
            'date' => "\$table->date('{$field}')",
            'datetime' => "\$table->dateTime('{$field}')",
            'timestamp' => "\$table->timestamp('{$field}')",
            'email' => "\$table->string('{$field}')->unique()", // Common for email
            default => "\$table->string('{$field}')",
        };

        // Add foreign key constraint if it's an ID field and not the primary key
        if (str_ends_with($field, '_id') && $field !== 'id' && $type === 'unsignedBigInteger') {
            $referencedTable = str_replace('_id', 's', $field); // user_id -> users
            $schemaLine .= "->constrained('{$referencedTable}')";
        } elseif ($type === 'uuid' && $field !== 'id') {
            $schemaLine .= "->unique()"; // UUIDs are often unique
        }

        return $schemaLine . ';';
    }

    /**
     * Generate a controller file.
     *
     * @param string $name
     * @param string $pluralName
     * @param string $formRequestName
     * @return void
     */
    protected function makeController(string $name, string $pluralName, string $formRequestName): void
    {
        $modelNamespace = 'App\\Models\\' . $name;
        $formRequestNamespace = 'App\\Http\\Requests\\' . $formRequestName;

        $stub = file_get_contents(__DIR__ . "/../Console/Commands/stubs/controller.resource.stub");
        $content = str_replace(
            [
                '{{ namespace }}',
                '{{ class }}',
                '{{ modelName }}',
                '{{ modelNamespace }}',
                '{{ formRequestName }}',
                '{{ formRequestNamespace }}',
                '{{ resourcePlural }}',
                '{{ resourceSingularLower }}'
            ],
            [
                'App\\Controllers',
                $name . 'Controller',
                $name,
                $modelNamespace,
                $formRequestName,
                $formRequestNamespace,
                $pluralName,
                strtolower($name)
            ],
            $stub
        );

        $path = $this->app->basePath("app/Controllers/{$name}Controller.php");
        file_put_contents($path, $content);
        $this->console->line("Created App/Controllers/{$name}Controller.php");
    }

    /**
     * Generate view files.
     *
     * @param string $name
     * @param string $pluralName
     * @param array $fields
     * @return void
     */
    protected function makeViews(string $name, string $pluralName, array $fields): void
    {
        $viewPath = $this->app->basePath("resources/views/{$pluralName}");
        if (!is_dir($viewPath)) {
            mkdir($viewPath, 0755, true);
        }

        // --- Index View ---
        $indexTableHeaders = [];
        $indexTableRows = [];
        foreach ($fields as $field => $type) {
            $indexTableHeaders[] = "          <th class=\"px-4 py-2 text-left\">" . ucfirst($field) . "</th>";
            $indexTableRows[] = "          <td class=\"border px-4 py-2\">{{ \${$pluralName}Item->{$field} }}</td>";
        }
        $indexTableHeaders[] = "          <th class=\"px-4 py-2 text-left\">Actions</th>";
        $indexTableRows[] = "          <td class=\"border px-4 py-2\">\n            <a href=\"/{$pluralName}/{{ \${$pluralName}Item->id }}\" class=\"text-blue-600 hover:underline\">Show</a> |\n            <a href=\"/{$pluralName}/{{ \${$pluralName}Item->id }}/edit\" class=\"text-yellow-600 hover:underline\">Edit</a>\n          </td>";

        $indexContent = str_replace(
            [
                '{{ resourceName }}',
                '{{ resourcePlural }}',
                '{{ resourcePluralLower }}',
                '{{ indexTableHeaders }}',
                '{{ indexTableRows }}'
            ],
            [
                $name,
                $pluralName,
                strtolower($pluralName),
                implode("\n", $indexTableHeaders),
                implode("\n", $indexTableRows)
            ],
            file_get_contents(__DIR__ . "/../Console/Commands/stubs/views/index.tf.stub")
        );
        file_put_contents("{$viewPath}/index.tf.php", $indexContent);
        $this->console->line("Created resources/views/{$pluralName}/index.tf.php");


        // --- Create View ---
        $formFields = [];
        foreach ($fields as $field => $type) {
            $inputType = $this->getHtmlInputForField($field, $type, $name, 'old(\''.$field.'\')');
            $formFields[] = "<div class=\"mb-4\">\n        <label for=\"{$field}\" class=\"block text-sm font-medium text-gray-700 mb-1\">" . ucfirst($field) . "</label>\n        {$inputType}\n        @if(errors('{$field}'))<p class=\"text-red-500 text-xs mt-1\">{{ errors('{$field}')[0] }}</p>@endif\n      </div>";
        }
        $formFieldsString = implode("\n", $formFields);

        $createContent = str_replace(
            [
                '{{ resourceName }}',
                '{{ resourcePlural }}',
                '{{ formFields }}'
            ],
            [
                $name,
                $pluralName,
                $formFieldsString
            ],
            file_get_contents(__DIR__ . "/../Console/Commands/stubs/views/create.tf.stub")
        );
        file_put_contents("{$viewPath}/create.tf.php", $createContent);
        $this->console->line("Created resources/views/{$pluralName}/create.tf.php");


        // --- Edit View ---
        $editFormFields = [];
        foreach ($fields as $field => $type) {
            $valueAttribute = "\${$name}->{$field}";
            $inputType = $this->getHtmlInputForField($field, $type, $name, "old('{$field}', {$valueAttribute})");
            $editFormFields[] = "<div class=\"mb-4\">\n        <label for=\"{$field}\" class=\"block text-sm font-medium text-gray-700 mb-1\">" . ucfirst($field) . "</label>\n        {$inputType}\n        @if(errors('{$field}'))<p class=\"text-red-500 text-xs mt-1\">{{ errors('{$field}')[0] }}</p>@endif\n      </div>";
        }
        $editFormFieldsString = implode("\n", $editFormFields);

        $editContent = str_replace(
            [
                '{{ resourceName }}',
                '{{ resourcePlural }}',
                '{{ resourceSingularLower }}',
                '{{ formFields }}'
            ],
            [
                $name,
                $pluralName,
                strtolower($name),
                $editFormFieldsString
            ],
            file_get_contents(__DIR__ . "/../Console/Commands/stubs/views/edit.tf.stub")
        );
        file_put_contents("{$viewPath}/edit.tf.php", $editContent);
        $this->console->line("Created resources/views/{$pluralName}/edit.tf.php");


        // --- Show View ---
        $showFields = [];
        foreach ($fields as $field => $type) {
            $showFields[] = "    <p class=\"mb-2\"><strong>" . ucfirst($field) . ":</strong> {{ \${$name}->{$field} }}</p>";
        }
        $showFieldsString = implode("\n", $showFields);

        $showContent = str_replace(
            [
                '{{ resourceName }}',
                '{{ resourcePlural }}',
                '{{ resourceSingularLower }}',
                '{{ showFields }}'
            ],
            [
                $name,
                $pluralName,
                strtolower($name),
                $showFieldsString
            ],
            file_get_contents(__DIR__ . "/../Console/Commands/stubs/views/show.tf.stub")
        );
        file_put_contents("{$viewPath}/show.tf.php", $showContent);
        $this->console->line("Created resources/views/{$pluralName}/show.tf.php");
    }

    /**
     * Get HTML input field based on field type.
     *
     * @param string $field
     * @param string $type
     * @param string $modelName
     * @param string $valueHelper
     * @return string
     */
    protected function getHtmlInputForField(string $field, string $type, string $modelName, string $valueHelper): string
    {
        return match ($type) {
            'text' => "<textarea name=\"{$field}\" id=\"{$field}\" class=\"input\" rows=\"5\">{{ {$valueHelper} }}</textarea>",
            'boolean' => "<input type=\"checkbox\" name=\"{$field}\" id=\"{$field}\" class=\"h-4 w-4 text-gray-600 border-gray-300 rounded\" {{ {$valueHelper} ? 'checked' : '' }}>",
            'int', 'integer', 'unsignedBigInteger' => "<input type=\"number\" name=\"{$field}\" id=\"{$field}\" class=\"input\" value=\"{{ {$valueHelper} }}\">",
            'float', 'double' => "<input type=\"number\" step=\"0.01\" name=\"{$field}\" id=\"{$field}\" class=\"input\" value=\"{{ {$valueHelper} }}\">",
            'date' => "<input type=\"date\" name=\"{$field}\" id=\"{$field}\" class=\"input\" value=\"{{ {$valueHelper} }}\">",
            'datetime', 'timestamp' => "<input type=\"datetime-local\" name=\"{$field}\" id=\"{$field}\" class=\"input\" value=\"{{ {$valueHelper} }}\">",
            'email' => "<input type=\"email\" name=\"{$field}\" id=\"{$field}\" class=\"input\" value=\"{{ {$valueHelper} }}\">",
            'password' => "<input type=\"password\" name=\"{$field}\" id=\"{$field}\" class=\"input\" value=\"\">", // Passwords are not repopulated
            default => "<input type=\"text\" name=\"{$field}\" id=\"{$field}\" class=\"input\" value=\"{{ {$valueHelper} }}\">",
        };
    }

    /**
     * Add web routes for the resource.
     *
     * @param string $name
     * @param string $pluralName
     * @return void
     */
    protected function addWebRoutes(string $name, string $pluralName): void
    {
        $controller = 'App\\Controllers\\' . $name . 'Controller';
        $routeContent = <<<PHP
Route::get('/{$pluralName}', [{$controller}::class, 'index']);
Route::get('/{$pluralName}/create', [{$controller}::class, 'create']);
Route::post('/{$pluralName}', [{$controller}::class, 'store']);
Route::get('/{$pluralName}/{id}', [{$controller}::class, 'show']);
Route::get('/{$pluralName}/{id}/edit', [{$controller}::class, 'edit']);
Route::put('/{$pluralName}/{id}', [{$controller}::class, 'update']);
Route::delete('/{$pluralName}/{id}', [{$controller}::class, 'destroy']);
PHP;
        $this->appendToRoutesFile('routes/web.php', $routeContent);
        $this->console->line("Added web routes for '{$name}' to routes/web.php");
    }

    /**
     * Add API routes for the resource.
     *
     * @param string $name
     * @param string $pluralName
     * @return void
     */
    protected function addApiRoutes(string $name, string $pluralName): void
    {
        $controller = 'App\\Controllers\\' . $name . 'Controller';
        $routeContent = <<<PHP
Route::group(['prefix' => 'api', 'middleware' => ['api']], function () {
    Route::get('/{$pluralName}', [{$controller}::class, 'index']);
    Route::post('/{$pluralName}', [{$controller}::class, 'store']);
    Route::get('/{$pluralName}/{id}', [{$controller}::class, 'show']);
    Route::put('/{$pluralName}/{id}', [{$controller}::class, 'update']);
    Route::delete('/{$pluralName}/{id}', [{$controller}::class, 'destroy']);
});
PHP;
        $this->appendToRoutesFile('routes/api.php', $routeContent);
        $this->console->line("Added API routes for '{$name}' to routes/api.php");
    }

    /**
     * Append content to a routes file.
     *
     * @param string $filePath
     * @param string $content
     * @return void
     */
    protected function appendToRoutesFile(string $filePath, string $content): void
    {
        $fullPath = $this->app->basePath($filePath);
        file_put_contents($fullPath, "\n" . $content . "\n", FILE_APPEND);
    }
}