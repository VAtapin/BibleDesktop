import { createApp } from 'vue';
import './bootstrap';
import '../css/app.css';
import BibleDesktopApp from './components/BibleDesktopApp.vue';

/**
 * VK Mini Apps require VKWebAppInit. The bridge is bundled locally and loaded
 * only when VK launch parameters are present, so normal browsers and WebViews
 * make no requests to VK or Telegram resources.
 */
async function initializeVkMiniApp(): Promise<void> {
    const parameters = new URLSearchParams(window.location.search);

    if (!parameters.has('vk_app_id') && !parameters.has('vk_platform')) {
        return;
    }

    try {
        const { default: bridge } = await import('@vkontakte/vk-bridge');

        await bridge.send('VKWebAppInit');
    } catch (error) {
        console.warn('VK Mini App initialization failed.', error);
    }
}

async function startApplication(): Promise<void> {
    await Promise.race([
        initializeVkMiniApp(),
        new Promise<void>((resolve) => window.setTimeout(resolve, 800)),
    ]);
    createApp(BibleDesktopApp).mount('#app');
}

void startApplication();
