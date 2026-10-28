export const ExternalLink = {

    init(amplify) {
        this.registerClickEvent();
        return this;
    },

    registerClickEvent() {
        document.addEventListener('click', function (event) {
            // 1. Find the closest anchor tag (handles clicks on icons/spans inside the link)
            const anchor = event.target.closest('a');

            // 2. Safety check: Exit early if it's not a link, or if it doesn't have an href
            if (!anchor || !anchor.href) return;

            // 3. Ignore special links like javascript:, mailto:, or tel:
            if (anchor.protocol !== 'http:' && anchor.protocol !== 'https:') return;

            // 4. Compare hostnames to identify external links
            if (anchor.hostname !== window.location.hostname) {

                // 5. STOP the browser from opening the link immediately
                event.preventDefault();

                // 6. Capture the external URL destination
                const externalUrl = anchor.href;
                const targetTab = anchor.target ?? '_blank';

                // 7. Trigger your custom confirmation UI modal
                Amplify.ExternalLink.externalLinkChallenge(externalUrl, targetTab);
            }
        });
    },

    externalLinkChallenge(url, openInNewTab = '_blank') {
        Amplify.confirm(`Redirecting to an external URL: <b>${url}</b>.`, 'External Link', 'Agree', {
            icon: 'warning',
            customClass: {
                confirmButton: 'btn btn-warning',
                cancelButton: 'btn btn-outline-secondary',
            }

        }).then((result)=> {
            if (result.isConfirmed) {
                window.open(url, openInNewTab, 'noopener,noreferrer,nofollow');
            }
        })
    }
}