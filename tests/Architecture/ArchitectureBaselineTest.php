<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class ArchitectureBaselineTest extends TestCase
{
    public function test_all_modules_have_required_layers(): void
    {
        $modulesPath = dirname(__DIR__, 2) . '/app/Modules';

        $modules = array_filter(
            scandir($modulesPath),
            static fn (string $item): bool =>
                $item !== '.'
                && $item !== '..'
                && is_dir($modulesPath . '/' . $item),
        );

        $this->assertNotEmpty($modules);

        foreach ($modules as $module) {
            foreach ([
                'Domain',
                'Application',
                'Infrastructure',
                'Presentation',
            ] as $layer) {
                $this->assertDirectoryExists(
                    $modulesPath . '/' . $module . '/' . $layer,
                    sprintf(
                        'Module "%s" must contain "%s" layer.',
                        $module,
                        $layer,
                    ),
                );
            }
        }
    }

    public function test_shared_domain_does_not_depend_on_laravel(): void
    {
        $path = dirname(__DIR__, 2) . '/app/Shared/Domain';

        $forbiddenNamespaces = [
            'Illuminate\\',
            'Laravel\\',
            'Symfony\\Component\\HttpFoundation\\',
            'Symfony\\Component\\HttpKernel\\',
        ];

        $violations = $this->findNamespaceViolations(
            path: $path,
            forbiddenNamespaces: $forbiddenNamespaces,
        );

        $this->assertSame(
            [],
            $violations,
            "Shared Domain contains forbidden framework dependencies:\n"
            . implode("\n", $violations),
        );
    }

    public function test_shared_domain_does_not_depend_on_modules(): void
    {
        $path = dirname(__DIR__, 2) . '/app/Shared/Domain';

        $violations = $this->findNamespaceViolations(
            path: $path,
            forbiddenNamespaces: [
                'StoreYar\\Modules\\',
            ],
        );

        $this->assertSame(
            [],
            $violations,
            "Shared Domain must not depend on Modules:\n"
            . implode("\n", $violations),
        );
    }

    public function test_module_domains_do_not_depend_on_laravel(): void
    {
        $modulesPath = dirname(__DIR__, 2) . '/app/Modules';

        $forbiddenNamespaces = [
            'Illuminate\\',
            'Laravel\\',
            'Symfony\\Component\\HttpFoundation\\',
            'Symfony\\Component\\HttpKernel\\',
        ];

        $violations = $this->findNamespaceViolations(
            path: $modulesPath,
            forbiddenNamespaces: $forbiddenNamespaces,
            onlyDomainDirectories: true,
        );

        $this->assertSame(
            [],
            $violations,
            "Module Domain contains forbidden framework dependencies:\n"
            . implode("\n", $violations),
        );
    }

    public function test_domain_does_not_reference_database_or_eloquent(): void
    {
        $paths = [
            dirname(__DIR__, 2) . '/app/Shared/Domain',
            dirname(__DIR__, 2) . '/app/Modules',
        ];

        $forbiddenPatterns = [
            'Illuminate\\Database\\',
            'Illuminate\\Support\\Facades\\DB',
            'Illuminate\\Support\\Facades\\Schema',
            'Eloquent',
        ];

        $violations = [];

        foreach ($paths as $path) {
            foreach ($this->phpFiles($path) as $file) {
                $content = file_get_contents($file);

                if ($content === false) {
                    continue;
                }

                foreach ($forbiddenPatterns as $pattern) {
                    if (str_contains($content, $pattern)) {
                        $violations[] = $file . ' -> ' . $pattern;
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Domain contains forbidden persistence dependencies:\n"
            . implode("\n", $violations),
        );
    }

    public function test_shared_application_does_not_depend_on_database(): void
    {
        $path = dirname(__DIR__, 2) . '/app/Shared/Application';

        $forbiddenNamespaces = [
            'Illuminate\\Database\\',
            'Illuminate\\Support\\Facades\\DB',
            'Illuminate\\Support\\Facades\\Schema',
            'Illuminate\\Database\\Eloquent\\',
        ];

        $violations = $this->findNamespaceViolations(
            path: $path,
            forbiddenNamespaces: $forbiddenNamespaces,
        );

        $this->assertSame(
            [],
            $violations,
            "Shared Application contains forbidden persistence dependencies:\n"
            . implode("\n", $violations),
        );
    }

    /**
     * @return list<string>
     */
    private function findNamespaceViolations(
        string $path,
        array $forbiddenNamespaces,
        bool $onlyDomainDirectories = false,
    ): array {
        $violations = [];

        foreach ($this->phpFiles($path) as $file) {
            if (
                $onlyDomainDirectories
                && ! str_contains(
                    str_replace('\\', '/', $file),
                    '/Domain/',
                )
            ) {
                continue;
            }

            $content = file_get_contents($file);

            if ($content === false) {
                continue;
            }

            foreach ($forbiddenNamespaces as $namespace) {
                if (str_contains($content, $namespace)) {
                    $violations[] = $file . ' -> ' . $namespace;
                }
            }
        }

        return $violations;
    }

    /**
     * @return list<string>
     */
    private function phpFiles(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $path,
                \FilesystemIterator::SKIP_DOTS,
            ),
        );

        foreach ($iterator as $file) {
            if (
                $file instanceof \SplFileInfo
                && $file->isFile()
                && $file->getExtension() === 'php'
            ) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
