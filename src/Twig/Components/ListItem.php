<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('ListItem')]
final class ListItem
{
    public string $headline = '';
    public string $supporting = '';
    public string $trailing = '';
    public string $lines = '2'; // 1, 2, 3 - number of text lines
    public string $type = 'default'; // default, navigation, selectable, action
    public ?string $leadingIcon = null;
    public ?string $leadingAvatar = null; // URL to avatar image
    public ?string $trailingIcon = null;
    public bool $selected = false;
    public ?string $href = null;
    public string $class = '';
    public string $id = '';
    public array $attributes = [];

    public function getContainerClasses(): string
    {
        $classes = [
            'flex',
            'items-center',
            'w-full',
            'px-[var(--list-item-padding-x)]',
            'gap-4',
            'transition-colors',
            'duration-200',
        ];

        // Height based on number of lines
        $classes[] = match ($this->lines) {
            '1' => 'h-[var(--list-item-height-1)] py-[var(--list-item-padding-y)]',
            '3' => 'h-[var(--list-item-height-3)] py-[var(--list-item-padding-y)]',
            default => 'h-[var(--list-item-height-2)] py-[var(--list-item-padding-y)]',
        };

        // Interactive states
        if ($this->isInteractive()) {
            $classes[] = 'cursor-pointer';
            $classes[] = 'hover:bg-[var(--color-on-surface)]/8';
            $classes[] = 'active:bg-[var(--color-on-surface)]/12';
            $classes[] = 'focus-visible:outline-none focus-visible:bg-[var(--color-on-surface)]/12';
        }

        // Selected state
        if ($this->selected) {
            $classes[] = 'bg-[var(--color-secondary-container)]';
            $classes[] = 'text-[var(--color-on-secondary-container)]';
        } else {
            $classes[] = 'bg-transparent';
            $classes[] = 'text-[var(--color-on-surface)]';
        }

        // Custom classes
        if ($this->class) {
            $classes[] = $this->class;
        }

        return implode(' ', $classes);
    }

    public function getLeadingClasses(): string
    {
        return 'flex-shrink-0';
    }

    public function getLeadingIconClasses(): string
    {
        return 'w-6 h-6 text-[var(--color-on-surface-variant)]';
    }

    public function getLeadingAvatarClasses(): string
    {
        return 'w-10 h-10 rounded-full bg-[var(--color-primary-container)] overflow-hidden';
    }

    public function getContentClasses(): string
    {
        return 'flex-1 min-w-0';
    }

    public function getHeadlineClasses(): string
    {
        $classes = [
            'text-body-large',
            'text-[var(--color-on-surface)]',
            'truncate',
        ];

        if ($this->selected) {
            $classes[] = 'font-medium';
        }

        return implode(' ', $classes);
    }

    public function getSupportingClasses(): string
    {
        return 'text-body-medium text-[var(--color-on-surface-variant)] truncate mt-0.5';
    }

    public function getTrailingClasses(): string
    {
        return 'flex-shrink-0 text-label-small text-[var(--color-on-surface-variant)]';
    }

    public function getTrailingIconClasses(): string
    {
        return 'w-6 h-6 text-[var(--color-on-surface-variant)]';
    }

    public function getAttributesString(): string
    {
        $attrs = $this->attributes;

        if ($this->id) {
            $attrs['id'] = $this->id;
        }

        if ($this->isInteractive()) {
            $attrs['role'] = 'button';
            $attrs['tabindex'] = '0';
        }

        if ('selectable' === $this->type) {
            $attrs['aria-selected'] = $this->selected ? 'true' : 'false';
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

    public function isInteractive(): bool
    {
        return !empty($this->href) || in_array($this->type, ['navigation', 'selectable', 'action']);
    }

    public function isLink(): bool
    {
        return !empty($this->href);
    }
}
