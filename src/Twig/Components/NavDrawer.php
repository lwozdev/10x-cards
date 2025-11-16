<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('NavDrawer')]
final class NavDrawer
{
    public array $destinations = []; // Array of ['icon' => 'path', 'label' => 'Text', 'path' => '/url', 'badge' => 3]
    public string $currentPath = '';
    public string $variant = 'permanent'; // permanent, modal
    public string $class = '';
    public string $id = '';

    public function getContainerClasses(): string
    {
        $classes = [
            'bg-[var(--color-surface)]',
            'border-r',
            'border-[var(--color-outline-variant)]',
            'flex',
            'flex-col',
            'gap-1',
            'py-3',
        ];

        if ($this->variant === 'permanent') {
            $classes[] = 'hidden';
            $classes[] = 'lg:flex'; // Show on large screens
            $classes[] = 'fixed';
            $classes[] = 'left-0';
            $classes[] = 'top-0';
            $classes[] = 'bottom-0';
            $classes[] = 'z-40';
            $classes[] = 'w-80';
        } else {
            // Modal variant
            $classes[] = 'fixed';
            $classes[] = 'left-0';
            $classes[] = 'top-0';
            $classes[] = 'bottom-0';
            $classes[] = 'z-50';
            $classes[] = 'w-80';
            $classes[] = 'shadow-[var(--shadow-elevation-3)]';
            $classes[] = 'transform';
            $classes[] = 'transition-transform';
            $classes[] = 'duration-300';
        }

        if ($this->class) {
            $classes[] = $this->class;
        }

        return implode(' ', $classes);
    }

    public function getDestinationClasses(array $destination): string
    {
        $isActive = $this->isActive($destination);

        $classes = [
            'flex',
            'items-center',
            'gap-3',
            'px-4',
            'h-14',
            'mx-3',
            'relative',
            'transition-all',
            'duration-200',
            'cursor-pointer',
            'select-none',
            'no-underline',
            'rounded-[var(--radius-full)]',
        ];

        if ($isActive) {
            $classes[] = 'bg-[var(--color-secondary-container)]';
            $classes[] = 'text-[var(--color-on-secondary-container)]';
            $classes[] = 'font-medium';
        } else {
            $classes[] = 'text-[var(--color-on-surface-variant)]';
            $classes[] = 'hover:bg-[var(--color-on-surface)]/8';
            $classes[] = 'hover:text-[var(--color-on-surface)]';
        }

        return implode(' ', $classes);
    }

    public function getIconClasses(): string
    {
        return 'w-6 h-6 flex-shrink-0';
    }

    public function getLabelClasses(): string
    {
        return 'text-label-large flex-1';
    }

    public function getBadgeClasses(): string
    {
        return 'bg-[var(--color-error)] text-[var(--color-on-error)] text-label-small rounded-full min-w-[20px] h-5 px-2 flex items-center justify-center';
    }

    public function isActive(array $destination): bool
    {
        return isset($destination['path']) && $this->currentPath === $destination['path'];
    }
}
