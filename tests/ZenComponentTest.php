<?php

declare(strict_types=1);

namespace Tests;

class ZenComponentTest extends TestCase
{
    public function test_component_parsing(): void
    {
        $parser = new \Zenith\UI\ComponentParser();
        
        $attrs = 'variant="primary" size="lg"';
        $result = $parser::parseAttrs($attrs);
        
        $this->assertEquals('primary', $result['variant'] ?? null);
        $this->assertEquals('lg', $result['size'] ?? null);
    }
    
    public function test_component_parsing_single_quotes(): void
    {
        $parser = new \Zenith\UI\ComponentParser();
        
        $attrs = "variant='danger'";
        $result = $parser::parseAttrs($attrs);
        
        $this->assertEquals('danger', $result['variant'] ?? null);
    }
    
    public function test_slot_parsing(): void
    {
        $parser = new \Zenith\UI\ComponentParser();
        
        $content = '<span slot="header">Title</span>Body content<span slot="footer">Footer</span>';
        $result = $parser::parseSlots($content);
        
        $this->assertEquals('Title', $result['header'] ?? '');
        $this->assertEquals('Footer', $result['footer'] ?? '');
        $this->assertEquals('Body content', $result['default'] ?? '');
    }
    
    public function test_slot_parsing_default_only(): void
    {
        $parser = new \Zenith\UI\ComponentParser();
        
        $content = 'Just body content';
        $result = $parser::parseSlots($content);
        
        $this->assertEquals('Just body content', $result['default'] ?? '');
    }
    
    public function test_compile_zen_components_button(): void
    {
        $template = '<zen:button variant="primary">Click</zen:button>';
        $compiled = \Zenith\UI\TemplateDirectives::compile($template);
        
        $this->assertTrue(str_contains($compiled, 'App\UI\Components\Button::make'));
    }
    
    public function test_compile_zen_component_self_closing(): void
    {
        $template = '<zen:counter />';
        $compiled = \Zenith\UI\TemplateDirectives::compile($template);
        
        $this->assertTrue(str_contains($compiled, 'App\UI\Components\Counter::make'));
    }
    
    public function test_compile_layout(): void
    {
        $template = '<zen:layout.main title="Home">';
        $compiled = \Zenith\UI\TemplateDirectives::compile($template);
        
        $this->assertTrue(str_contains($compiled, 'ZenTemplate::extends'));
        $this->assertTrue(str_contains($compiled, 'layouts.main'));
    }
}