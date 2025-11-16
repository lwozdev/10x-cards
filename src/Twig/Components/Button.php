<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Button')]
final class Button
{
    public string $label = '';
    public string $variant = 'filled'; // filled, filled-tonal, outlined, text, elevated
    public string $type = 'button'; // button, submit, reset
    public string $size = 'md'; // sm, md, lg
    public ?string $icon = null; // leading icon class
    public ?string $trailingIcon = null;
    public bool $disabled = false;
    public string $href = ''; // if set, renders as <a> instead of <button>
    public string $class = '';
    public string $id = '';
    public array $attributes = [];

    public function getBaseClasses(): string
    {
        $classes = [
            'inline-flex',
            'items-center',
            'justify-center',
            'font-medium',
            'text-label-large',
            'transition-all',
            'duration-200',
            'cursor-pointer',
            'select-none',
            'outline-none',
            'focus:ring-2',
            'focus:ring-offset-2',
        ];

        // Size classes
        $classes[] = match ($this->size) {
            'sm' => 'h-[var(--button-height-sm)] px-[var(--button-padding-x-sm)] gap-[6px]',
            'lg' => 'h-[var(--button-height-lg)] px-[var(--button-padding-x)] gap-[var(--button-gap)]',
            default => 'h-[var(--button-height)] px-[var(--button-padding-x)] gap-[var(--button-gap)]',
        };

        // Full rounded corners (stadium shape)
        $classes[] = 'rounded-[var(--radius-full)]';

        // Variant-specific classes
        $classes[] = match ($this->variant) {
            'filled' => 'bg-[var(--color-primary)] text-[var(--color-on-primary)] hover:shadow-[var(--shadow-elevation-1)] active:shadow-none',
            'filled-tonal' => 'bg-[var(--color-secondary-container)] text-[var(--color-on-secondary-container)] hover:shadow-[var(--shadow-elevation-1)] active:shadow-none',
            'outlined' => 'bg-transparent text-[var(--color-primary)] border border-[var(--color-outline)] hover:bg-[var(--color-primary)]/8',
            'text' => 'bg-transparent text-[var(--color-primary)] hover:bg-[var(--color-primary)]/8',
            'elevated' => 'bg-[var(--color-surface)] text-[var(--color-primary)] shadow-[var(--shadow-elevation-1)] hover:shadow-[var(--shadow-elevation-2)]',
            default => 'bg-[var(--color-primary)] text-[var(--color-on-primary)]',
        };

        // Focus ring color
        $classes[] = 'focus:ring-[var(--color-primary)]';

        // Disabled state
        if ($this->disabled) {
            $classes[] = 'opacity-38 cursor-not-allowed pointer-events-none';
        }

        // Custom classes
        if ($this->class) {
            $classes[] = $this->class;
        }

        return implode(' ', $classes);
    }

    public function getIconSize(): string
    {
        return match ($this->size) {
            'sm' => 'w-4 h-4',
            'lg' => 'w-6 h-6',
            default => 'w-5 h-5',
        };
    }

    public function getAttributesString(): string
    {
        $attrs = $this->attributes;

        if ($this->id) {
            $attrs['id'] = $this->id;
        }

        if (!$this->href && $this->disabled) {
            $attrs['disabled'] = 'disabled';
        }

        if (!$this->href) {
            $attrs['type'] = $this->type;
        }

        $result = [];
        foreach ($attrs as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $result[] = htmlspecialchars($key);
                }
            } else {
                $result[] = sprintf('%s="%s"', htmlspecialchars($key), htmlspecialchars($value));
            }
        }

        return implode(' ', $result);
    }

    public function isLink(): bool
    {
        return !empty($this->href);
    }
}
