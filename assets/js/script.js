document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registrationForm');
    
    form.addEventListener('submit', function(event) {
        // You can add more complex client-side validation here
        const clientName = document.getElementById('clientName').value;
        const email = document.getElementById('email').value;
        
        if (!clientName || !email) {
            alert('Please fill in all required fields.');
            event.preventDefault();
            return;
        }
        
        // Optional: Show a loading state on the button
        const submitBtn = form.querySelector('.submit-btn');
        submitBtn.textContent = 'Sending...';
        submitBtn.disabled = true;
    });

    // Handle file size validation (example: max 5MB)
    const fileInput = document.getElementById('inspiration');
    fileInput.addEventListener('change', function() {
        const files = this.files;
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        for (let i = 0; i < files.length; i++) {
            if (files[i].size > maxSize) {
                alert(`File "${files[i].name}" is too large. Max size is 5MB.`);
                this.value = ''; // Clear the selection
                break;
            }
        }
    });

    // Handle location dropdown logic
    const locationSelect = document.getElementById('location');
    const manualLocationGroup = document.getElementById('manualLocationGroup');
    const manualLocationInput = document.getElementById('manualLocation');

    locationSelect.addEventListener('change', function() {
        if (this.value === 'other') {
            manualLocationGroup.style.display = 'block';
            manualLocationInput.required = true;
        } else {
            manualLocationGroup.style.display = 'none';
            manualLocationInput.required = false;
        }
    });

    // Handle Cake Table Selection
    const cakeTableOptions = document.querySelectorAll('#cakeTableOptions .selection-item');
    const cakeTableInput = document.getElementById('cakeTableType');

    cakeTableOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all
            cakeTableOptions.forEach(opt => opt.classList.remove('selected'));
            // Add to clicked
            this.classList.add('selected');
            // Update hidden input
            cakeTableInput.value = this.getAttribute('data-value');
        });
    });

    // Handle Custom Package Details Logic
    const decorPackageRadios = document.querySelectorAll('input[name="decorPackage"]');
    const customPackageDetails = document.getElementById('customPackageDetails');
    const budgetInput = document.getElementById('estimatedBudget');
    const requirementsInput = document.getElementById('customRequirements');

    decorPackageRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'custom') {
                customPackageDetails.style.display = 'block';
                budgetInput.required = true;
                requirementsInput.required = true;
            } else {
                customPackageDetails.style.display = 'none';
                budgetInput.required = false;
                requirementsInput.required = false;
            }
        });
    });
});
