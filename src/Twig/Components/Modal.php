<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Modal')]
final class Modal
{
    public string $id = '';
    public string $headline = '';
    public string $supportingText = '';
    public string $variant = 'basic'; // basic, scrollable, fullscreen
    public bool $dismissible = true; // can be closed with ESC or backdrop click
    public string $class = '';
    public array $attributes = [];

    public function getId(): string
    {
        return $this->id ?: 'modal_'.uniqid();
    }

    public function getBackdropClasses(): string
    {
        return 'fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4 backdrop-blur-sm';
    }

    public function getDialogClasses(): string
    {
        $classes = [
            'relative',
            'bg-[var(--color-surface)]',
            'text-[var(--color-on-surface)]',
            'shadow-[var(--shadow-elevation-3)]',
            'w-full',
            'flex',
            'flex-col',
        ];

        // Variant-specific classes
        if ('fullscreen' === $this->variant) {
            $classes[] = 'h-full';
            $classes[] = 'max-w-full';
            $classes[] = 'rounded-none';
        } else {
            $classes[] = 'max-w-md';
            $classes[] = 'max-h-[90vh]';
            $classes[] = 'rounded-[var(--radius-xl)]';
        }

        if ($this->class) {
            $classes[] = $this->class;
        }

        return implode(' ', $classes);
    }

    public function getHeaderClasses(): string
    {
        return 'px-6 pt-6 pb-4';
    }

    public function getHeadlineClasses(): string
    {
        return 'text-headline-small text-[var(--color-on-surface)] m-0';
    }

    public function getSupportingTextClasses(): string
    {
        return 'text-body-medium text-[var(--color-on-surface-variant)] mt-2';
    }

    public function getContentClasses(): string
    {
        $classes = [
            'px-6',
            'text-body-medium',
            'text-[var(--color-on-surface-variant)]',
        ];

        if ('scrollable' === $this->variant) {
            $classes[] = 'overflow-y-auto';
            $classes[] = 'flex-1';
        } else {
            $classes[] = 'pb-6';
        }

        return implode(' ', $classes);
    }

    public function getActionsClasses(): string
    {
        return 'flex items-center justify-end gap-2 px-6 pb-6 pt-4';
    }

    public function getAttributesString(): string
    {
        $attrs = $this->attributes;
        $attrs['role'] = 'dialog';
        $attrs['aria-modal'] = 'true';
        $attrs['aria-labelledby'] = $this->getId().'_headline';

        if ($this->supportingText) {
            $attrs['aria-describedby'] = $this->getId().'_description';
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
}
