@extends('layouts.citizen')

@section('title', config('app.name'))

@section('content')
<div x-data="nidviteTracker(@js(__('home.report_not_found')))" class="relative flex flex-col min-h-0 overflow-hidden">
    <div class="pointer-events-none absolute -top-24 -left-16 h-56 w-56 rounded-full bg-amber-300/30 blur-3xl"></div>
    <div class="pointer-events-none absolute top-32 -right-20 h-64 w-64 rounded-full bg-teal-300/20 blur-3xl"></div>
    <div class="flex-1 px-4 py-8 sm:py-12">
        <div class="max-w-lg mx-auto">

            {{-- Hero --}}
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-linear-to-br from-amber-500 to-amber-600 shadow-lg mb-5">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold font-display text-gray-900 tracking-tight">{{ config('app.name') }}</h1>
                <p class="mt-3 text-base sm:text-lg text-gray-700 leading-relaxed max-w-sm mx-auto">{{ __('home.description') }}</p>
            </div>

            {{-- Action buttons --}}
            <div class="space-y-3 mb-12">
                <a href="{{ route('report.create') }}"
                   class="w-full inline-flex items-center justify-center px-6 py-4 text-lg font-semibold rounded-2xl shadow-xl text-white bg-linear-to-r from-amber-700 to-orange-500 hover:from-amber-800 hover:to-orange-600 active:scale-[0.98] transition-all duration-200 btn-touch interactive-lift">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('home.actions.report_issue') }}
                </a>
                <button type="button"
                        x-show="!showInput"
                        x-on:click="showInput = true"
                    class="w-full inline-flex items-center justify-center px-6 py-4 text-lg font-medium rounded-2xl shadow-xl text-gray-900 bg-white/90 hover:bg-white active:scale-[0.98] transition-all duration-200 btn-touch border border-amber-100 interactive-lift">
                    <svg class="w-6 h-6 mr-2 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    {{ __('home.actions.track_report') }}
                </button>

                {{-- Track input --}}
                <div x-show="showInput" x-cloak class="space-y-3 animate-fade-in">
                    <div class="bg-white/90 rounded-2xl shadow-xl p-4 space-y-3 border border-amber-100">
                        <div class="relative">
                            <input type="text" x-model="trackingId"
                                class="block w-full rounded-xl border-gray-300/80 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-base transition px-4 py-3.5 pr-20 uppercase tracking-wide"
                                   placeholder="{{ __('home.actions.track_placeholder') }}"
                                   x-on:keydown.enter="lookup()">
                            <button type="button"
                                    x-on:click="lookup()"
                                    class="absolute right-2 top-2 bottom-2 px-4 bg-amber-700 text-white text-sm font-semibold rounded-lg hover:bg-amber-800 active:scale-[0.98] transition-all btn-touch interactive-lift">
                                {{ __('home.actions.track_submit') }}
                            </button>
                        </div>
                        <p x-show="error" x-text="error" class="text-sm text-red-600"></p>
                    </div>
                    <button type="button" x-on:click="showInput = false; trackingId = ''; error = ''"
                            class="w-full text-sm text-gray-500 hover:text-gray-700 font-medium py-2 btn-touch">
                        {{ __('home.actions.cancel') }}
                    </button>
                </div>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-2 gap-3">
                <div class="citizen-card p-4 text-center">
                    <p class="text-2xl sm:text-3xl font-bold text-amber-600">{{ number_format($totalReported) }}</p>
                    <p class="text-xs sm:text-sm text-gray-500 mt-1 leading-tight">{{ __('home.stats.reported_to_date') }}</p>
                </div>
                <div class="citizen-card p-4 text-center">
                    <p class="text-2xl sm:text-3xl font-bold text-green-600">{{ number_format($totalFixed) }}</p>
                    <p class="text-xs sm:text-sm text-gray-500 mt-1 leading-tight">{{ __('home.stats.fixed_to_date') }}</p>
                </div>
                <div class="citizen-card p-4 text-center">
                    <p class="text-2xl sm:text-3xl font-bold text-blue-600">{{ number_format($totalPending) }}</p>
                    <p class="text-xs sm:text-sm text-gray-500 mt-1 leading-tight">{{ __('home.stats.pending_fix') }}</p>
                </div>
                <div class="citizen-card p-4 text-center">
                    <p class="text-2xl sm:text-3xl font-bold text-teal-700">{{ $velocity }}</p>
                    <p class="text-xs sm:text-sm text-gray-500 mt-1 leading-tight">{{ __('home.stats.repair_velocity') }}</p>
                </div>
            </div>

        </div>
    </div>

    {{-- Tracking Modal --}}
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-1002 flex items-center justify-center p-4" x-transition.opacity>
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" x-on:click="modalOpen = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md max-h-[85vh] overflow-y-auto animate-slide-up" x-on:click.away="modalOpen = false">
            <div class="sticky top-0 bg-white border-b border-gray-100 px-5 py-4 flex items-center justify-between rounded-t-3xl z-10">
                <h2 class="text-lg font-bold text-gray-900">{{ __('home.modal.title') }}</h2>
                <button type="button" x-on:click="modalOpen = false" class="p-2 rounded-full hover:bg-gray-100 transition btn-touch">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-5 space-y-5">
                <div x-show="loading" class="py-12 text-center">
                    <svg class="animate-spin mx-auto h-8 w-8 text-amber-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-3 text-sm text-gray-500">{{ __('home.modal.loading') }}</p>
                </div>

                <template x-if="report && !loading">
                    <div class="space-y-5">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded" x-text="report.tracking_id"></span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                                  :class="{
                                      'bg-gray-100 text-gray-800': report.status === 'received',
                                      'bg-blue-100 text-blue-800': report.status === 'verified',
                                      'bg-yellow-100 text-yellow-800': report.status === 'scheduled',
                                      'bg-orange-100 text-orange-800': report.status === 'in_progress',
                                      'bg-green-100 text-green-800': report.status === 'repaired',
                                      'bg-red-100 text-red-800': report.status === 'rejected',
                                  }"
                                  x-text="report.status_label"></span>
                        </div>

                        <div class="space-y-2 text-sm">
                            <div x-show="report.category" class="flex">
                                <span class="text-gray-500 w-24 shrink-0">{{ __('home.modal.fields.type') }}</span>
                                <span class="text-gray-900" x-text="report.category"></span>
                            </div>
                            <div x-show="report.address" class="flex">
                                <span class="text-gray-500 w-24 shrink-0">{{ __('home.modal.fields.address') }}</span>
                                <span class="text-gray-900" x-text="report.address"></span>
                            </div>
                            <div x-show="report.created_at" class="flex">
                                <span class="text-gray-500 w-24 shrink-0">{{ __('home.modal.fields.date') }}</span>
                                <span class="text-gray-900" x-text="new Date(report.created_at).toLocaleDateString()"></span>
                            </div>
                        </div>

                        <div x-show="report.status === 'rejected'" class="p-4 rounded-xl bg-red-50 border border-red-200">
                            <p class="font-semibold text-red-900 text-sm">{{ __('home.modal.rejected') }}</p>
                            <p x-show="report.rejection_reason" x-text="report.rejection_reason" class="text-sm text-red-700 mt-1"></p>
                        </div>

                        <div x-show="report.status !== 'rejected'">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-semibold text-gray-700">{{ __('home.modal.progress') }}</span>
                                <span class="text-sm font-bold text-amber-600" x-text="Math.round(report.progress.percent) + '%'"></span>
                            </div>
                            <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
                                  <div class="h-full bg-linear-to-r from-amber-500 to-amber-600 rounded-full transition-all duration-700 ease-out"
                                     :style="'width: ' + report.progress.percent + '%'"></div>
                            </div>
                            <div class="mt-4 space-y-2">
                                <template x-for="(step, idx) in report.steps" :key="step.status">
                                    <div class="flex items-center gap-3">
                                        <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 text-xs font-bold"
                                             :class="step.done ? (step.current ? 'bg-amber-600 text-white' : 'bg-amber-200 text-amber-800') : 'bg-gray-200 text-gray-400'">
                                            <template x-if="step.done && !step.current">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            </template>
                                            <template x-if="step.current">
                                                <span class="w-2 h-2 bg-white rounded-full"></span>
                                            </template>
                                            <template x-if="!step.done">
                                                <span x-text="idx + 1"></span>
                                            </template>
                                        </div>
                                        <span class="text-sm font-medium"
                                              :class="step.done ? (step.current ? 'text-amber-900' : 'text-gray-700') : 'text-gray-400'"
                                              x-text="step.label"></span>
                                        <span x-show="step.current" class="text-xs text-amber-600 font-semibold ml-auto">{{ __('home.modal.current') }}</span>
                                    </div>
                                </template>
                            </div>
                        </div>

                                <a :href="'/suivi/' + report.tracking_id" target="_blank"
                           class="block w-full text-center py-3 text-sm font-medium text-amber-600 hover:text-amber-700 bg-amber-50 rounded-xl hover:bg-amber-100 transition btn-touch">
                            {{ __('home.modal.full_page') }} →
                        </a>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection
