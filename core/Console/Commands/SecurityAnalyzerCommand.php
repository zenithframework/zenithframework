<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Security Analyzer Command
 * 
 * Scans codebase for security vulnerabilities
 */
class SecurityAnalyzerCommand extends Command
{
    protected string $name = 'analyzer:security';
    protected string $description = 'Analyze codebase for security vulnerabilities';

    protected array $securityPatterns = [
        // SQL Injection risks
        'raw(' => 'Raw SQL query - verify parameterization',
        'DB::raw(' => 'Raw SQL via DB facade - verify parameterization',
        '->raw(' => 'Raw SQL method - verify parameterization',
        
        // XSS vulnerabilities
        'echo $' => 'Direct variable output - verify escaping',
        'echo $_' => 'Direct superglobal output - verify escaping',
        '{{ $' => 'Template output - verify auto-escaping',
        
        // Command injection
        'exec(' => 'Shell execution - verify input sanitization',
        'shell_exec(' => 'Shell execution - verify input sanitization',
        'system(' => 'System call - verify input sanitization',
        'passthru(' => 'System passthru - verify input sanitization',
        'popen(' => 'Process popen - verify input sanitization',
        'proc_open(' => 'Process opener - verify input sanitization',
        
        // File inclusion risks
        'include $_' => 'Dynamic include from superglobal',
        'require $_' => 'Dynamic require from superglobal',
        'include_once $_' => 'Dynamic include_once from superglobal',
        'require_once $_' => 'Dynamic require_once from superglobal',
        
        // Unsafe deserialization
        'unserialize(' => 'Unsafe deserialization - use json_decode instead',
        'php_unserialize(' => 'PHP deserialization - verify source',
        
        // eval and assert
        'eval(' => 'Code eval - critical security risk',
        'assert(' => 'Assert usage - can be exploited',
        'create_function(' => 'Deprecated create_function - security risk',
        
        // Password handling
        'password = ' => 'Password assignment - verify hashing',
        '$_POST[\'password\']' => 'Raw password from POST',
        '$_GET[\'password\']' => 'Raw password from GET - never use',
        
        // Cryptographic concerns
        'md5(' => 'Weak MD5 hash - use SHA-256 or better',
        'sha1(' => 'Weak SHA-1 hash - use SHA-256 or better',
        'rand(' => 'Weak random - use random_int() for security',
        'mt_rand(' => 'Weak random - use random_int() for security',
        
        // Superglobal access
        '$_REQUEST' => 'REQUEST superglobal - use specific superglobal',
        '$GLOBALS' => 'Global variable access',
        
        // Environment and config
        'getenv(' => 'Environment access - use config() helper',
        'putenv(' => 'Environment modification',
        
        // File operations
        'file_get_contents($_' => 'File read from variable - verify path',
        'file_put_contents($_' => 'File write from variable - verify path',
        'unlink($_' => 'File delete from variable - verify path',
        
        // Session security
        'session_id($_' => 'Session ID from variable - verify validation',
        
        // Header injection
        'header(' => 'Header setting - verify no injection',
        'header("Location: ' => 'Redirect - verify URL validation',
    ];

    protected array $fileExtensions = ['php'];

    public function handle(Container $container, array $arguments): void
    {
        $this->info('🔒 Zenith Framework Security Analyzer');
        $this->line('');

        $directory = dirname(__DIR__, 3);
        $totalFiles = 0;
        $totalVulnerabilities = 0;
        $vulnerabilitiesByFile = [];
        $severityCounts = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];

