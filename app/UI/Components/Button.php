<?php

declare(strict_types=1);

namespace App\UI\Components;

use Zenith\UI\Component;

class Button extends Component
{
    public function render(): string
    {
        $type = $this->getProp('type', 'button');
        $variant = $this->getProp('variant', 'primary');
        $size = $this->getProp('size', 'md');
        $href = $this->getProp('href');
        $action = $this->getProp('action');
        $disabled = $this->getProp('disabled', false);
        $class = $this->getProp('class', '');
        
        $baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2';
        
        $variantClasses = match ($variant) {
            'primary' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
            'secondary' => 'bg-gray-200 text-gray-900 hover:bg-gray-300 focus:ring-gray-500',
            'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
            'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
            'outline' => 'border border-gray-300 text-gray-700 hover:bg-gray-50 focus:ring-gray-500',
            default => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
        };
        
        $sizeClasses = match ($size) {
            'sm' => 'px-3 py-1.5 text-sm',
            'md' => 'px-4 py-2 text-sm',
            'lg' => 'px-5 py-2.5 text-base',
            default => 'px-4 py-2 text-sm',
        };
        
        $disabledClass = $disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer';
        
        $classes = trim("{$baseClasses} {$variantClasses} {$sizeClasses} {$disabledClass} {$class}");
        
        $attrs = [
            'class' => $classes,
            'type' => $href ? null : $type,
            'href' => $href,
            'disabled' => $disabled ? 'disabled' : null,
        ];
        
        if ($action) {
            $attrs['hx-post'] = $action;
            $attrs['hx-trigger'] = 'click';
            $attrs['hx-swap'] = 'outerHTML';
        }
        
        $content = $this->slots['default'] ?? $this->getProp('label', 'Button');
        
        if ($href && !$disabled) {
            return '<a ' . $this->attrs($attrs) . '>' . $content . '</a>';
        }
        
        return '<button ' . $this->attrs($attrs) . '>' . $content . '</button>';
    }
}
