/**
 * User form validation for names
 */

document.addEventListener('DOMContentLoaded', function() {
    const firstNameInput = document.querySelector('input[name*="[first_name]"]');
    const lastNameInput = document.querySelector('input[name*="[last_name]"]');

    if (!firstNameInput) {
        return;
    }

    // Additional validation for Polish names
    function validatePolishName(name) {
        const polishNamePattern = /^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]+$/;
        return polishNamePattern.test(name);
    }

    // Real-time name validation
    firstNameInput.addEventListener('input', function() {
        const name = this.value.trim();
        
        if (name && !validatePolishName(name)) {
            this.setCustomValidity('Imię może zawierać tylko litery (w tym polskie znaki diakrytyczne)');
        } else {
            this.setCustomValidity('');
        }
    });

    // Last name validation
    if (lastNameInput) {
        lastNameInput.addEventListener('input', function() {
            const name = this.value.trim();
            const lastNamePattern = /^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ\s-]+$/;
            
            if (name && !lastNamePattern.test(name)) {
                this.setCustomValidity('Nazwisko może zawierać tylko litery, spacje i myślniki');
            } else {
                this.setCustomValidity('');
            }
        });
    }
});