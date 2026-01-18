<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Card')]
final class Card
{
    public string $variant = 'elevated'; // elevated, outlined, filled
    public string $class = '';
    public string $id = '';
    public array $attributes = [];
    public bool $clickable = false;
    public ?string $href = null;

    public function getContainerClasses(): string
    {
        $classes = [
            'block',
            'w-full',
            'overflow-hidden',
            'transition-all',
            'duration-200',
        ];

        // Shape
        $classes[] = 'rounded-[var(--radius-md)]';

        // Padding
        $classes[] = 'p-[var(--card-padding)]';

        // Variant-specific classes
        $classes[] = match ($this->variant) {
            'elevated' => 'bg-[var(--color-surface)] text-[var(--color-on-surface)] shadow-[var(--shadow-elevation-1)] hover:shadow-[var(--shadow-elevation-2)]',
            'outlined' => 'bg-[var(--color-surface)] text-[var(--color-on-surface)] border border-[var(--color-outline-variant)]',
            'filled' => 'bg-[var(--color-surface-variant)] text-[var(--color-on-surface)]',
            default => 'bg-[var(--color-surface)] text-[var(--color-on-surface)]',
        };

        // Clickable/interactive states
        if ($this->clickable || $this->href) {
            $classes[] = 'cursor-pointer select-none';
            $classes[] = 'hover:bg-[var(--color-on-surface)]/4';
            $classes[] = 'active:bg-[var(--color-on-surface)]/8';
            $classes[] = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)]';
        }

        // Custom classes
        if ($this->class) {
            $classes[] = $this->class;
        }

        return implode(' ', $classes);
    }

    public function getAttributesString(): string
    {
        $attrs = $this->attributes;

        if ($this->id) {
            $attrs['id'] = $this->id;
        }

        if ($this->clickable || $this->href) {
            $attrs['role'] = 'button';
            $attrs['tabindex'] = '0';
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
