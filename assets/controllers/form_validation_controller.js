import { Controller } from '@hotwired/stimulus';

/**
 * Form Validation Controller
 *
 * Provides real-time client-side validation for authentication forms:
 * - Email format validation
 * - Password strength checking
 * - Password confirmation matching
 * - Submit button state management
 */
export default class extends Controller {
    static targets = [
        'email',
        'emailError',
        'password',
        'passwordError',
        'passwordConfirm',
        'passwordConfirmError',
        'submitButton',
        'strengthBar1',
        'strengthBar2',
        'strengthBar3',
        'strengthBar4',
        'strengthText',
        'terms'
    ];

    connect() {
        console.log('Form validation controller connected');
        this.updateSubmitButtonState();
    }

    /**
     * Validate email format
     */
    validateEmail(event) {
        const email = event ? event.target.value : this.emailTarget.value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!email) {
            this.showError('emailError', 'Email jest wymagany');
            return false;
        }

        if (!emailRegex.test(email)) {
            this.showError('emailError', 'Podaj prawidłowy adres email');
            return false;
        }

        this.hideError('emailError');
        return true;
    }

    /**
     * Real-time email validation (less strict, only shows error after blur)
     */
    validateEmailRealtime(event) {
        const email = event.target.value;
        if (email.length > 0) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailRegex.test(email)) {
                this.hideError('emailError');
            }
        }
        this.updateSubmitButtonState();
    }

    /**
     * Validate password strength
     */
    validatePassword(event) {
        const password = event ? event.target.value : this.passwordTarget.value;

        if (!password) {
            this.showError('passwordError', 'Hasło jest wymagane');
            this.updatePasswordStrength(0);
            return false;
        }

        if (password.length < 8) {
            this.showError('passwordError', 'Hasło musi mieć co najmniej 8 znaków');
            this.updatePasswordStrength(1);
            return false;
        }

        // Calculate strength
        let strength = 1;
        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength = 4;

        this.updatePasswordStrength(strength);
        this.hideError('passwordError');
        return true;
    }

    /**
     * Real-time password validation
     */
    validatePasswordRealtime(event) {
        const password = event.target.value;

        // Update strength indicator
        if (password.length > 0) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.length >= 8 && /[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (password.length >= 8 && /\d/.test(password)) strength++;
            if (password.length >= 8 && /[^a-zA-Z0-9]/.test(password)) strength = 4;

            this.updatePasswordStrength(strength);

            if (password.length >= 8) {
                this.hideError('passwordError');
            }
        } else {
            this.updatePasswordStrength(0);
        }

        // Also check password match if confirmation field exists and has value
        if (this.hasPasswordConfirmTarget && this.passwordConfirmTarget.value) {
            this.validatePasswordMatchRealtime({ target: this.passwordConfirmTarget });
        }

        this.updateSubmitButtonState();
    }

    /**
     * Update password strength visual indicator
     */
    updatePasswordStrength(strength) {
        if (!this.hasStrengthBar1Target) return;

        const bars = [
            this.strengthBar1Target,
            this.strengthBar2Target,
            this.strengthBar3Target,
            this.strengthBar4Target
        ];

        const colors = [
            'bg-[var(--color-surface-variant)]',  // default
            'bg-red-500',                          // weak
            'bg-orange-500',                       // fair
            'bg-yellow-500',                       // good
            'bg-green-500'                         // strong
        ];

        const texts = [
            '',
            'Słabe hasło',
            'Przeciętne hasło',
            'Dobre hasło',
            'Mocne hasło'
        ];

        // Reset all bars
        bars.forEach(bar => {
            bar.className = 'h-1 flex-1 bg-[var(--color-surface-variant)] rounded transition-colors';
        });

        // Fill bars based on strength
        for (let i = 0; i < strength; i++) {
            bars[i].className = `h-1 flex-1 ${colors[strength]} rounded transition-colors`;
        }

        // Update text
        if (this.hasStrengthTextTarget) {
            this.strengthTextTarget.textContent = texts[strength];
            this.strengthTextTarget.className = `text-body-small ${
                strength === 0 ? 'text-[var(--color-on-surface-variant)]' :
                strength === 1 ? 'text-red-500' :
                strength === 2 ? 'text-orange-500' :
                strength === 3 ? 'text-yellow-600' :
                'text-green-600'
            } mt-1`;
        }
    }

    /**
     * Validate password confirmation match
     */
    validatePasswordMatch(event) {
        if (!this.hasPasswordTarget || !this.hasPasswordConfirmTarget) {
            return true;
        }

        const password = this.passwordTarget.value;
        const passwordConfirm = event ? event.target.value : this.passwordConfirmTarget.value;

        if (!passwordConfirm) {
            this.showError('passwordConfirmError', 'Potwierdź hasło');
            return false;
        }

        if (password !== passwordConfirm) {
            this.showError('passwordConfirmError', 'Hasła nie są identyczne');
            return false;
        }

        this.hideError('passwordConfirmError');
        return true;
    }

    /**
     * Real-time password confirmation validation
     */
    validatePasswordMatchRealtime(event) {
        if (!this.hasPasswordTarget || !this.hasPasswordConfirmTarget) {
            return;
        }

        const password = this.passwordTarget.value;
        const passwordConfirm = event.target.value;

        if (passwordConfirm.length > 0) {
            if (password === passwordConfirm) {
                this.hideError('passwordConfirmError');
            } else if (passwordConfirm.length >= password.length) {
                // Only show error if they've typed enough characters
                this.showError('passwordConfirmError', 'Hasła nie są identyczne');
            }
        }

        this.updateSubmitButtonState();
    }

    /**
     * Update submit button state based on form validity
     */
    updateSubmitButtonState() {
        if (!this.hasSubmitButtonTarget) return;

        let isValid = true;

        // Check email if present
        if (this.hasEmailTarget) {
            const email = this.emailTarget.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email || !emailRegex.test(email)) {
                isValid = false;
            }
        }

        // Check password if present
        if (this.hasPasswordTarget) {
            const password = this.passwordTarget.value;
            if (!password || password.length < 8) {
                isValid = false;
            }
        }

        // Check password confirmation if present
        if (this.hasPasswordConfirmTarget) {
            const password = this.passwordTarget.value;
            const passwordConfirm = this.passwordConfirmTarget.value;
            if (!passwordConfirm || password !== passwordConfirm) {
                isValid = false;
            }
        }

        // Check terms checkbox if present
        if (this.hasTermsTarget) {
            if (!this.termsTarget.checked) {
                isValid = false;
            }
        }

        // Update button state
        if (isValid) {
            this.submitButtonTarget.removeAttribute('disabled');
            this.submitButtonTarget.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            this.submitButtonTarget.setAttribute('disabled', 'disabled');
            this.submitButtonTarget.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    /**
     * Show error message
     */
    showError(targetName, message) {
        const errorTarget = this[targetName + 'Target'];
        if (errorTarget) {
            errorTarget.textContent = message;
            errorTarget.classList.remove('hidden');
        }
    }

    /**
     * Hide error message
     */
    hideError(targetName) {
        const errorTarget = this[targetName + 'Target'];
        if (errorTarget) {
            errorTarget.textContent = '';
            errorTarget.classList.add('hidden');
        }
    }

    /**
     * Handle form submission (can add additional validation here)
     */
    handleSubmit(event) {
        let isValid = true;

        // Validate all fields
        if (this.hasEmailTarget) {
            if (!this.validateEmail({ target: this.emailTarget })) {
                isValid = false;
            }
        }

        if (this.hasPasswordTarget) {
            if (!this.validatePassword({ target: this.passwordTarget })) {
                isValid = false;
            }
        }

        if (this.hasPasswordConfirmTarget) {
            if (!this.validatePasswordMatch({ target: this.passwordConfirmTarget })) {
                isValid = false;
            }
        }

        if (!isValid) {
            event.preventDefault();
            event.stopPropagation();
        }
    }
}
