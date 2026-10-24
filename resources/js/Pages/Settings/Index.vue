<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    Settings, 
    Bell, 
    Download, 
    Upload, 
    ShieldCheck, 
    Lock, 
    Globe, 
    Clock, 
    CheckCircle2, 
    AlertTriangle, 
    Smartphone,
    FileJson,
    RefreshCw
} from 'lucide-vue-next';

interface SettingsData {
    timezone: string;
    default_study_minutes: number;
    default_break_minutes: number;
    notifications_enabled: boolean;
    locale: string;
    native_bridge_active: boolean;
    permission_status: {
        has_permission: boolean;
        exact_alarm_allowed: boolean;
    };
}

const props = defineProps<{
    settings: SettingsData;
}>();

const page = usePage();
const isArabic = computed(() => page.props.locale === 'ar');

// Common timezones
const commonTimezones = [
    { value: 'Africa/Cairo', label: 'Cairo (UTC+2 / UTC+3)' },
    { value: 'Asia/Riyadh', label: 'Riyadh / Mecca (UTC+3)' },
    { value: 'Asia/Dubai', label: 'Dubai (UTC+4)' },
    { value: 'UTC', label: 'UTC (Coordinated Universal Time)' },
    { value: 'Europe/London', label: 'London (UTC+0 / UTC+1)' },
    { value: 'Europe/Paris', label: 'Paris (UTC+1 / UTC+2)' },
    { value: 'America/New_York', label: 'New York (UTC-5 / UTC-4)' },
    { value: 'America/Los_Angeles', label: 'Los Angeles (UTC-8 / UTC-7)' },
];

// Study preferences form
const prefForm = useForm({
    timezone: props.settings.timezone || 'UTC',
    default_study_minutes: props.settings.default_study_minutes || 45,
    default_break_minutes: props.settings.default_break_minutes || 15,
    notifications_enabled: props.settings.notifications_enabled ?? true,
});

const savePreferences = () => {
    prefForm.post('/settings', {
        preserveScroll: true,
    });
};

// Export Backup form
const exportPassword = ref('');
const triggerExport = () => {
    let url = '/settings/backup/export';
    if (exportPassword.value.trim()) {
        url += `?password=${encodeURIComponent(exportPassword.value.trim())}`;
    }
    window.location.href = url;
};

// Import Backup form
const importMode = ref<'replace' | 'merge'>('merge');
const importPassword = ref('');
const backupFileInput = ref<HTMLInputElement | null>(null);
const importForm = useForm({
    backup_file: null as File | null,
    mode: 'merge',
    password: '',
});

const onBackupFileSelected = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        importForm.backup_file = target.files[0];
    }
};

const submitRestore = () => {
    if (!importForm.backup_file) {
        alert(isArabic.value ? 'يرجى اختيار ملف النسخة الاحتياطية (.pma)' : 'Please select a .pma backup file.');
        return;
    }

    const warning = importMode.value === 'replace'
        ? (isArabic.value ? 'تحذير: نمط "الاستبدال الكامل" سيحذف بيانات التطبيق الحالية ويعوضها بالنسخة الاحتياطية. هل تود الاستمرار؟' : 'Warning: "Replace" mode will replace current application data with the backup. Continue?')
        : (isArabic.value ? 'سيتم دمج بيانات النسخة الاحتياطية مع بياناتك الحالية. هل تود المتابعة؟' : 'Backup data will be merged with your existing records. Continue?');

    if (!confirm(warning)) return;

    importForm.mode = importMode.value;
    importForm.password = importPassword.value;
    importForm.post('/settings/backup/import', {
        preserveScroll: true,
        onSuccess: () => {
            importForm.reset();
            importPassword.value = '';
            if (backupFileInput.value) backupFileInput.value.value = '';
        },
    });
};

// Import Roadmap JSON
const roadmapFileInput = ref<HTMLInputElement | null>(null);
const roadmapForm = useForm({
    json_file: null as File | null,
});

const onRoadmapFileSelected = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        roadmapForm.json_file = target.files[0];
    }
};

const submitRoadmapImport = () => {
    if (!roadmapForm.json_file) {
        alert(isArabic.value ? 'يرجى تحديد ملف .roadmap.json' : 'Please select a .roadmap.json file');
        return;
    }
    roadmapForm.post('/settings/roadmap/import', {
        preserveScroll: true,
        onSuccess: () => {
            roadmapForm.reset();
            if (roadmapFileInput.value) roadmapFileInput.value.value = '';
        },
    });
};

