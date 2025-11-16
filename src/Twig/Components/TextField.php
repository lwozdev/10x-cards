<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('TextField')]
final class TextField
{
    public string $name = '';
    public string $label = '';
    public string $value = '';
    public string $type = 'text'; // text, email, password, number, tel, url
    public string $variant = 'filled'; // filled, outlined
    public string $placeholder = '';
    public bool $required = false;
    public bool $disabled = false;
    public bool $readonly = false;
    public ?string $helperText = null;
    public ?string $errorText = null;
    public ?string $leadingIcon = null;
    public ?string $trailingIcon = null;
    public string $id = '';
    public string $class = '';
    public array $attributes = [];
    public int $maxlength = 0;
    public int $minlength = 0;
    public ?string $pattern = null;
    public ?string $autocomplete = null;

    public function getId(): string
    {
        return $this->id ?: 'textfield_' . uniqid();
    }

    public function getContainerClasses(): string
    {
        $classes = [
            'relative',
            'w-full',
        ];

        if ($this->class) {
            $classes[] = $this->class;
        }

        return implode(' ', $classes);
    }

    public function getInputWrapperClasses(): string
    {
        $classes = [
            'relative',
            'w-full',
            'h-[var(--input-height)]',
            'flex',
            'items-center',
            'transition-all',
            'duration-200',
        ];

        // Variant-specific classes
        if ($this->variant === 'filled') {
            $classes[] = 'bg-[var(--color-surface-variant)]';
            $classes[] = 'rounded-t-[var(--radius-xs)]';
            $classes[] = 'border-b-2';
            $classes[] = $this->hasError()
                ? 'border-[var(--color-error)]'
                : 'border-[var(--color-on-surface-variant)] focus-within:border-[var(--color-primary)]';
        } else { // outlined
            $classes[] = 'bg-transparent';
            $classes[] = 'rounded-[var(--radius-xs)]';
            $classes[] = 'border';
            $classes[] = $this->hasError()
                ? 'border-[var(--color-error)]'
                : 'border-[var(--color-outline)] focus-within:border-[var(--color-primary)] focus-within:border-2';
        }

        if ($this->disabled) {
            $classes[] = 'opacity-38 cursor-not-allowed';
        }

        return implode(' ', $classes);
    }

    public function getInputClasses(): string
    {
        $classes = [
            'w-full',
            'h-full',
            'px-[var(--input-padding-x)]',
            'pt-[20px]',
            'pb-[8px]',
            'bg-transparent',
            'outline-none',
            'text-body-large',
            'text-[var(--color-on-surface)]',
            'placeholder:opacity-0',
            'focus:placeholder:opacity-60',
        ];

        if ($this->leadingIcon) {
            $classes[] = 'pl-12';
        }

        if ($this->trailingIcon) {
            $classes[] = 'pr-12';
        }

        if ($this->disabled || $this->readonly) {
            $classes[] = 'cursor-not-allowed';
        }

        return implode(' ', $classes);
    }

    public function getLabelClasses(): string
    {
        $classes = [
            'absolute',
            'left-[var(--input-padding-x)]',
            'transition-all',
            'duration-200',
            'pointer-events-none',
            'origin-left',
        ];

        if ($this->leadingIcon) {
            $classes[] = 'left-12';
        }

        // Floating label behavior handled by peer-placeholder-shown utility
        $classes[] = 'top-[8px]';
        $classes[] = 'text-label-small';
        $classes[] = 'scale-100';

        $classes[] = $this->hasError()
            ? 'text-[var(--color-error)]'
            : 'text-[var(--color-on-surface-variant)] peer-focus:text-[var(--color-primary)]';

        return implode(' ', $classes);
    }

    public function getIconClasses(): string
    {
        return 'absolute top-1/2 -translate-y-1/2 w-6 h-6 text-[var(--color-on-surface-variant)]';
    }

    public function getSupportingTextClasses(): string
    {
        $classes = [
            'mt-1',
            'px-[var(--input-padding-x)]',
            'text-body-small',
        ];

        $classes[] = $this->hasError()
            ? 'text-[var(--color-error)]'
            : 'text-[var(--color-on-surface-variant)]';

        return implode(' ', $classes);
    }

    public function getAttributesString(): string
    {
        $attrs = $this->attributes;

        if ($this->required) {
            $attrs['required'] = true;
        }

        if ($this->disabled) {
            $attrs['disabled'] = true;
        }

        if ($this->readonly) {
            $attrs['readonly'] = true;
        }

        if ($this->maxlength > 0) {
            $attrs['maxlength'] = $this->maxlength;
        }

        if ($this->minlength > 0) {
            $attrs['minlength'] = $this->minlength;
        }

        if ($this->pattern) {
            $attrs['pattern'] = $this->pattern;
        }

        if ($this->autocomplete) {
            $attrs['autocomplete'] = $this->autocomplete;
        }

        $attrs['aria-describedby'] = $this->getId() . '_support';

        if ($this->hasError()) {
            $attrs['aria-invalid'] = 'true';
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

    public function hasError(): bool
    {
        return !empty($this->errorText);
    }

    public function getSupportingText(): ?string
    {
        return $this->hasError() ? $this->errorText : $this->helperText;
    }
}
