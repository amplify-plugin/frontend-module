export const ExternalLink = {
    allowedHosts: [],
    init(amplify) {
        this.allowedHosts = amplify.config.allowedHosts ?? [];
        this.registerClickEvent();
        return this;
    },

    isAllowedUrl(url) {
        try {
            const getRootDomain = (hostname) => {
                const parts = hostname.toLowerCase().split('.');
                return parts.slice(-2).join('.');
            };

            const targetHost = new URL(url, window.location.origin).hostname;

            const allowedHosts = this.allowedHosts;

            const allowedDomains = [
                getRootDomain(window.location.hostname.toLowerCase()),
                ...allowedHosts.map(host => {
                    try {
                        return getRootDomain(new URL(host).hostname);
                    } catch {
                        return getRootDomain(host);
                    }
                })
            ];

            return allowedDomains.includes(getRootDomain(targetHost));
        } catch {
            return false;
        }
    },

    registerClickEvent() {
        document.addEventListener('click', (event) => {
            // 1. Find the closest anchor tag (handles clicks on icons/spans inside the link)
            const anchor = event.target.closest('a');

            // 2. Safety check: Exit early if it's not a link, or if it doesn't have an href
            if (!anchor || !anchor.href) return;

            // 3. Ignore special links like javascript:, mailto:, or tel:
            if (anchor.protocol !== 'http:' && anchor.protocol !== 'https:') return;

            // 4. Compare hostnames to identify external links
            const externalUrl = anchor.href;
            if (!Amplify.ExternalLink.isAllowedUrl(externalUrl)) {

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

        }).then((result) => {
            if (result.isConfirmed) {
                window.open(url, openInNewTab, 'noopener,noreferrer,nofollow');
            }
        })
    }
}