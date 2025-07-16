<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const vkidContainer = ref<HTMLDivElement | null>(null);

onMounted(() => {
    // Загрузка скрипта
    const script = document.createElement('script');
    script.src = "https://unpkg.com/@vkid/sdk@<3.0.0/dist-sdk/umd/index.js";
    script.async = true;
    script.onload = initVKID; // Инициализация после загрузки скрипта
    document.head.appendChild(script);
});

const initVKID = () => {
    if ('VKIDSDK' in window) {
        const VKID = window.VKIDSDK;

        VKID.Config.init({
            app: 53915902,
            redirectUrl: 'https://www.ping-check.ru',
            responseMode: VKID.ConfigResponseMode.Callback,
            source: VKID.ConfigSource.LOWCODE,
            scope: '', // Заполните нужными доступами по необходимости
        });

        const oneTap = new VKID.OneTap();

        oneTap.render({
            container: vkidContainer.value, // Используем ref для контейнера
            showAlternativeLogin: true
        })
            .on(VKID.WidgetEvents.ERROR, vkidOnError)
            .on(VKID.OneTapInternalEvents.LOGIN_SUCCESS, (payload) => {
                const code = payload.code;
                const deviceId = payload.device_id;

                VKID.Auth.exchangeCode(code, deviceId)
                    .then(vkidOnSuccess)
                    .catch(vkidOnError);
            });
    }
};

const vkidOnSuccess = (data: any) => {
    // Обработка полученного результата
    console.log('Успех:', data);
};

const vkidOnError = (error: any) => {
    // Обработка ошибки
    console.error('Ошибка:', error);
};
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="flex min-h-screen flex-col items-center bg-[#0a0a0a] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
        <header class="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl">
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <div ref="vkidContainer"></div> <!-- Контейнер для виджета VKID -->
                </template>
            </nav>
        </header>
        <div class="flex w-full justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
            <main class="w-full max-w-[335px] flex-col-reverse overflow-hidden rounded-lg lg:max-w-4xl lg:flex-row text-white">
                <div><h1 class="text-white text-4xl font-bold text-center mt-20">Ping-Check</h1></div>
                <div class="mt-10 text-center">Пингуй и
                    <span
                        class="ml-1 inline-flex space-x-1 font-medium text-[#f53003] underline underline-offset-4 dark:text-[#FF4433]"
                    >чекай</span>
                </div>
            </main>
        </div>
        <div class="hidden h-14.5 lg:block"></div>
    </div>
    <div class="flex w-full justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0 bg-red-600">
    </div>
</template>

<style>
.vk-button:hover {
    background-color: #4a76a8;
    transition: background-color 0.2s ease;
}
</style>