// Notification Test
const testingNotification = ref(false);
const triggerTestNotification = () => {
    testingNotification.value = true;
    router.post('/settings/notify-test', {}, {
        preserveScroll: true,
        onFinish: () => {
            testingNotification.value = false;
        },
    });
};

// Language toggle
const toggleLanguage = () => {
    const nextLocale = isArabic.value ? 'en' : 'ar';
    router.post('/settings/locale', { locale: nextLocale }, { preserveScroll: true });
};
</script>

<template>
    <AppLayout :title="isArabic ? 'الإعدادات والنسخ الاحتياطي' : 'Settings & Backups'">
        <div class="space-y-6">
            
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-white flex items-center gap-2">
                        <Settings class="w-6 h-6 text-indigo-400" />
                        <span>{{ isArabic ? 'الإعدادات والنسخ الاحتياطي' : 'Settings & Data Portability' }}</span>
                    </h1>
                    <p class="text-xs text-slate-400 mt-0.5">
                        {{ isArabic ? 'تخصيص تفضيلات المذاكرة، التنبيهات، والنسخ الاحتياطي المشفر' : 'Configure study preferences, local notifications, and encrypted backups' }}
                    </p>
                </div>

                <!-- Language Toggle Button -->
                <button
                    @click="toggleLanguage"
                    class="px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs font-semibold text-slate-200 hover:text-white flex items-center gap-1.5 transition active:scale-95"
                >
                    <Globe class="w-4 h-4 text-indigo-400" />
                    <span>{{ isArabic ? 'English (LTR)' : 'العربية (RTL)' }}</span>
                </button>
            </div>

            <!-- Section 1: Study & Timezone Preferences -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 space-y-4 shadow-sm">
                <h2 class="text-sm font-bold text-white flex items-center gap-2">
                    <Clock class="w-4 h-4 text-indigo-400" />
                    <span>{{ isArabic ? 'تفضيلات الجلسات والتوقيت' : 'Session & Timezone Preferences' }}</span>
                </h2>

                <form @submit.prevent="savePreferences" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Timezone -->
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">
                                {{ isArabic ? 'المنطقة الزمنية المحلية' : 'Local Timezone' }}
                            </label>
                            <select
                                v-model="prefForm.timezone"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                            >
                                <option v-for="tz in commonTimezones" :key="tz.value" :value="tz.value">
                                    {{ tz.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Notifications Switch -->
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">
                                {{ isArabic ? 'تفعيل الإشعارات المحلية' : 'Local Notifications' }}
                            </label>
                            <div class="flex items-center gap-3 h-9">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        v-model="prefForm.notifications_enabled" 
                                        class="sr-only peer"
                                    />
                                    <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                    <span class="ms-3 text-xs text-slate-300 font-medium">
                                        {{ prefForm.notifications_enabled ? (isArabic ? 'مفعلة' : 'Enabled') : (isArabic ? 'معطلة' : 'Disabled') }}
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Default Study Duration -->
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">
                                {{ isArabic ? 'مدة المذاكرة الافتراضية (دقيقة)' : 'Default Study Duration (mins)' }}
                            </label>
                            <input
                                v-model.number="prefForm.default_study_minutes"
                                type="number"
                                min="15"
                                max="180"
                                step="5"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        <!-- Default Break Duration -->
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">
                                {{ isArabic ? 'مدة الاستراحة الافتراضية (دقيقة)' : 'Default Break Duration (mins)' }}
                            </label>
                            <input
                                v-model.number="prefForm.default_break_minutes"
                                type="number"
                                min="5"
                                max="60"
                                step="5"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            :disabled="prefForm.processing"
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 transition disabled:opacity-50"
                        >
                            {{ isArabic ? 'حفظ التفضيلات' : 'Save Preferences' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Section 2: Local Notifications & Hardware Bridge (Module D) -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 space-y-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-white flex items-center gap-2">
                            <Bell class="w-4 h-4 text-emerald-400" />
                            <span>{{ isArabic ? 'محرك الإشعارات المحلي (Android AlarmManager)' : 'Local Notifications Engine' }}</span>
                        </h2>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            {{ isArabic ? 'إشعارات حقيقية مجدولة بدون خوادم سحابية تعمل حتى أثناء إغلاق التطبيق' : 'True offline reminders scheduled via Android AlarmManager without cloud dependencies' }}
                        </p>
                    </div>

                    <!-- Bridge status pill -->
                    <span 
                        :class="settings.native_bridge_active ? 'bg-emerald-950/80 text-emerald-300 border-emerald-800/60' : 'bg-slate-800 text-slate-400 border-slate-700'"
                        class="px-2.5 py-1 rounded-full text-[10px] font-semibold border flex items-center gap-1 shrink-0"
                    >
                        <span class="w-2 h-2 rounded-full" :class="settings.native_bridge_active ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500'"></span>
                        {{ settings.native_bridge_active ? (isArabic ? 'الجسر نشط' : 'Bridge Active') : (isArabic ? 'محاكاة / ويب' : 'Web / Standby') }}
                    </span>
                </div>

                <div class="bg-slate-950/70 border border-slate-800/80 rounded-xl p-3.5 space-y-2 text-xs">
                    <div class="flex items-center justify-between text-slate-300">
                        <span>{{ isArabic ? 'صلاحية الإشعارات' : 'Notification Permission' }}:</span>
                        <span class="font-semibold" :class="settings.permission_status.has_permission ? 'text-emerald-400' : 'text-amber-400'">
                            {{ settings.permission_status.has_permission ? (isArabic ? 'ممنوحة' : 'Granted') : (isArabic ? 'غير ممنوحة' : 'Not Granted') }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-slate-300">
                        <span>{{ isArabic ? 'المنبهات الدقيقة (Exact Alarms)' : 'Exact Alarms Allowed' }}:</span>
                        <span class="font-semibold" :class="settings.permission_status.exact_alarm_allowed ? 'text-emerald-400' : 'text-amber-400'">
                            {{ settings.permission_status.exact_alarm_allowed ? (isArabic ? 'مفعلة' : 'Yes') : (isArabic ? 'غير مفعلة' : 'No') }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <span class="text-[11px] text-slate-400">
                        {{ isArabic ? 'اختبار وصول إشعار خلال 5 ثوانٍ للتحقق من عمل النظام' : 'Test alarm dispatch within 5 seconds to verify vibration/sound' }}
                    </span>
                    <button
                        @click="triggerTestNotification"
                        :disabled="testingNotification"
                        class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-semibold text-slate-200 transition active:scale-95 disabled:opacity-50"
                    >
                        {{ testingNotification ? (isArabic ? 'جارٍ الإرسال...' : 'Scheduling...') : (isArabic ? 'إرسال إشعار تجريبي' : 'Send Test Alarm') }}
                    </button>
                </div>
            </div>

            <!-- Section 3: Full Backup & Restore (Module J) -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 space-y-4 shadow-sm">
                <div>
                    <h2 class="text-sm font-bold text-white flex items-center gap-2">
                        <ShieldCheck class="w-4 h-4 text-indigo-400" />
                        <span>{{ isArabic ? 'النسخ الاحتياطي الكامل واستعادة البيانات' : 'Full Backup & Data Restore' }}</span>
                    </h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        {{ isArabic ? 'تصدير أرشيف .pma مشفر بكلمة مرور (AES-256-GCM) أو استعادة بياناتك بأمان مع فحص التماسك' : 'Export password-encrypted .pma archives (AES-256-GCM) or restore with integrity verification' }}
                    </p>
                </div>

                <!-- Export Box -->
                <div class="bg-slate-950/70 border border-slate-800/80 rounded-xl p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-semibold text-white flex items-center gap-1.5">
                            <Download class="w-4 h-4 text-indigo-400" />
                            <span>{{ isArabic ? 'تصدير نسخة احتياطية كاملة (.pma)' : 'Export Full Application Archive' }}</span>
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-400">
                        {{ isArabic ? 'يحتوي الأرشيف على جميع المسارات، المهام، جلسات المذاكرة الفعلية، التقويم، ومواد المكتبة.' : 'Archive includes all roadmaps, phases, topics, tasks, study sessions, availability, and resources.' }}
                    </p>

                    <div class="space-y-2">
                        <label class="block text-[11px] text-slate-300">
                            {{ isArabic ? 'كلمة مرور للتشفير (اختياري - لحماية خصوصيتك)' : 'Encryption Password (Optional)' }}
                        </label>
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <Lock class="w-3.5 h-3.5 text-slate-500 absolute top-2.5 ltr:left-3 rtl:right-3" />
                                <input
                                    v-model="exportPassword"
                                    type="password"
                                    :placeholder="isArabic ? 'اتركه فارغاً لأرشيف غير مشفر' : 'Leave empty for unencrypted archive'"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-xl ltr:pl-8 rtl:pr-8 pr-3 py-1.5 text-xs text-white focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                            <button
                                @click="triggerExport"
                                class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 transition shrink-0 active:scale-95"
                            >
                                {{ isArabic ? 'تنزيل الأرشيف' : 'Download .pma' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Restore Box -->
                <div class="bg-slate-950/70 border border-slate-800/80 rounded-xl p-4 space-y-3">
                    <div class="text-xs font-semibold text-white flex items-center gap-1.5">
                        <Upload class="w-4 h-4 text-emerald-400" />
                        <span>{{ isArabic ? 'استعادة نسخة احتياطية من ملف (.pma)' : 'Restore Application From Backup' }}</span>
                    </div>

                    <form @submit.prevent="submitRestore" class="space-y-3">
                        <div>
                            <input
                                ref="backupFileInput"
                                type="file"
                                accept=".pma,.zip"
                                @change="onBackupFileSelected"
                                class="block w-full text-xs text-slate-400 file:mr-4 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700"
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] text-slate-300 mb-1">
                                    {{ isArabic ? 'نمط الاستعادة' : 'Restore Mode' }}
                                </label>
                                <select
                                    v-model="importMode"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-xl px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-indigo-500"
                                >
                                    <option value="merge">{{ isArabic ? 'دمج مع البيانات الحالية (Merge)' : 'Merge with current data' }}</option>
                                    <option value="replace">{{ isArabic ? 'استبدال كامل للبيانات (Replace)' : 'Clean Replace (Overwrites all)' }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] text-slate-300 mb-1">
                                    {{ isArabic ? 'كلمة المرور (إذا كان الأرشيف مشفراً)' : 'Decryption Password (if encrypted)' }}
                                </label>
                                <input
                                    v-model="importPassword"
                                    type="password"
                                    placeholder="••••••••"
                                    class="w-full bg-slate-900 border border-slate-800 rounded-xl px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                        </div>

                        <div class="flex justify-end pt-1">
                            <button
                                type="submit"
                                :disabled="importForm.processing"
                                class="px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-md shadow-emerald-600/30 transition disabled:opacity-50"
                            >
                                {{ isArabic ? 'بدء الاستعادة والتحقق' : 'Verify & Restore' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Roadmap.sh Importer Box -->
                <div class="bg-slate-950/70 border border-slate-800/80 rounded-xl p-4 space-y-3">
                    <div class="text-xs font-semibold text-white flex items-center gap-1.5">
                        <FileJson class="w-4 h-4 text-amber-400" />
                        <span>{{ isArabic ? 'استيراد مسار تعليمي بتنسيق (.roadmap.json)' : 'Import Roadmap Metadata (.roadmap.json)' }}</span>
                    </div>

                    <p class="text-[11px] text-slate-400">
                        {{ isArabic ? 'استيراد هيكل المسارات والمواضيع والمهام المستقلة وتوليد معرفات UUID آمنة جديدة تلقائياً' : 'Import external JSON roadmap hierarchies with automatic UUID generation and DAG validation' }}
                    </p>

                    <form @submit.prevent="submitRoadmapImport" class="flex items-center gap-2">
                        <input
                            ref="roadmapFileInput"
                            type="file"
                            accept=".json"
                            @change="onRoadmapFileSelected"
                            class="block w-full text-xs text-slate-400 file:mr-4 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700"
                        />
                        <button
                            type="submit"
                            :disabled="roadmapForm.processing"
                            class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30 transition shrink-0 disabled:opacity-50"
                        >
                            {{ isArabic ? 'استيراد المسار' : 'Import' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Footer info -->
            <div class="text-center text-[11px] text-slate-500 pb-4">
                Personal Manager Mobile • Local-First Architecture • Offline Relational Database
            </div>

        </div>
    </AppLayout>
</template>
