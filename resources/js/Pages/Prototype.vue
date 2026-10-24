<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { 
    Bell, 
    Database, 
    Languages, 
    Smartphone, 
    CheckCircle2, 
    AlertCircle, 
    Plus, 
    Trash2, 
    Clock, 
    Cpu, 
    ShieldCheck,
    Layers
} from 'lucide-vue-next';

interface PrototypeItem {
    id: string;
    title: string;
    details: string | null;
    created_at: string;
    updated_at: string;
}

interface SystemInfo {
    php_version: string;
    os: string;
    os_family: string;
    sqlite_path: string;
    sqlite_journal_mode: string;
    native_bridge_active: boolean;
    total_items_persisted: number;
    locale: string;
    direction: 'ltr' | 'rtl';
    app_id: string;
    server_time: string;
}

interface FlashData {
    success?: string | null;
    error?: string | null;
    notification?: Record<string, any> | null;
}

const props = defineProps<{
    items: PrototypeItem[];
    systemInfo: SystemInfo;
    flash: FlashData;
}>();

const isArabic = computed(() => props.systemInfo.locale === 'ar');

// New Item Form
const itemForm = useForm({
    title: '',
    details: '',
});

const submitItem = () => {
    if (!itemForm.title.trim()) return;
    itemForm.post('/prototype/items', {
        preserveScroll: true,
        onSuccess: () => {
            itemForm.reset();
        },
    });
};

const deleteItem = (id: string) => {
    if (confirm(isArabic.value ? 'هل أنت متأكد من حذف هذا العنصر؟' : 'Are you sure you want to delete this item?')) {
        router.delete(`/prototype/items/${id}`, {
            preserveScroll: true,
        });
    }
};

// Notification Form
const notifDelay = ref(10);
const isSchedulingNotif = ref(false);

const scheduleNotification = () => {
    isSchedulingNotif.value = true;
    router.post('/prototype/notify', {
        seconds: notifDelay.value,
        title: isArabic.value ? 'تذكير جلسة التعلم' : 'Study Session Reminder',
        body: isArabic.value 
            ? `حان وقت البدء في جلستك المجدولة! (تأخير ${notifDelay.value} ثوانٍ)` 
            : `Time to start your scheduled study session! (${notifDelay.value}s delay)`,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isSchedulingNotif.value = false;
        },
    });
};

// Language Toggle
const toggleLanguage = () => {
    const nextLocale = isArabic.value ? 'en' : 'ar';
    router.post('/prototype/locale', {
        locale: nextLocale,
    }, {
        preserveScroll: true,
    });
};
</script>

