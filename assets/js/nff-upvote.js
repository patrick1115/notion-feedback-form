/**
 * Upvote handling
 */
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.nff-upvote-btn').forEach(button => {
        button.addEventListener('click', function() {
            const pageId = this.dataset.id;
            const countSpan = this.nextElementSibling;
            let currentVotes = parseInt(countSpan.textContent, 10);
            let newVotes = currentVotes + 1;

            // Check if already voted
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
                    nonce: nff_ajax.nonce,
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

            // Optimistic UI update - show as updated immediately
            countSpan.textContent = newVotes;
        });
    });
});
