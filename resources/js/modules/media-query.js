export const MediaQuery = {
    Amplify: {},
    breakpoints : {
        wide: 1440,
        desktop: 1200,
        laptop: 991,
        tablet: 768,
        mobile: 567,
    },

    init(amplify) {

        this.breakpoints = amplify.config.screen.breakpoints;

        if (!sessionStorage.getItem('screen_initialized')) {
            sessionStorage.setItem('screen_initialized', '1');
            window.location.reload();
        } else {
            this.setCookie();
        }
        this.updateCookie();

        return this;
    },

    updateCookie() {
        window.addEventListener('resize', () => {
            Amplify.MediaQuery.setCookie();
        });
    },

    setCookie() {
        const width = window.innerWidth;
        const height = window.innerHeight;

        document.cookie = `mw=${width}; path=/; SameSite=Lax`;
        document.cookie = `md=${height}; path=/; SameSite=Lax`;
    },

    get width() {
        return window.innerWidth;
    },

    screen() {
        const width = this.width;

        for (const [name, minWidth] of Object.entries(this.breakpoints)) {
            if (width >= minWidth) {
                return name;
            }
        }

        return 'mobile';
    },

    isDesktop() {
        return this.screen() === 'desktop';
    },

    isLaptop() {
        return this.screen() === 'laptop';
    },

    isTablet() {
        return this.screen() === 'tablet';
    },

    isMobile() {
        return this.screen() === 'mobile';
    }
}