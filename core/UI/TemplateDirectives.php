<?php

declare(strict_types=1);

namespace Zen\UI;

use Zen\Auth\Auth;
use Zen\UI\ZenTemplate;
use Zen\UI\ComponentParser;

class TemplateDirectives
{
    public static function compile(string $content): string
    {
        $content = "<?php use Zen\\UI\\ZenTemplate; ?>\n" . $content;
        $content = self::compileComments($content);
        $content = self::compilePhp($content);
        $content = self::compileZenComponents($content);
        $content = self::compileLayouts($content);
        $content = self::compileConditionals($content);
        $content = self::compileLoops($content);
        $content = self::compileSections($content);
        $content = self::compileIncludes($content);
        $content = self::compileComponents($content);
        $content = self::compileForms($content);
        $content = self::compileStacks($content);
        $content = self::compileEnv($content);
        $content = self::compileAuth($content);
        $content = self::compileHelpers($content);
        
        return $content;
    }

    protected static function compileComments(string $content): string
    {
        return preg_replace('/\{\{--[\s\S]*?--\}\}/', '', $content);
    }

    protected static function compilePhp(string $content): string
    {
        $content = preg_replace('/@php\b/', '<?php', $content);
        $content = preg_replace('/@endphp/', ' ?>', $content);
        
        return $content;
    }

    protected static function compileConditionals(string $content): string
    {
        $content = preg_replace('/@if\s*\(([^)]+)\)/', '<?php if($1): ?>', $content);
        $content = preg_replace('/@elseif\s*\(([^)]+)\)/', '<?php elseif($1): ?>', $content);
        $content = preg_replace('/@else\b/', '<?php else: ?>', $content);
        $content = preg_replace('/@endif\b/', '<?php endif; ?>', $content);
        
        $content = preg_replace('/@unless\s*\(([^)]+)\)/', '<?php if(!($1)): ?>', $content);
        $content = preg_replace('/@endunless\b/', '<?php endif; ?>', $content);
        
        $content = preg_replace('/@isset\s*\(([^)]+)\)/', '<?php if(isset($1)): ?>', $content);
        $content = preg_replace('/@endisset\b/', '<?php endif; ?>', $content);
        
        $content = preg_replace('/@empty\s*\(([^)]+)\)/', '<?php if(empty($1)): ?>', $content);
        $content = preg_replace('/@endempty\b/', '<?php endif; ?>', $content);
        
        return $content;
    }

    protected static function compileLoops(string $content): string
    {
        $content = preg_replace('/@foreach\s*\(([^)]+)\)/', '<?php foreach($1): ?>', $content);
        $content = preg_replace('/@endforeach\b/', '<?php endforeach; ?>', $content);
        
        $content = preg_replace('/@for\s*\(([^)]+)\)/', '<?php for($1): ?>', $content);
        $content = preg_replace('/@endfor\b/', '<?php endfor; ?>', $content);
        
        $content = preg_replace('/@while\s*\(([^)]+)\)/', '<?php while($1): ?>', $content);
        $content = preg_replace('/@endwhile\b/', '<?php endwhile; ?>', $content);
        
        $content = preg_replace('/@forelse\s*\(([^)]+)\)/', '<?php foreach($1): ?>', $content);
        $content = preg_replace('/@empty\b/', '<?php endforeach; else: ?>', $content);
        $content = preg_replace('/@endforelse\b/', '<?php endif; ?>', $content);
        
        $content = preg_replace('/@break\b/', '<?php break; ?>', $content);
        $content = preg_replace('/@continue\b/', '<?php continue; ?>', $content);
        
        return $content;
    }

