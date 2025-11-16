<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Snackbar')]
final class Snackbar
{
    public string $message = '';
    public string $actionLabel = '';
    public string $status = 'info'; // info, success, warning, error
    public int $duration = 4000; // milliseconds, 0 = no auto-hide
    public string $position = 'bottom-center'; // bottom-center, bottom-left, bottom-right, top-center
    public string $class = '';
    public string $id = '';

    public function getId(): string
    {
        return $this->id ?: 'snackbar_' . uniqid();
    }

    public function getContainerClasses(): string
    {
        $classes = [
            'fixed',
            'z-50',
            'flex',
            'items-center',
            'justify-between',
            'gap-4',
            'min-h-[48px]',
            'min-w-[344px]',
            'max-w-[672px]',
            'px-4',
            'py-3',
            'rounded-[var(--radius-xs)]',
            'shadow-[var(--shadow-elevation-3)]',
            'transition-all',
            'duration-300',
            'transform',
        ];

        // Position
        $classes[] = match ($this->position) {
            'bottom-left' => 'bottom-4 left-4',
            'bottom-right' => 'bottom-4 right-4',
            'top-center' => 'top-4 left-1/2 -translate-x-1/2',
            default => 'bottom-4 left-1/2 -translate-x-1/2', // bottom-center
        };

        // Status colors
        $classes[] = match ($this->status) {
            'success' => 'bg-[var(--color-primary-container)] text-[var(--color-on-primary-container)]',
            'warning' => 'bg-[var(--color-tertiary-container)] text-[var(--color-on-tertiary-container)]',
            'error' => 'bg-[var(--color-error-container)] text-[var(--color-on-error-container)]',
            default => 'bg-[var(--color-surface-variant)] text-[var(--color-on-surface-variant)]',
        };

        // Custom classes
        if ($this->class) {
            $classes[] = $this->class;
        }

        return implode(' ', $classes);
    }

    public function getMessageClasses(): string
    {
        return 'text-body-medium flex-1';
    }

    public function getActionButtonClasses(): string
    {
        $classes = [
            'text-label-large',
            'font-medium',
            'px-3',
            'py-2',
            'rounded-[var(--radius-xs)]',
            'transition-colors',
            'duration-200',
            'cursor-pointer',
            'select-none',
            'outline-none',
            'focus:ring-2',
        ];

        $classes[] = match ($this->status) {
            'success' => 'text-[var(--color-on-primary-container)] hover:bg-[var(--color-on-primary-container)]/8 focus:ring-[var(--color-on-primary-container)]',
            'warning' => 'text-[var(--color-on-tertiary-container)] hover:bg-[var(--color-on-tertiary-container)]/8 focus:ring-[var(--color-on-tertiary-container)]',
            'error' => 'text-[var(--color-on-error-container)] hover:bg-[var(--color-on-error-container)]/8 focus:ring-[var(--color-on-error-container)]',
            default => 'text-[var(--color-on-surface-variant)] hover:bg-[var(--color-on-surface-variant)]/8 focus:ring-[var(--color-on-surface-variant)]',
        };

        return implode(' ', $classes);
    }

    public function getCloseButtonClasses(): string
    {
        $classes = [
            'w-6',
            'h-6',
            'ml-2',
            'cursor-pointer',
            'opacity-70',
            'hover:opacity-100',
            'transition-opacity',
            'duration-200',
        ];

        return implode(' ', $classes);
    }
}
