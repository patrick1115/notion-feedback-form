document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('notion-feedback-form');
    const msgDiv = document.getElementById('nff-message');

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const feedback = this.feedback.value.trim();
            if (!feedback) return;

            fetch(nff_ajax.ajax_url, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'nff_submit_feedback',
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
            });
        });
    }

    // Upvote logic
    document.querySelectorAll('.nff-upvote-btn').forEach(button => {
        button.addEventListener('click', function () {
            const pageId = this.dataset.id;
            const countSpan = this.nextElementSibling;
            let currentVotes = parseInt(countSpan.textContent, 10);
            let newVotes = currentVotes + 1;

            const voted = localStorage.getItem(`nff-voted-${pageId}`);
            if (voted) {
                alert('You have already upvoted this comment.');
                return;
            }

            localStorage.setItem(`nff-voted-${pageId}`, 'true');
            button.classList.add('voted');

            fetch(nff_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'nff_upvote',
                    page_id: pageId,
                    upvotes: newVotes
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    countSpan.textContent = data.upvotes;
                } else {
                    countSpan.textContent = currentVotes;
                    button.classList.remove('voted');
                    alert('Failed to save upvote to Notion. Reason: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error('Upvote network error:', err);
                countSpan.textContent = currentVotes;
                button.classList.remove('voted');
                localStorage.removeItem(`nff-voted-${pageId}`);
                alert('Network error during upvote.');
            });

            countSpan.textContent = newVotes;
        });
    });
});
