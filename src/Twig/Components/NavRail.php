<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('NavRail')]
final class NavRail
{
    public array $destinations = []; // Array of ['icon' => 'path', 'label' => 'Text', 'path' => '/url', 'badge' => 3]
    public string $currentPath = '';
    public string $class = '';
    public string $id = '';
    public bool $showLabels = true;

    public function getContainerClasses(): string
    {
        $classes = [
            'hidden',
            'md:flex', // Show on medium screens and up
            'lg:hidden', // Hide on large screens (drawer takes over)
            'fixed',
            'left-0',
            'top-0',
            'bottom-0',
            'z-40',
            'w-20',
            'flex-col',
            'items-center',
            'gap-3',
            'py-6',
            'bg-[var(--color-surface)]',
            'border-r',
            'border-[var(--color-outline-variant)]',
        ];

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
            'flex-col',
            'items-center',
            'justify-center',
            'gap-1',
            'w-14',
            'h-14',
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
        } else {
            $classes[] = 'text-[var(--color-on-surface-variant)]';
            $classes[] = 'hover:bg-[var(--color-on-surface)]/8';
            $classes[] = 'hover:text-[var(--color-on-surface)]';
        }

        return implode(' ', $classes);
    }

    public function getIconClasses(): string
    {
        return 'w-6 h-6';
    }

    public function getLabelClasses(): string
    {
        return 'text-label-small mt-0.5';
    }

    public function getBadgeClasses(): string
    {
        return 'absolute top-0 right-0 bg-[var(--color-error)] text-[var(--color-on-error)] text-label-small rounded-full min-w-[16px] h-4 px-1 flex items-center justify-center';
    }

    public function isActive(array $destination): bool
    {
        return isset($destination['path']) && $this->currentPath === $destination['path'];
    }
}
