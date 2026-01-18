<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('AppScaffold')]
final class AppScaffold
{
    public string $title = '';
    public bool $showTopBar = true;
    public bool $showBottomNav = false;
    public bool $showNavRail = false;
    public bool $showNavDrawer = false;
    public ?string $fabIcon = null;
    public ?string $fabHref = null;
    public array $navDestinations = [];
    public string $currentPath = '';
    public string $class = '';
    public string $id = '';

    public function getContainerClasses(): string
    {
        $classes = [
            'min-h-screen',
            'bg-[var(--color-background)]',
            'text-[var(--color-on-background)]',
            'flex',
            'flex-col',
        ];

        if ($this->class) {
            $classes[] = $this->class;
        }

        return implode(' ', $classes);
    }

    public function getTopBarClasses(): string
    {
        return 'sticky top-0 z-30 h-16 bg-[var(--color-surface)] border-b border-[var(--color-outline-variant)] flex items-center px-4 gap-4';
    }

    public function getTitleClasses(): string
    {
        return 'text-title-large text-[var(--color-on-surface)] flex-1';
    }

    public function getMainClasses(): string
    {
        $classes = [
            'flex-1',
            'flex',
        ];

        // Add margin for navigation components
        if ($this->showNavRail) {
            $classes[] = 'md:ml-20';
        }

        if ($this->showNavDrawer) {
            $classes[] = 'lg:ml-80';
        }

        return implode(' ', $classes);
    }

    public function getContentClasses(): string
    {
        $classes = [
            'flex-1',
            'w-full',
            'max-w-7xl',
            'mx-auto',
            'p-4',
            'md:p-6',
            'lg:p-8',
        ];

        // Add bottom padding if bottom nav is shown
        if ($this->showBottomNav) {
            $classes[] = 'pb-24';
        }

        return implode(' ', $classes);
    }

    public function getFabClasses(): string
    {
        $classes = [
            'fixed',
            'z-30',
            'w-14',
            'h-14',
            'bg-[var(--color-primary-container)]',
            'text-[var(--color-on-primary-container)]',
            'rounded-[var(--radius-lg)]',
            'shadow-[var(--shadow-elevation-3)]',
            'hover:shadow-[var(--shadow-elevation-4)]',
            'flex',
            'items-center',
            'justify-center',
            'transition-all',
            'duration-200',
            'cursor-pointer',
            'select-none',
            'no-underline',
        ];

        // Position: bottom-right, accounting for bottom nav on mobile
        if ($this->showBottomNav) {
            $classes[] = 'bottom-24';
            $classes[] = 'md:bottom-6';
        } else {
            $classes[] = 'bottom-6';
        }

        $classes[] = 'right-6';

        return implode(' ', $classes);
    }

    public function getFabIconClasses(): string
    {
        return 'w-6 h-6';
    }

    public function hasNavigation(): bool
    {
        return !empty($this->navDestinations) && ($this->showBottomNav || $this->showNavRail || $this->showNavDrawer);
    }
}
