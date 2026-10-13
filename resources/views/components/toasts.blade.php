{{-- Escucha el evento `toast` ({type, message}) que despachan los componentes Livewire. --}}
<div x-data="{
        toasts: [],
        push(detail) {
            const d = Array.isArray(detail) ? detail[0] : detail;
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type: d.type ?? 'success', message: d.message });
            setTimeout(() => this.toasts = this.toasts.filter(t => t.id !== id), 4000);
        }
    }"
    x-on:toast.window="push($event.detail)"
    class="pointer-events-none fixed inset-x-0 bottom-0 z-50 flex flex-col items-center gap-2 p-4 sm:items-end sm:p-6"
    aria-live="polite">
    <template x-for="t in toasts" :key="t.id">
        <div x-transition.opacity.duration.200ms
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl bg-white p-4 text-sm shadow-lg ring-1 ring-zinc-900/5 dark:bg-zinc-800 dark:ring-white/10">
            <span class="mt-0.5 size-2 shrink-0 rounded-full" :class="t.type === 'error' ? 'bg-red-500' : 'bg-emerald-500'"></span>
            <p class="flex-1 text-zinc-700 dark:text-zinc-200" x-text="t.message"></p>
            <button type="button" class="text-zinc-400 hover:text-zinc-600" x-on:click="toasts = toasts.filter(x => x.id !== t.id)" aria-label="Cerrar">&times;</button>
        </div>
    </template>
</div>
