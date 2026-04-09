<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Bug Analyzer Command
 * 
 * Scans codebase for common bugs and issues
 */
class BugAnalyzerCommand extends Command
{
    protected string $name = 'analyzer:bugs';
    protected string $description = 'Analyze codebase for common bugs and issues';

    protected array $bugPatterns = [
        'TODO:' => 'Unimplemented code',
        'FIXME:' => 'Known bug marker',
        'HACK:' => 'Temporary workaround',
        'XXX:' => 'Problematic code',
        'die(' => 'Debug die statement',
        'var_dump(' => 'Debug var_dump',
        'print_r(' => 'Debug print_r',
        'eval(' => 'Dangerous eval usage',
        'exec(' => 'Shell execution - verify sanitization',
        'shell_exec(' => 'Shell execution - verify sanitization',
        'system(' => 'System call - verify sanitization',
        'passthru(' => 'System passthru - verify sanitization',
        '`' => 'Backtick operator - verify necessity',
        'global ' => 'Global variable usage',
        'extract(' => 'Extract usage - potential variable overwrite',
        'create_function(' => 'Deprecated create_function',
        '__FILE__' => 'Hardcoded file reference',
        '__DIR__' => 'Hardcoded directory reference',
    ];

    protected array $fileExtensions = ['php'];

    public function handle(Container $container, array $arguments): void
    {
        $this->info('🔍 Zenith Framework Bug Analyzer');
        $this->line('');

        $directory = dirname(__DIR__, 3);
        $totalFiles = 0;
        $totalBugs = 0;
        $bugsByFile = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!in_array($file->getExtension(), $this->fileExtensions)) {
                continue;
            }

            // Skip vendor and cache directories
            $pathname = $file->getPathname();
            if (strpos($pathname, 'vendor') !== false || 
                strpos($pathname, 'cache') !== false ||
                strpos($pathname, 'node_modules') !== false) {
                continue;
            }

            $totalFiles++;
            $content = file_get_contents($pathname);
            $lines = explode("\n", $content);
            $fileBugs = [];

            foreach ($lines as $lineNum => $line) {
                foreach ($this->bugPatterns as $pattern => $description) {
                    if (strpos($line, $pattern) !== false) {
                        // Skip if it's in a comment
                        $trimmed = trim($line);
                        if (strpos($trimmed, '//') === 0 || strpos($trimmed, '*') === 0) {
                            continue;
                        }

                        $fileBugs[] = [
                            'line' => $lineNum + 1,
                            'pattern' => $pattern,
                            'description' => $description,
                            'code' => trim($line),
                        ];
                        $totalBugs++;
                    }
                }
            }

            if (!empty($fileBugs)) {
                $relativePath = str_replace($directory . DIRECTORY_SEPARATOR, '', $pathname);
                $bugsByFile[$relativePath] = $fileBugs;
            }
        }

        $this->line('');
        $this->info("📊 Analysis Complete");
        $this->line('');
        $this->info("Files Scanned: {$totalFiles}");
        $this->info("Potential Issues: {$totalBugs}");
        $this->info("Files with Issues: " . count($bugsByFile));

        if (!empty($bugsByFile)) {
            $this->line('');
            $this->warn('📋 Detailed Issues:');
            $this->line('');

            foreach ($bugsByFile as $file => $bugs) {
                $this->warn("📁 {$file}");
                
                foreach ($bugs as $bug) {
                    $this->line("   Line {$bug['line']}: {$bug['description']}");
                    $this->line("   Pattern: {$bug['pattern']}");
                    $this->line("   Code: " . substr($bug['code'], 0, 80));
                    $this->line('');
                }
            }
        } else {
            $this->info('✅ No common bugs detected!');
        }

        $this->line('');
        $this->info('💡 Tips:');
        $this->line('   - Review each issue manually before removing');
        $this->line('   - Some patterns may be intentional (debug code in dev environment)');
        $this->line('   - Always test after removing debug statements');
        $this->line('');
    }
}