    protected static function compileSections(string $content): string
    {
        $content = preg_replace("/@extends\s*\(['\"]([^'\"]+)['\"]\)/", '<?php echo ZenTemplate::extends(\'$1\'); ?>', $content);
        
        $content = preg_replace("/@section\s*\(['\"]([^'\"]+)['\"]\)\s*/", '<?php ZenTemplate::section(\'$1\'); ?>', $content);
        $content = preg_replace("/@section\s*\(['\"]([^'\"]+)['\"],\s*(.+?)\)\s*/", '<?php ZenTemplate::section(\'$1\'); echo $2; ZenTemplate::endsection(); ?>', $content);
        $content = preg_replace('/@endsection\b/', '<?php ZenTemplate::endsection(); ?>', $content);
        
        $content = preg_replace("/@yield\s*\(['\"]([^'\"]+)['\"]\)/", '<?php echo ZenTemplate::yield(\'$1\'); ?>', $content);
        $content = preg_replace("/@yield\s*\(['\"]([^'\"]+)['\"],\s*(.+?)\)/", '<?php echo ZenTemplate::yield(\'$1\', $2); ?>', $content);
        
        $content = preg_replace('/@parent\b/', '<?php echo ZenTemplate::parent(); ?>', $content);
        
        $content = preg_replace("/@hasSection\s*\(['\"]([^'\"]+)['\"]\)/", '<?php echo ZenTemplate::hasSection(\'$1\'); ?>', $content);
        
        return $content;
    }

    protected static function compileIncludes(string $content): string
    {
        $content = preg_replace("/@include\s*\(['\"]([^'\"]+)['\"]\)/", '<?php echo ZenTemplate::include(\'$1\'); ?>', $content);
        $content = preg_replace("/@includeIf\s*\(['\"]([^'\"]+)['\"]\)/", '<?php echo ZenTemplate::includeIf(\'$1\'); ?>', $content);
        $content = preg_replace('/@includeWhen\s*\(([^,]+),\s*[\'"]([^\'"]+)[\'"]\)/', '<?php echo ZenTemplate::includeWhen($1, \'$2\'); ?>', $content);
        
        return $content;
    }

    protected static function compileComponents(string $content): string
    {
        $content = preg_replace("/@component\s*\(['\"]([^'\"]+)['\"]\)/", '<?php echo ZenTemplate::component(\'$1\'); ?>', $content);
        $content = preg_replace("/@component\s*\(['\"]([^'\"]+)['\"],\s*(.+?)\)/", '<?php echo ZenTemplate::component(\'$1\', $2); ?>', $content);
        $content = preg_replace('/@endcomponent\b/', '<?php echo ZenTemplate::endcomponent(); ?>', $content);
        
        $content = preg_replace("/@slot\s*\(['\"]([^'\"]+)['\"]\)/", '<?php echo ZenTemplate::slot(\'$1\'); ?>', $content);
        $content = preg_replace('/@endslot\b/', '<?php echo ZenTemplate::endslot(); ?>', $content);
        
        return $content;
    }

    protected static function compileForms(string $content): string
    {
        $content = preg_replace('/@csrf\b/', '<input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">', $content);
        $content = preg_replace("/@method\s*\(['\"]([^'\"]+)['\"]\)/", '<input type="hidden" name="_method" value="$1">', $content);
        $content = preg_replace('/@json\s*\(([^)]+)\)/', '<?php echo json_encode($1); ?>', $content);
        
        return $content;
    }

    protected static function compileStacks(string $content): string
    {
        $content = preg_replace("/@stack\s*\(['\"]([^'\"]+)['\"]\)/", '<?php echo ZenTemplate::stack(\'$1\'); ?>', $content);
        $content = preg_replace("/@push\s*\(['\"]([^'\"]+)['\"]\)/", '<?php ZenTemplate::push(\'$1\'); ?>', $content);
        $content = preg_replace('/@endpush\b/', '<?php ZenTemplate::endpush(); ?>', $content);
        $content = preg_replace("/@prepend\s*\(['\"]([^'\"]+)['\"]\)/", '<?php ZenTemplate::prepend(\'$1\'); ?>', $content);
        $content = preg_replace('/@endprepend\b/', '<?php ZenTemplate::endprepend(); ?>', $content);
        
        return $content;
    }

