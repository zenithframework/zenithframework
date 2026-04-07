<?php

declare(strict_types=1);

namespace Zen\UI;

class ComponentParser
{
    public static function parseAttrs(string $raw): array
    {
        $props = [];
        preg_match_all('/([a-zA-Z0-9\-_]+)=["\']([^"\']*)["\']/', $raw, $m, PREG_SET_ORDER);
        
        foreach ($m as $pair) {
            $props[$pair[1]] = $pair[2];
        }
        
        return $props;
    }
    
    public static function parseSlots(string $content): array
    {
        $slots = ['default' => ''];
        
        preg_match_all('/<span\s+slot=["\']([^"\']+)["\']>([\s\S]*?)<\/span>/', $content, $m, PREG_SET_ORDER);
        
        foreach ($m as $slot) {
            $slots[$slot[1]] = $slot[2];
            $content = str_replace($slot[0], '', $content);
        }
        
        $slots['default'] = trim($content);
        
        return $slots;
    }
}