        $criticalPatterns = ['eval(', 'exec(', 'system(', 'passthru(', 'unserialize(', '$_GET[\'password\']'];
        $highPatterns = ['raw(', 'shell_exec(', 'md5(', 'sha1(', 'rand(', 'mt_rand('];
        $mediumPatterns = ['echo $', 'include $_', 'require $_', 'file_get_contents($_', 'header('];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!in_array($file->getExtension(), $this->fileExtensions)) {
                continue;
            }

            $pathname = $file->getPathname();
            
            // Skip vendor, cache, and test directories
            if (strpos($pathname, 'vendor') !== false || 
                strpos($pathname, 'cache') !== false ||
                strpos($pathname, 'node_modules') !== false ||
                strpos($pathname, 'tests') !== false) {
                continue;
            }

            $totalFiles++;
            $content = file_get_contents($pathname);
            $lines = explode("\n", $content);
            $fileVulns = [];

            foreach ($lines as $lineNum => $line) {
                foreach ($this->securityPatterns as $pattern => $description) {
                    if (strpos($line, $pattern) !== false) {
                        // Skip comments
                        $trimmed = trim($line);
                        if (strpos($trimmed, '//') === 0 || 
                            strpos($trimmed, '*') === 0 ||
                            strpos($trimmed, '#') === 0) {
                            continue;
                        }

                        // Determine severity
                        $severity = 'low';
                        if (in_array($pattern, $criticalPatterns)) {
                            $severity = 'critical';
                        } elseif (in_array($pattern, $highPatterns)) {
                            $severity = 'high';
                        } elseif (in_array($pattern, $mediumPatterns)) {
                            $severity = 'medium';
                        }

                        $severityCounts[$severity]++;

                        $fileVulns[] = [
                            'line' => $lineNum + 1,
                            'pattern' => $pattern,
                            'description' => $description,
                            'severity' => $severity,
                            'code' => trim($line),
                        ];
                        $totalVulnerabilities++;
                    }
                }
            }

            if (!empty($fileVulns)) {
                $relativePath = str_replace($directory . DIRECTORY_SEPARATOR, '', $pathname);
                $vulnerabilitiesByFile[$relativePath] = $fileVulns;
            }
        }

        $this->line('');
        $this->info('📊 Security Analysis Complete');
        $this->line('');
        $this->info("Severity Breakdown:");
        $this->line("  🔴 Critical: {$severityCounts['critical']}");
        $this->line("  🟠 High: {$severityCounts['high']}");
        $this->line("  🟡 Medium: {$severityCounts['medium']}");
        $this->line("  🟢 Low: {$severityCounts['low']}");
        $this->line("  📈 Total: {$totalVulnerabilities}");
        $this->line('');
        $this->info("Files Scanned: {$totalFiles}");
        $this->info("Files with Issues: " . count($vulnerabilitiesByFile));
        $this->line('');

        if (!empty($vulnerabilitiesByFile)) {
            $this->warn('📋 Detailed Vulnerabilities:');
            $this->line('');

            foreach ($vulnerabilitiesByFile as $file => $vulns) {
                $severityIcon = ['critical' => '🔴', 'high' => '🟠', 'medium' => '🟡', 'low' => '🟢'];
                
                $this->warn("📁 {$file}");
                
                foreach ($vulns as $vuln) {
                    $icon = $severityIcon[$vuln['severity']] ?? '⚪';
                    $this->line("   {$icon} Line {$vuln['line']} [{$vuln['severity']}]: {$vuln['description']}");
                    $this->line("   Pattern: {$vuln['pattern']}");
                    $this->line("   Code: " . substr($vuln['code'], 0, 80));
                    $this->line('');
                }
            }
        } else {
            $this->info('✅ No security vulnerabilities detected!');
        }

        $this->line('');
        $this->info('💡 Security Recommendations:');
        $this->line('   - Always use prepared statements for database queries');
        $this->line('   - Escape all output to prevent XSS');
        $this->line('   - Use password_hash() for password storage');
        $this->line('   - Use random_int() instead of rand() for security');
        $this->line('   - Validate and sanitize all user inputs');
        $this->line('   - Use CSRF protection on all forms');
        $this->line('   - Implement rate limiting on sensitive endpoints');
        $this->line('   - Keep dependencies updated');
        $this->line('   - Use HTTPS in production');
        $this->line('');
    }
}