    protected static function compileEnv(string $content): string
    {
        $content = preg_replace("/@env\s*\(['\"]([^'\"]+)['\"]\)/", '<?php if(app()->environment(\'$1\')): ?>', $content);
        $content = preg_replace('/@endenv\b/', '<?php endif; ?>', $content);
        
        $content = preg_replace('/@production\b/', '<?php if(app()->isProduction()): ?>', $content);
        $content = preg_replace('/@endproduction\b/', '<?php endif; ?>', $content);
        
        return $content;
    }

    protected static function compileAuth(string $content): string
    {
        $content = preg_replace('/@auth\b/', '<?php if(' . Auth::class . '::check()): ?>', $content);
        $content = preg_replace('/@endauth\b/', '<?php endif; ?>', $content);
        
        $content = preg_replace('/@guest\b/', '<?php if(!' . Auth::class . '::check()): ?>', $content);
        $content = preg_replace('/@endguest\b/', '<?php endif; ?>', $content);
        
        return $content;
    }

    protected static function compileHelpers(string $content): string
    {
        // {{ $var }} - simple variable (no expression)
        $content = preg_replace('/\{\{\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/', '<?php echo htmlspecialchars(\$$1, ENT_QUOTES, \'UTF-8\'); ?>', $content);
        
        // {{ $expr }} - expressions like {{ $a * 2 }}, {{ $a + $b }}, {{ func() }}
        $content = preg_replace('/\{\{\s*(\$.+?)\}\}/', '<?php echo htmlspecialchars($1, ENT_QUOTES, \'UTF-8\'); ?>', $content);
        
        // {!! $var !!} - raw output simple variable
        $content = preg_replace('/\{\!\!\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\!\!\}/', '<?php echo \$$1; ?>', $content);
        
        // {!! $expr !!} - raw output expressions
        $content = preg_replace('/\{\!\!\s*(\$.+?)\!\!\}/', '<?php echo $1; ?>', $content);
        
        return $content;
    }

    protected static function compileZenComponents(string $content): string
    {
        $content = preg_replace_callback(
            '/<zen:([a-zA-Z0-9\-_]+)\s*([^>]*)>([\s\S]*?)<\/zen:\1>/',
            fn($m) => '<?php echo App\\UI\\Components\\' . ucfirst($m[1]) . '::make(\'' . trim($m[2]) . '\', \'' . addslashes($m[3]) . '\'); ?>',
            $content
        );

        $content = preg_replace_callback(
            '/<zen:([a-zA-Z0-9\-_]+)\s+([^>]+)\/>/',
            fn($m) => '<?php echo App\\UI\\Components\\' . ucfirst($m[1]) . '::make(\'' . trim($m[2]) . '\'); ?>',
            $content
        );

        $content = preg_replace_callback(
            '/<zen:([a-zA-Z0-9\-_]+)\s*\/>/',
            fn($m) => '<?php echo App\\UI\\Components\\' . ucfirst($m[1]) . '::make(); ?>',
            $content
        );

        return $content;
    }

    protected static function compileLayouts(string $content): string
    {
        $content = preg_replace_callback(
            '/<zen:layout\.([a-zA-Z0-9\-_]+)\s*([^>]*)>/',
            fn($m) => self::compileLayoutAttrs($m[1], $m[2]),
            $content
        );

        return $content;
    }

    protected static function compileLayoutAttrs(string $name, string $attrs): string
    {
        $props = ComponentParser::parseAttrs($attrs);
        $layout = 'layouts.' . $name;

        if (!empty($props)) {
            $sections = [];
            foreach ($props as $key => $value) {
                $sections[] = "ZenTemplate::section('$key'); echo \$value; ZenTemplate::endsection();";
            }

            return "<?php ZenTemplate::extends('$layout'); ?>" .
                   implode('', array_map(fn($s) => "<?php $s ?>", $sections));
        }

        return "<?php ZenTemplate::extends('$layout'); ?>";
    }
}
