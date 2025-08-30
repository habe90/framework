<?php

namespace TrueFrame\View;

use TrueFrame\Config\Repository;
use InvalidArgumentException;

class Compiler
{
    /**
     * The array of sections.
     *
     * @var array
     */
    protected array $sections = [];

    /**
     * The stack of current sections being rendered.
     *
     * @var array
     */
    protected array $sectionStack = [];

    /**
     * The stack of current layouts being extended.
     *
     * @var array
     */
    protected array $layoutStack = [];

    /**
     * The path to the compiled view files.
     *
     * @var string
     */
    protected string $compiledPath;

    /**
     * The current view factory instance (for @include).
     *
     * @var View|null
     */
    protected ?View $viewFactory = null;


    /**
     * Create a new compiler instance.
     *
     * @param Repository $config
     * @return void
     */
    public function __construct(Repository $config)
    {
        $this->compiledPath = $config->get('view.compiled');
        if (!is_dir($this->compiledPath)) {
            mkdir($this->compiledPath, 0777, true);
        }
    }

    /**
     * Set the view factory instance.
     *
     * @param View $viewFactory
     * @return void
     */
    public function setViewFactory(View $viewFactory): void
    {
        $this->viewFactory = $viewFactory;
    }

    /**
     * Compile the given view path.
     *
     * @param string $path
     * @return string The path to the compiled file.
     * @throws InvalidArgumentException
     */
    public function compile(string $path): string
    {
        $compiledFile = $this->getCompiledPath($path);

        // If the compiled file is up-to-date, return it
        if ($this->isExpired($path, $compiledFile)) {
            $contents = file_get_contents($path);
            $compiledContents = $this->compileString($contents);
            file_put_contents($compiledFile, $compiledContents);
        }

        return $compiledFile;
    }

    /**
     * Get the path to the compiled version of a view.
     *
     * @param string $path
     * @return string
     */
    protected function getCompiledPath(string $path): string
    {
        return $this->compiledPath . DIRECTORY_SEPARATOR . sha1($path) . '.php';
    }

    /**
     * Determine if the given view is expired.
     *
     * @param string $path
     * @param string $compiledFile
     * @return bool
     */
    protected function isExpired(string $path, string $compiledFile): bool
    {
        if (!file_exists($compiledFile)) {
            return true;
        }

        return filemtime($path) >= filemtime($compiledFile);
    }

    /**
     * Compile the given TrueBlade template string.
     *
     * @param string $value
     * @return string
     */
    protected function compileString(string $value): string
    {
        $value = $this->compileComments($value);
        $value = $this->compileEchos($value); // Handles {{ $var }} and {{{ $var }}}
        $value = $this->compileRawEchos($value); // Handles {!! $var !!}
        $value = $this->compileLayouts($value);
        $value = $this->compileSections($value);
        $value = $this->compileYields($value);
        $value = $this->compileIncludes($value);
        $value = $this->compileIfs($value);
        $value = $this->compileForeachs($value);
        $value = $this->compileFors($value);
        $value = $this->compileWhiles($value);
        $value = $this->compileMethod($value); // New directive
        $value = $this->compileCsrf($value);

        return $value;
    }

    /**
     * Compile Blade comments into valid PHP.
     *
     * @param string $value
     * @return string
     */
    protected function compileComments(string $value): string
    {
        return preg_replace('/\{\{--((.|\s)*?)--\}\}/', '<?php /*$1*/ ?>', $value);
    }

    /**
     * Compile Blade echos into valid PHP.
     *
     * @param string $value
     * @return string
     */
    protected function compileEchos(string $value): string
    {
        // Unescaped echo: {{{ $var }}}
        $value = preg_replace(
            '/\{\{\{\s*(.+?)\s*\}\}\}/s',
            '<?php echo $1 ?? \'\'; ?>',
            $value
        );

        // Escaped echo: {{ $var }}
        $value = preg_replace(
            '/\{\{\s*(.+?)\s*\}\}/s',
            '<?php echo htmlspecialchars($1 ?? \'\', ENT_QUOTES, \'UTF-8\'); ?>',
            $value
        );

        return $value;
    }

    /**
     * Compile Blade raw echos into valid PHP. (Original {!! $var !!})
     * This is kept separate from compileEchos to follow the prompt's implied structure
     * and for potential future distinction, though functionally {{{ }}} and {!! !!} are similar.
     *
     * @param string $value
     * @return string
     */
    protected function compileRawEchos(string $value): string
    {
        return preg_replace('/\{!!\s*(.+?)\s*!!\}/s', '<?php echo $1 ?? \'\'; ?>', $value);
    }

    /**
     * Compile the @extends directive.
     *
     * @param string $value
     * @return string
     */
    protected function compileLayouts(string $value): string
    {
        $pattern = '/@extends\s*\(\s*([\'"])(.*?)\1\s*\)/';
        return preg_replace_callback($pattern, function ($matches) {
            $layout = $matches[2];
            $this->layoutStack[] = $layout;
            return "<?php \$__env->startSection('__content'); ?>";
        }, $value) . "<?php \$__env->stopSection(); ?>";
    }

