<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('BottomNav')]
final class BottomNav
{
    public array $destinations = []; // Array of ['icon' => 'path', 'label' => 'Text', 'path' => '/url', 'badge' => 3]
    public string $currentPath = '';
    public string $class = '';
    public string $id = '';

    public function getContainerClasses(): string
    {
        $classes = [
            'fixed',
            'bottom-0',
            'left-0',
            'right-0',
            'z-40',
            'bg-[var(--color-surface)]',
            'border-t',
            'border-[var(--color-outline-variant)]',
            'flex',
            'justify-around',
            'items-center',
            'h-20',
            'md:hidden', // Hide on medium screens and up
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
            'relative',
            'flex-1',
            'h-full',
            'transition-all',
            'duration-200',
            'cursor-pointer',
            'select-none',
            'no-underline',
        ];

        if ($isActive) {
            $classes[] = 'text-[var(--color-on-surface)]';
        } else {
            $classes[] = 'text-[var(--color-on-surface-variant)]';
            $classes[] = 'hover:text-[var(--color-on-surface)]';
        }

        return implode(' ', $classes);
    }

    public function getPillClasses(bool $isActive): string
    {
        $classes = [
            'absolute',
            'top-1/2',
            '-translate-y-1/2',
            'flex',
            'flex-col',
            'items-center',
            'justify-center',
            'gap-1',
            'px-4',
            'h-8',
            'rounded-[var(--radius-full)]',
            'transition-all',
            'duration-200',
        ];

        if ($isActive) {
            $classes[] = 'bg-[var(--color-secondary-container)]';
        }

        return implode(' ', $classes);
    }

    public function getIconClasses(): string
    {
        return 'w-6 h-6';
    }

    public function getLabelClasses(): string
    {
        return 'text-label-medium';
    }

    public function getBadgeClasses(): string
    {
        return 'absolute top-1 right-1 bg-[var(--color-error)] text-[var(--color-on-error)] text-label-small rounded-full min-w-[16px] h-4 px-1 flex items-center justify-center';
    }

    public function isActive(array $destination): bool
    {
        return isset($destination['path']) && $this->currentPath === $destination['path'];
    }
}
