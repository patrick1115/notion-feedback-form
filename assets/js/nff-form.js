/**
 * Form submission handling
 */
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('notion-feedback-form');
    const msgDiv = document.getElementById('nff-message');

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const feedback = this.feedback.value.trim();
            if (!feedback) return;

            // Show loading indicator
            msgDiv.textContent = 'Submitting...';

            fetch(nff_ajax.ajax_url, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'nff_submit_feedback',
                    nonce: nff_ajax.nonce,
                    name: this.name.value,
                    feedback: feedback,
                    first_name: this.first_name.value,
                    last_name: this.last_name.value
                })
            })
            .then(res => res.text())
            .then(msg => {
                msgDiv.textContent = msg;
                this.reset();
            })
            .catch(error => {
                console.error('Form submission error:', error);
                msgDiv.textContent = 'An error occurred. Please try again.';
            });
        });
    }
});