    /**
     * Compile the @section and @endsection directives.
     *
     * @param string $value
     * @return string
     */
    protected function compileSections(string $value): string
    {
        $value = preg_replace_callback('/@section\s*\(\s*([\'"])(.*?)\1\s*\)/', function ($matches) {
            $this->sectionStack[] = $matches[2];
            return "<?php \$__env->startSection('{$matches[2]}'); ?>";
        }, $value);

        $value = preg_replace('/@endsection/', '<?php \$__env->stopSection(); ?>', $value);

        return $value;
    }

    /**
     * Compile the @yield directive.
     *
     * @param string $value
     * @return string
     */
    protected function compileYields(string $value): string
    {
        return preg_replace_callback('/@yield\s*\(\s*([\'"])(.*?)\1\s*\)/', function ($matches) {
            return "<?php echo \$__env->yieldContent('{$matches[2]}'); ?>";
        }, $value);
    }

    /**
     * Compile the @include directive.
     *
     * @param string $value
     * @return string
     */
    protected function compileIncludes(string $value): string
    {
        // Pass the current data to the included view
        // The regex needs to handle optional data array
        $value = preg_replace_callback('/@include\s*\(\s*([\'"])(.*?)\1\s*(?:,\s*(.*?))?\s*\)/s', function ($matches) {
            $viewName = $matches[2];
            $data = $matches[3] ?? '[]'; // Default to empty array if no data is passed
            return "<?php echo \$__env->viewFactory->make('{$viewName}', array_merge(get_defined_vars(), {$data}))->render(); ?>";
        }, $value);

        return $value;
    }

    /**
     * Compile the @if, @elseif, @else, @endif directives.
     *
     * @param string $value
     * @return string
     */
    protected function compileIfs(string $value): string
    {
        $value = preg_replace('/@if\s*\((.*)\)/', '<?php if ($1): ?>', $value);
        $value = preg_replace('/@elseif\s*\((.*)\)/', '<?php elseif ($1): ?>', $value);
        $value = preg_replace('/@else/', '<?php else: ?>', $value);
        $value = preg_replace('/@endif/', '<?php endif; ?>', $value);
        return $value;
    }

    /**
     * Compile the @foreach and @endforeach directives.
     *
     * @param string $value
     * @return string
     */
    protected function compileForeachs(string $value): string
    {
        $value = preg_replace('/@foreach\s*\((.*)\)/', '<?php foreach ($1): ?>', $value);
        $value = preg_replace('/@endforeach/', '<?php endforeach; ?>', $value);
        return $value;
    }

    /**
     * Compile the @for and @endfor directives.
     *
     * @param string $value
     * @return string
     */
    protected function compileFors(string $value): string
    {
        $value = preg_replace('/@for\s*\((.*)\)/', '<?php for ($1): ?>', $value);
        $value = preg_replace('/@endfor/', '<?php endfor; ?>', $value);
        return $value;
    }

    /**
     * Compile the @while and @endwhile directives.
     *
     * @param string $value
     * @return string
     */
    protected function compileWhiles(string $value): string
    {
        $value = preg_replace('/@while\s*\((.*)\)/', '<?php while ($1): ?>', $value);
        $value = preg_replace('/@endwhile/', '<?php endwhile; ?>', $value);
        return $value;
    }

    /**
     * Compile the @method directive.
     *
     * @param string $value
     * @return string
     */
    protected function compileMethod(string $value): string
    {
        return preg_replace('/@method\s*\(\s*([\'"])(.*?)\1\s*\)/', '<?php echo \'<input type="hidden" name="_method" value="\' . strtoupper($2) . \'">\'; ?>', $value);
    }

    /**
     * Compile the @csrf directive.
     *
     * @param string $value
     * @return string
     */
    protected function compileCsrf(string $value): string
    {
        return str_replace('@csrf', '<?php echo \'<input type="hidden" name="_token" value="\' . csrf_token() . \'">\'; ?>', $value);
    }

    /**
     * Start a new section.
     *
     * @param string $name
     * @return void
     */
    public function startSection(string $name): void
    {
        ob_start();
        $this->sectionStack[] = $name;
    }

    /**
     * Stop the current section.
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function stopSection(): void
    {
        $last = array_pop($this->sectionStack);

        if (is_null($last)) {
            throw new InvalidArgumentException('Cannot stop a section without first starting one.');
        }

        $this->sections[$last] = ob_get_clean();

        // If a layout is being extended, render it
        if (!empty($this->layoutStack) && $last === '__content') {
            $layout = array_pop($this->layoutStack);
            // Re-render the layout with the collected sections
            echo $this->viewFactory->make($layout, get_defined_vars())->render();
        }
    }

    /**
     * Get the content for a given section.
     *
     * @param string $section
     * @return string
     */
    public function yieldContent(string $section): string
    {
        return $this->sections[$section] ?? '';
    }
}