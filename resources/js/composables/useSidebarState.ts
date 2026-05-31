import { ref } from 'vue';

const cookieLifetime = 60 * 60 * 24 * 365;

export function useSidebarState(initiallyOpen = true) {
    const sidebarOpen = ref(initiallyOpen);

    const persist = (isOpen: boolean): void => {
        if (typeof document === 'undefined') {
            return;
        }

        document.cookie = `sidebar_state=${isOpen ? 'true' : 'false'}; path=/; max-age=${cookieLifetime}; samesite=lax`;
    };

    const setSidebarOpen = (isOpen: boolean): void => {
        sidebarOpen.value = isOpen;
        persist(isOpen);
    };

    const toggleSidebar = (): void => {
        setSidebarOpen(!sidebarOpen.value);
    };

    return {
        sidebarOpen,
        setSidebarOpen,
        toggleSidebar,
    };
}
