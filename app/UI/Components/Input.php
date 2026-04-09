<?php

declare(strict_types=1);

namespace App\UI\Components;

use Zenith\UI\Component;

class Input extends Component
{
    public function render(): string
    {
        $type = $this->getProp('type', 'text');
        $name = $this->getProp('name', '');
        $label = $this->getProp('label');
        $value = $this->getProp('value', '');
        $placeholder = $this->getProp('placeholder', '');
        $required = $this->getProp('required', false);
        $error = $this->getProp('error');
        $class = $this->getProp('class', '');
        
        $baseClass = 'w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500';
        $errorClass = $error ? 'border-red-500' : 'border-gray-300';
        
        $html = '';
        
        if ($label) {
            $html .= '<label class="block text-sm font-medium text-gray-700 mb-1">';
            $html .= $label;
            if ($required) {
                $html .= ' <span class="text-red-500">*</span>';
            }
            $html .= '</label>';
        }
        
        $attrs = [
            'type' => $type,
            'name' => $name,
            'value' => $value,
            'placeholder' => $placeholder,
            'required' => $required ? 'required' : null,
            'class' => trim("{$baseClass} {$errorClass} {$class}"),
        ];
        
        $html .= '<input ' . $this->attrs($attrs) . '>';
        
        if ($error) {
            $html .= '<p class="mt-1 text-sm text-red-600">' . $this->e($error) . '</p>';
        }
        
        return $html;
    }
}
