<?php

declare(strict_types=1);

namespace App\UI\Components;

use Zen\UI\Component;

class Card extends Component
{
    public function render(): string
    {
        $class = $this->getProp('class', 'p-4 border rounded');
        
        $html = '<div class="card ' . $this->e($class) . '">';
        
        if ($this->hasSlot('header')) {
            $html .= '<div class="card-header font-bold mb-2">' . $this->getSlot('header') . '</div>';
        }
        
        $html .= '<div class="card-body">' . $this->getSlot('default') . '</div>';
        
        if ($this->hasSlot('footer')) {
            $html .= '<div class="card-footer mt-2 text-sm text-gray-500">' . $this->getSlot('footer') . '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}