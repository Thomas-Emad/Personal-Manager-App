<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { 
    LayoutDashboard, 
    Calendar, 
    Map, 
    Timer, 
    BookOpen, 
    BarChart3, 
    Settings, 
    Languages, 
    CheckCircle2, 
    AlertCircle,
    Bell
} from 'lucide-vue-next';

defineProps<{
    title?: string;
}>();

const page = usePage();
const locale = computed(() => (page.props.locale as string) || 'en');
const isArabic = computed(() => locale.value === 'ar');
const direction = computed(() => (isArabic.value ? 'rtl' : 'ltr'));

const flash = computed(() => (page.props.flash as Record<string, any>) || {});

const toggleLanguage = () => {
    const nextLocale = isArabic.value ? 'en' : 'ar';
    router.post('/settings/locale', { locale: nextLocale }, { preserveScroll: true });
};

const navItems = computed(() => [
    {
        name: isArabic.value ? 'اليوم' : 'Today',
        route: 'dashboard',
        href: '/',
        icon: LayoutDashboard,
        active: page.url === '/',
    },
    {
        name: isArabic.value ? 'التقويم' : 'Calendar',
        route: 'calendar.index',
        href: '/calendar',
        icon: Calendar,
        active: page.url.startsWith('/calendar'),
    },
    {
        name: isArabic.value ? 'المسارات' : 'Roadmaps',
        route: 'roadmaps.index',
        href: '/roadmaps',
        icon: Map,
        active: page.url.startsWith('/roadmaps'),
    },
    {
        name: isArabic.value ? 'الجلسة' : 'Tracker',
        route: 'tracker.index',
        href: '/tracker',
        icon: Timer,
        active: page.url.startsWith('/tracker'),
    },
    {
        name: isArabic.value ? 'المكتبة' : 'Library',
        route: 'library.index',
        href: '/library',
        icon: BookOpen,
        active: page.url.startsWith('/library'),
    },
]);
</script>

<template>
    <div :dir="direction" class="min-h-screen bg-slate-950 text-slate-100 font-sans pb-24 sm:pb-20">
        
        <!-- Top App Bar -->
        <header class="sticky top-0 z-30 bg-slate-900/90 backdrop-blur-md border-b border-slate-800 px-4 py-3 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2.5">
                <Link href="/" class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-md shadow-indigo-600/30">
                    <Timer class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="font-bold text-sm tracking-tight text-white leading-none">
                        {{ isArabic ? 'مدير التعلم والإنتاجية' : 'Personal Manager' }}
                    </h1>
                    <span class="text-[10px] text-indigo-400 font-medium">
                        {{ isArabic ? 'تطبيق محلي متكامل' : 'Local-First Productivity' }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Analytics Shortcut -->
                <Link 
                    href="/analytics" 
                    :class="page.url.startsWith('/analytics') ? 'bg-indigo-600/20 text-indigo-400 border-indigo-500/30' : 'bg-slate-800/80 text-slate-400 border-slate-700'"
                    class="p-2 rounded-xl border hover:text-white transition active:scale-95"
                    :title="isArabic ? 'التحليلات والتقدم' : 'Progress & Analytics'"
                >
                    <BarChart3 class="w-4 h-4" />
                </Link>

                <!-- Settings Shortcut -->
                <Link 
                    href="/settings" 
                    :class="page.url.startsWith('/settings') ? 'bg-indigo-600/20 text-indigo-400 border-indigo-500/30' : 'bg-slate-800/80 text-slate-400 border-slate-700'"
                    class="p-2 rounded-xl border hover:text-white transition active:scale-95"
                    :title="isArabic ? 'الإعدادات والنسخ الاحتياطي' : 'Settings & Backups'"
                >
                    <Settings class="w-4 h-4" />
                </Link>

                <!-- Language Toggle -->
                <button
                    @click="toggleLanguage"
                    type="button"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-semibold text-slate-200 transition active:scale-95 shadow-sm"
                >
                    <Languages class="w-3.5 h-3.5 text-indigo-400" />
                    <span>{{ isArabic ? 'EN' : 'عربي' }}</span>
                </button>
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="max-w-3xl mx-auto px-4 pt-4 space-y-2">
            <div v-if="flash.success" class="bg-emerald-950/80 border border-emerald-800 text-emerald-200 p-3 rounded-xl flex items-start gap-2.5 text-xs shadow-sm">
                <CheckCircle2 class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                <div class="flex-1">{{ flash.success }}</div>
            </div>

            <div v-if="flash.error" class="bg-rose-950/80 border border-rose-800 text-rose-200 p-3 rounded-xl flex items-start gap-2.5 text-xs shadow-sm">
                <AlertCircle class="w-4 h-4 text-rose-400 shrink-0 mt-0.5" />
                <div class="flex-1">{{ flash.error }}</div>
            </div>
        </div>

        <!-- Main Screen Content Slot -->
        <main class="max-w-3xl mx-auto px-4 py-4">
            <slot />
        </main>

        <!-- Bottom Mobile Navigation Bar -->
        <nav class="fixed bottom-0 inset-x-0 z-40 bg-slate-900/95 backdrop-blur-md border-t border-slate-800/80 px-2 py-1.5 shadow-2xl">
            <div class="max-w-md mx-auto grid grid-cols-5 gap-1">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    :class="item.active ? 'text-indigo-400 font-semibold' : 'text-slate-400 hover:text-slate-200'"
                    class="flex flex-col items-center justify-center py-1.5 px-1 rounded-xl transition group active:scale-95"
                >
                    <component 
                        :is="item.icon" 
                        class="w-5 h-5 transition-transform group-hover:-translate-y-0.5" 
                        :class="item.active ? 'text-indigo-400' : 'text-slate-400'"
                    />
                    <span class="text-[10px] mt-1 truncate">{{ item.name }}</span>
                    <span 
                        v-if="item.active" 
                        class="w-1 h-1 rounded-full bg-indigo-500 mt-0.5"
                    ></span>
                </Link>
            </div>
        </nav>

    </div>
</template>
