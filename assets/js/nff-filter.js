/**
 * Filter functionality for feedback comments
 */
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.nff-filter-btn');
    const comments = document.querySelectorAll('.nff-comment');

    // Set "All" as default active button on page load
    const allButton = document.querySelector('.nff-filter-btn[data-tag="All"]');
    if (allButton) {
        allButton.classList.add('active');
    }

    buttons.forEach(button => {
        button.addEventListener('click', () => {
            const tag = button.dataset.tag;

            comments.forEach(comment => {
                const commentTag = comment.dataset.tag;
                if (tag === 'All' || commentTag === tag) {
                    comment.style.display = '';
                } else {
                    comment.style.display = 'none';
                }
            });

            // Highlight the active button
            buttons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
        });
    });
});
