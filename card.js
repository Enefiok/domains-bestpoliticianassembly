let currentStep = 1;

        // Function called when user clicks "Register Now" on a card
        function selectDestination(location, date) {
            document.getElementById('cards-view').classList.add('hidden');
            document.getElementById('form-view').classList.remove('hidden');

            // Update UI text
            document.getElementById('display-location').innerText = location;
            document.getElementById('display-date').innerText = date;
            
            // Update Hidden Input fields for Email Submission
            document.getElementById('input-destination').value = `${location} (${date})`;
            document.getElementById('email-subject').value = `New Registration for ${location} - ${date}`;

            // Update dynamic question text inside steps
            document.querySelector('.fee-question-label').innerText = `The Best Politicians ${location} Conference has a registration fee. How do you plan on paying this registration cost? *`;
            document.querySelector('.motivation-label').innerText = `Why do you want to participate in the Best Politicians ${location} Conference? (150 - 250 words) *`;

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function showCardsView() {
            document.getElementById('form-view').classList.add('hidden');
            document.getElementById('cards-view').classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Multi-Step Form Navigation Logic
        function nextStep(step) {
            const currentStepEl = document.querySelector(`.form-step[data-step="${step}"]`);
            
            // Simple validation check before advancing
            const inputs = currentStepEl.querySelectorAll('input[required], select[required], textarea[required]');
            let isValid = true;
            inputs.forEach(input => {
                if (!input.value) {
                    isValid = false;
                    input.classList.add('border-red-500');
                } else {
                    input.classList.remove('border-red-500');
                }
            });

            if (!isValid) {
                alert('Please fill out all required fields before proceeding.');
                return;
            }

            currentStep = step + 1;
            updateStepView();
        }

        function prevStep(step) {
            currentStep = step - 1;
            updateStepView();
        }

        function updateStepView() {
            document.querySelectorAll('.form-step').forEach(el => el.classList.add('hidden'));
            document.querySelector(`.form-step[data-step="${currentStep}"]`).classList.remove('hidden');

            let progress = '33%';
            if (currentStep === 2) progress = '66%';
            if (currentStep === 3) progress = '100%';

            document.getElementById('step-indicator').innerText = `Step ${currentStep} of 3`;
            document.getElementById('progress-text').innerText = progress;
            document.getElementById('progress-bar').style.width = progress;

            window.scrollTo({ top: 100, behavior: 'smooth' });
        }