<template>
    <main 
        :dir="systemInfo.direction" 
        class="min-h-screen bg-slate-950 text-slate-100 font-sans p-4 sm:p-6 lg:p-8"
    >
        <div class="max-w-2xl mx-auto space-y-6">
            
            <!-- Header Bar -->
            <header class="flex items-center justify-between bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20">
                        <Smartphone class="w-6 h-6" />
                    </div>
                    <div>
                        <h1 class="font-bold text-lg leading-tight text-white">
                            {{ isArabic ? 'نموذج تطبيق التعلم الشخصي' : 'Personal Learning & Productivity' }}
                        </h1>
                        <p class="text-xs text-slate-400">
                            {{ isArabic ? 'نموذج التحقق الأولي لنظام أندرويد' : 'Minimal Android Native Prototype' }}
                        </p>
                    </div>
                </div>

                <!-- Language Toggle Button -->
                <button
                    @click="toggleLanguage"
                    type="button"
                    class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-semibold text-slate-200 transition active:scale-95 shadow-sm"
                >
                    <Languages class="w-4 h-4 text-indigo-400" />
                    <span>{{ isArabic ? 'English (LTR)' : 'العربية (RTL)' }}</span>
                </button>
            </header>

            <!-- Alerts / Feedback -->
            <transition name="fade">
                <div v-if="flash.success" class="bg-emerald-950/80 border border-emerald-800 text-emerald-200 p-3.5 rounded-xl flex items-start gap-3 text-sm">
                    <CheckCircle2 class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" />
                    <div class="flex-1">{{ flash.success }}</div>
                </div>
            </transition>

            <transition name="fade">
                <div v-if="flash.error" class="bg-rose-950/80 border border-rose-800 text-rose-200 p-3.5 rounded-xl flex items-start gap-3 text-sm">
                    <AlertCircle class="w-5 h-5 text-rose-400 shrink-0 mt-0.5" />
                    <div class="flex-1">{{ flash.error }}</div>
                </div>
            </transition>

            <!-- System Diagnostics Card -->
            <section class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2 text-indigo-400 font-semibold text-sm">
                        <Cpu class="w-4 h-4" />
                        <h2>{{ isArabic ? 'حالة النظام والبيئة المحلية' : 'System & Local Runtime Diagnostics' }}</h2>
                    </div>
                    <span 
                        :class="systemInfo.native_bridge_active ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border-amber-500/20'"
                        class="text-[11px] font-medium px-2.5 py-0.5 rounded-full border flex items-center gap-1.5"
                    >
                        <span class="w-1.5 h-1.5 rounded-full" :class="systemInfo.native_bridge_active ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400'"></span>
                        {{ systemInfo.native_bridge_active ? (isArabic ? 'جسر NativePHP نشط' : 'NativePHP Bridge Active') : (isArabic ? 'بيئة متصفح / تطوير' : 'Browser Dev / WebView Preview') }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="bg-slate-950/60 p-3 rounded-xl border border-slate-800/80">
                        <span class="text-slate-500 block mb-1">{{ isArabic ? 'إصدار PHP' : 'PHP Version' }}</span>
                        <span class="font-mono text-slate-200 font-semibold">{{ systemInfo.php_version }}</span>
                    </div>
                    <div class="bg-slate-950/60 p-3 rounded-xl border border-slate-800/80">
                        <span class="text-slate-500 block mb-1">{{ isArabic ? 'قاعدة البيانات المحلية' : 'Local SQLite Storage' }}</span>
                        <span class="font-mono text-emerald-400 font-semibold">SQLite (WAL Mode)</span>
                    </div>
                    <div class="bg-slate-950/60 p-3 rounded-xl border border-slate-800/80">
                        <span class="text-slate-500 block mb-1">{{ isArabic ? 'معرف التطبيق' : 'Application ID' }}</span>
                        <span class="font-mono text-slate-300 truncate block">{{ systemInfo.app_id }}</span>
                    </div>
                    <div class="bg-slate-950/60 p-3 rounded-xl border border-slate-800/80">
                        <span class="text-slate-500 block mb-1">{{ isArabic ? 'اتجاه الواجهة' : 'Layout Direction' }}</span>
                        <span class="font-semibold text-slate-200 uppercase">{{ systemInfo.direction }} ({{ systemInfo.locale }})</span>
                    </div>
                </div>
            </section>

            <!-- Card 1: Local SQLite Persistence -->
            <section class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2 text-indigo-400 font-semibold text-sm">
                        <Database class="w-4 h-4" />
                        <h2>{{ isArabic ? 'اختبار استمرارية قاعدة البيانات المحلية (SQLite)' : 'SQLite Local Persistence Test' }}</h2>
                    </div>
                    <span class="text-xs bg-indigo-500/10 text-indigo-300 px-2 py-0.5 rounded-full border border-indigo-500/20 font-mono">
                        {{ items.length }} {{ isArabic ? 'عناصر' : 'items' }}
                    </span>
                </div>

                <p class="text-xs text-slate-400 leading-relaxed">
                    {{ isArabic 
                        ? 'تُحفظ هذه العناصر مباشرة في ملف SQLite المحلي على الجهاز وتستمر عبر إعادة تشغيل التطبيق دون أي سيرفر خارجي.' 
                        : 'These records are stored directly in the local SQLite database file on the device and persist across app reboots without any external server.' }}
                </p>

                <!-- Add Item Form -->
                <form @submit.prevent="submitItem" class="space-y-3">
                    <div>
                        <input
                            v-model="itemForm.title"
                            type="text"
                            :placeholder="isArabic ? 'عنوان الهدف التعليمي...' : 'Learning goal or roadmap task title...'"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition"
                            required
                        />
                    </div>
                    <div>
                        <input
                            v-model="itemForm.details"
                            type="text"
                            :placeholder="isArabic ? 'تفاصيل إضافية (اختياري)...' : 'Optional notes or details...'"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition"
                        />
                    </div>
                    <button
                        type="submit"
                        :disabled="itemForm.processing || !itemForm.title.trim()"
                        class="w-full bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-medium py-2.5 px-4 rounded-xl text-sm transition active:scale-[0.99] flex items-center justify-center gap-2 shadow-lg shadow-indigo-600/20"
                    >
                        <Plus class="w-4 h-4" />
                        <span>{{ isArabic ? 'حفظ في قاعدة البيانات المحلية' : 'Save to SQLite Database' }}</span>
                    </button>
                </form>

                <!-- Item List -->
                <div class="space-y-2 pt-2">
                    <div 
                        v-for="item in items" 
                        :key="item.id"
                        class="bg-slate-950/80 border border-slate-800/80 rounded-xl p-3 flex items-start justify-between gap-3 group"
                    >
                        <div class="space-y-1 min-w-0">
                            <h3 class="font-medium text-sm text-slate-100 truncate">{{ item.title }}</h3>
                            <p v-if="item.details" class="text-xs text-slate-400 line-clamp-2">{{ item.details }}</p>
                            <div class="flex items-center gap-2 text-[10px] text-slate-500 font-mono">
                                <span>ID: {{ item.id.substring(0, 8) }}...</span>
                                <span>•</span>
                                <span>{{ new Date(item.created_at).toLocaleTimeString() }}</span>
                            </div>
                        </div>
                        <button
                            @click="deleteItem(item.id)"
                            type="button"
                            class="text-slate-500 hover:text-rose-400 p-1.5 rounded-lg hover:bg-rose-500/10 transition"
                            :title="isArabic ? 'حذف' : 'Delete'"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>

                    <div v-if="items.length === 0" class="text-center py-6 text-slate-500 text-xs border border-dashed border-slate-800 rounded-xl">
                        {{ isArabic ? 'لا توجد عناصر محفوظة حتى الآن. أضف عنصراً أعلاه لاختبار الحفظ.' : 'No items persisted yet. Add one above to test SQLite storage.' }}
                    </div>
                </div>
            </section>

            <!-- Card 2: Local Notification Test -->
            <section class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2 text-indigo-400 font-semibold text-sm">
                        <Bell class="w-4 h-4" />
                        <h2>{{ isArabic ? 'اختبار الإشعارات المحلية على أندرويد' : 'Native Android Local Notification Test' }}</h2>
                    </div>
                    <span class="text-[11px] bg-slate-800 text-slate-300 px-2 py-0.5 rounded-full border border-slate-700">
                        AlarmManager
                    </span>
                </div>

                <p class="text-xs text-slate-400 leading-relaxed">
                    {{ isArabic 
                        ? 'يستخدم التطبيق مجدول نظام أندرويد (AlarmManager) لجدولة الإشعارات المحلية بدقة حتى عندما يكون التطبيق مغلقاً دون الحاجة لسيرفر خارجي.' 
                        : 'The app schedules local alarms directly via the Android OS AlarmManager with POST_NOTIFICATIONS & exact alarm permissions, working even when the app is completely closed.' }}
                </p>

                <div class="bg-slate-950/80 p-4 rounded-xl border border-slate-800/80 space-y-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-400">{{ isArabic ? 'وقت الإطلاق بعد:' : 'Trigger Alarm In:' }}</span>
                        <div class="flex items-center gap-2 font-mono">
                            <button 
                                @click="notifDelay = 5"
                                type="button"
                                :class="notifDelay === 5 ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-300'"
                                class="px-2.5 py-1 rounded-lg text-xs font-semibold transition"
                            >5s</button>
                            <button 
                                @click="notifDelay = 10"
                                type="button"
                                :class="notifDelay === 10 ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-300'"
                                class="px-2.5 py-1 rounded-lg text-xs font-semibold transition"
                            >10s</button>
                            <button 
                                @click="notifDelay = 30"
                                type="button"
                                :class="notifDelay === 30 ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-300'"
                                class="px-2.5 py-1 rounded-lg text-xs font-semibold transition"
                            >30s</button>
                        </div>
                    </div>

                    <button
                        @click="scheduleNotification"
                        :disabled="isSchedulingNotif"
                        type="button"
                        class="w-full bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-medium py-2.5 px-4 rounded-xl text-sm transition active:scale-[0.99] flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/20"
                    >
                        <Bell class="w-4 h-4" />
                        <span>
                            {{ isArabic ? `جدولة إشعار أندرويد حقيقي (${notifDelay} ثوانٍ)` : `Schedule Real Android Notification (${notifDelay}s)` }}
                        </span>
                    </button>

                    <!-- Notification Result Display -->
                    <div v-if="flash.notification" class="mt-3 p-3 bg-slate-900 border border-slate-800 rounded-lg text-xs font-mono space-y-1">
                        <div class="text-emerald-400 font-semibold flex items-center gap-1.5">
                            <CheckCircle2 class="w-3.5 h-3.5" />
                            <span>{{ isArabic ? 'تم تأكيد الجدولة:' : 'Dispatched Alarm Payload:' }}</span>
                        </div>
                        <div class="text-slate-400 break-all">ID: {{ flash.notification.notification_id }}</div>
                        <div class="text-slate-400">Driver: {{ flash.notification.driver }}</div>
                        <div class="text-slate-400">Target: {{ flash.notification.target_time }}</div>
                        <p v-if="flash.notification.message" class="text-[11px] text-amber-400/90 font-sans mt-1">
                            ℹ️ {{ flash.notification.message }}
                        </p>
                    </div>
                </div>
            </section>

        </div>
    </main>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
