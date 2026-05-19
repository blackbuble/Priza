<div
    x-data="lotteryDraw(@js($couponNames), @js($showShuffleNames))"
    x-init="init()"
    @winners-revealed.window="onWinnersRevealed($event.detail)"
    @hide-confetti-after-delay.window="scheduleHide()"
>

    {{-- ══════════════════════════════════════════
         STATS CARDS
    ══════════════════════════════════════════ --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4 mb-6">
        @foreach([
            ['label'=>'Total Kupon',     'key'=>'total_coupons',         'color'=>'primary', 'icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['label'=>'Target Pemenang', 'key'=>'total_winners_setting',  'color'=>'success', 'icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label'=>'Terpilih',        'key'=>'active_winners',         'color'=>'warning', 'icon'=>'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
            ['label'=>'Slot Tersisa',    'key'=>'remaining_slots',        'color'=>'info',    'icon'=>'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        ] as $card)
        <div class="bg-white rounded-2xl shadow-sm hover:shadow-md border border-gray-100 p-5 dark:bg-gray-800 dark:border-gray-700 flex items-center justify-between transition-all duration-300 transform hover:-translate-y-1 group">
            <div>
                <div class="text-[10px] text-gray-500 dark:text-gray-400 font-bold uppercase tracking-widest">{{ $card['label'] }}</div>
                <div class="text-3xl font-black text-gray-800 dark:text-white mt-1 flex items-baseline gap-1">
                    <span class="text-{{ $card['color'] }}-500 pulse-soft">{{ $stats[$card['key']] ?? 0 }}</span>
                </div>
            </div>
            <div class="p-3 bg-{{ $card['color'] }}-50 rounded-2xl dark:bg-{{ $card['color'] }}-900/30 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-6 h-6 text-{{ $card['color'] }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/>
                </svg>
            </div>
        </div>
        @endforeach
    </div>


    {{-- ══════════════════════════════════════════
         VERTICAL ROLLING DRUMROLL DISPLAY
    ══════════════════════════════════════════ --}}
    <div
        class="relative mb-8 rounded-[2rem] overflow-hidden shadow-2xl border-4 transition-colors duration-500"
        :class="theme === 'dark' ? 'bg-[#0a0c10] border-[#1a1d24]' : 'bg-gray-50 border-white'"
        style="height: 180px;"
    >
        {{-- Shine overlay --}}
        <div class="absolute inset-0 pointer-events-none bg-gradient-to-tr from-white/5 to-transparent z-10" :class="theme === 'light' ? 'opacity-20' : ''"></div>
        
        {{-- Top/Bottom Masking Fade --}}
        <div class="absolute inset-x-0 top-0 h-16 z-20 transition-all duration-500" :style="theme === 'dark' ? 'background: linear-gradient(to bottom, #0a0c10, transparent)' : 'background: linear-gradient(to bottom, #f9fafb, transparent)'"></div>
        <div class="absolute inset-x-0 bottom-0 h-16 z-20 transition-all duration-500" :style="theme === 'dark' ? 'background: linear-gradient(to top, #0a0c10, transparent)' : 'background: linear-gradient(to top, #f9fafb, transparent)'"></div>

        {{-- Rolling Container --}}
        <div class="flex flex-col items-center justify-center h-full">
            <div x-show="phase === 'idle'" class="text-center z-30" x-transition>
                <div class="text-5xl mb-2 animate-bounce">🎰</div>
                <p class="font-black uppercase tracking-[0.2em] text-[10px]" :class="theme === 'dark' ? 'text-gray-500' : 'text-gray-400'">Siap untuk Mengundi</p>
            </div>

            {{-- The Reel --}}
            <div 
                x-show="phase === 'shuffling' || phase === 'locked' || phase === 'stopping'" 
                class="relative h-[60px] w-full overflow-hidden flex flex-col items-center z-10"
                x-transition
            >
                <div 
                    class="transition-transform"
                    :style="'transform: translateY(' + reelOffset + 'px); transition-duration: ' + (phase === 'locked' ? '1s' : (phase === 'stopping' ? '0.4s' : '0.05s')) + '; transition-timing-function: cubic-bezier(0.12, 0, 0.39, 0);'"
                    style="will-change: transform;"
                >
                    <template x-for="(name, index) in reel" :key="index">
                        <div 
                            class="h-[60px] flex items-center justify-center px-10 text-center"
                            :class="{'opacity-100 scale-110 font-black': index === centeredIndex, 'opacity-10 scale-90': index !== centeredIndex}"
                        >
                            <span 
                                x-text="name" 
                                class="text-3xl md:text-5xl tracking-tight truncate max-w-2xl px-4"
                                :class="theme === 'dark' ? 'text-white' : 'text-gray-900'"
                                :style="index === centeredIndex ? (theme === 'dark' ? 'text-shadow: 0 0 30px rgba(59,130,246,0.8);' : 'text-shadow: 0 0 20px rgba(59,130,246,0.2);') : ''"
                            ></span>
                        </div>
                    </template>
                </div>
            </div>
            
            {{-- Target Line / Center Glass --}}
            <div x-show="phase !== 'idle'" class="absolute inset-x-0 top-1/2 -mt-[30px] h-[60px] border-y pointer-events-none z-0 bg-white/5" :class="theme === 'dark' ? 'border-white/10' : 'border-primary-100'"></div>
            <div x-show="phase !== 'idle'" class="absolute left-6 top-1/2 -mt-1 w-2.5 h-2.5 rounded-full bg-primary-500 shadow-[0_0_15px_#3b82f6]"></div>
            <div x-show="phase !== 'idle'" class="absolute right-6 top-1/2 -mt-1 w-2.5 h-2.5 rounded-full bg-primary-500 shadow-[0_0_15px_#3b82f6]"></div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════
         MAIN DRAW BUTTON
    ══════════════════════════════════════════ --}}
    <div class="text-center mb-12">
        <button
            wire:click="drawWinner"
            wire:loading.attr="disabled"
            @disabled(!($stats['can_draw'] ?? false) || $isDrawing)
            @click="startShuffle()"
            class="relative group active:scale-95 transition-all duration-200 rounded-[2.5rem] overflow-hidden shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-105"
        >
            <div class="absolute inset-0 bg-gradient-to-r from-primary-600 via-primary-500 to-indigo-700 group-hover:from-primary-500 group-hover:to-indigo-600 transition-all duration-500 group-hover:bg-pos-100 bg-pos-0" style="background-size: 200% 100%;"></div>
            <div class="relative flex items-center gap-6 px-14 py-6 text-white">
                <div x-show="!$wire.isDrawing" class="p-3 bg-white/20 rounded-2xl shadow-inner">
                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 3L4 9v12h16V9l-8-6zm0 2.2L18.8 10v9H5.2v-9L12 5.2zM12 11a1.5 1.5 0 100 3 1.5 1.5 0 000-3z"/>
                    </svg>
                </div>
                <div x-show="$wire.isDrawing" class="animate-spin p-3">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </div>
                <div>
                    <span class="block text-left text-[10px] font-black tracking-[0.4em] opacity-80 uppercase mb-1" x-text="$wire.isDrawing ? 'MENGUNDI' : 'SIAP UNDI'"></span>
                    <span class="block text-3xl font-black uppercase tracking-tight" x-text="$wire.isDrawing ? 'HARAP TUNGGU...' : 'UNDI ' + {{ $drawCount }} + ' PEMENANG'"></span>
                </div>
            </div>
        </button>

        @if(!($stats['can_draw'] ?? false))
        <div class="mt-6 flex items-center justify-center gap-3 text-gray-500 font-black uppercase tracking-widest text-[10px]">
            <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse shadow-[0_0_10px_rgba(239,68,68,0.5)]"></span>
            @if(($stats['remaining_slots'] ?? 0) <= 0)
                SEMUA PEMENANG SUDAH TERPILIH
            @elseif(($stats['total_coupons'] ?? 0) <= 0)
                TIDAK ADA KUPON TERSEDIA
            @else
                BATAS PEMENANG TERCAPAI
            @endif
        </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════
         FULLSCREEN CELEBRATION
    ══════════════════════════════════════════ --}}
    <div
        x-show="phase === 'celebrating'"
        x-transition:enter="transition ease-out duration-700"
        x-transition:enter-start="opacity-0 backdrop-blur-0"
        x-transition:enter-end="opacity-100 backdrop-blur-xl"
        x-transition:leave="transition ease-in duration-500"
        x-transition:leave-start="opacity-100 scale-100 blur-0"
        x-transition:leave-end="opacity-0 scale-110 blur-2xl"
        class="fixed inset-0 z-[9999] flex items-center justify-center overflow-hidden"
    >
        {{-- God Rays Background --}}
        <div class="absolute inset-0 bg-black/95 pointer-events-none overflow-hidden">
            <div class="god-rays"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-black opacity-60"></div>
        </div>

        <canvas id="lottery-confetti" class="absolute inset-0 w-full h-full pointer-events-none z-20"></canvas>

        <div class="relative z-30 text-center px-6 scale-90 sm:scale-100 max-w-4xl w-full">
            <div class="winner-trophy text-[140px] mb-4 drop-shadow-[0_0_60px_rgba(250,204,21,0.6)]">🏆</div>

            <h4 class="text-yellow-400 font-black tracking-[0.6em] uppercase text-xs mb-8 animate-pulse">
                ✦&nbsp; CONGRATULATIONS &nbsp;✦
            </h4>

            <div class="relative inline-block mb-12 group w-full px-4">
                {{-- Decorative borders --}}
                <div class="absolute -inset-6 bg-gradient-to-r from-yellow-600 via-yellow-300 to-yellow-600 rounded-[3rem] blur-3xl opacity-20 animate-pulse"></div>

                {{-- Single or Multi Grid Layout for Winners --}}
                <div 
                    class="relative bg-white/5 backdrop-blur-2xl border border-white/20 rounded-[3.5rem] p-8 md:p-12 shadow-[0_0_120px_rgba(0,0,0,0.8)] border-gradient grid gap-8"
                    :class="winners.length > 3 ? 'grid-cols-2 lg:grid-cols-3' : (winners.length > 1 ? 'grid-cols-2' : 'grid-cols-1')"
                >
                    <template x-for="win in winners" :key="win.id">
                        <div class="flex flex-col items-center justify-center relative p-6 bg-white/5 rounded-3xl border border-white/10 hover:bg-white/10 hover:scale-105 transition-all duration-300">
                            
                            <div class="absolute -top-4 left-1/2 -translate-x-1/2 px-6 py-2 bg-yellow-400 text-black font-black uppercase text-[10px] sm:text-xs rounded-full shadow-[0_10px_30px_rgba(250,204,21,0.3)] tracking-[0.2em] whitespace-nowrap">
                                <span x-text="'NO ' + win.position"></span>
                            </div>
                            
                            <h1 
                                class="text-3xl md:text-5xl lg:text-6xl font-black text-white mb-4 mt-2 tracking-tighter uppercase leading-tight text-center break-words w-full px-2"
                                x-text="win.owner_name"
                                style="text-shadow: 0 10px 40px rgba(0,0,0,1), 0 0 20px rgba(255,255,255,0.1);"
                            ></h1>
                            
                            <div class="flex items-center gap-3 w-full justify-center opacity-80 mt-auto">
                                <div class="h-px flex-1 bg-gradient-to-r from-transparent to-white/30 hidden sm:block"></div>
                                <p class="text-yellow-100 font-mono text-sm md:text-lg tracking-[0.2em] bg-black/30 px-4 py-1.5 rounded-xl border border-white/10">
                                    <span class="opacity-40">#</span><span x-text="win.coupon_code"></span>
                                </p>
                                <div class="h-px flex-1 bg-gradient-to-l from-transparent to-white/30 hidden sm:block"></div>
                            </div>
                            
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex flex-col items-center gap-8">
                <div class="px-8 py-3 bg-white/10 rounded-full border border-white/10 flex items-center gap-4 backdrop-blur-md shadow-inner">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500 shadow-[0_0_8px_#ef4444]"></span>
                    </span>
                    <span class="text-white font-black uppercase tracking-widest text-[11px]">Auto-Close: <span x-text="countdown" class="text-yellow-400 font-mono text-sm"></span>s</span>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-5 w-full justify-center px-4">
                    <button
                        @click="closeCelebration()"
                        class="px-10 py-5 bg-white/5 text-white/50 font-black text-xs uppercase tracking-[0.3em] rounded-[2.5rem] border border-white/10 hover:bg-white/10 hover:text-white transition-all duration-300 min-w-[200px]"
                    >
                        TUTUP DASHBOARD
                    </button>
                    <button
                        @click="reDraw()"
                        class="px-14 py-6 bg-yellow-400 text-black font-black text-xl rounded-[2.5rem] hover:bg-white hover:scale-105 active:scale-95 transition-all duration-300 shadow-[0_25px_50px_rgba(250,204,21,0.4)] flex items-center justify-center gap-4 group min-w-[300px]"
                    >
                        <svg class="w-6 h-6 transform group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        RE-UNDI LAGI
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════
         LATEST WINNERS
    ══════════════════════════════════════════ --}}
    <div class="bg-white rounded-[2.5rem] shadow-sm dark:bg-gray-800 border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-10 py-8 border-b dark:border-gray-700 flex flex-wrap justify-between items-center bg-gray-50/50 dark:bg-gray-900/50 gap-4">
            <div>
                <h3 class="text-2xl font-black dark:text-white uppercase tracking-tighter">Daftar Pemenang</h3>
                <p class="text-gray-500 text-[10px] font-black font-mono tracking-[0.4em] uppercase mt-1">
                    Urutan: <span class="text-primary-500" x-text="$wire.orderDir === 'desc' ? 'Terbaru' : 'Terlama'"></span> ✦ Riwayat Sistem
                </p>
            </div>
            <div class="flex items-center gap-4">
                <button 
                   @click="$wire.set('orderDir', $wire.orderDir === 'desc' ? 'asc' : 'desc')"
                   class="px-4 py-2 bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl text-[10px] font-black uppercase tracking-widest text-gray-500 hover:text-primary-500 transition shadow-sm active:scale-95 flex items-center gap-2"
                >
                   <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
                   </svg>
                   BALIK URUTAN
                </button>
                <button wire:click="refreshData" class="w-12 h-12 flex items-center justify-center rounded-2xl bg-white dark:bg-gray-800 border dark:border-gray-700 text-primary-500 hover:shadow-md transition active:scale-90 shadow-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>
        </div>
        
        @if(count($latestWinners) > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[10px] text-gray-400 font-black uppercase tracking-[0.3em] bg-gray-50/30 dark:bg-gray-900/40">
                        <th class="px-10 py-5">No. Posisi</th>
                        <th class="px-10 py-5">Kode Kupon</th>
                        <th class="px-10 py-5">Nama Pemenang</th>
                        <th class="px-10 py-5">Status</th>
                        <th class="px-10 py-5 text-right">Tanggal Undi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    @foreach($latestWinners as $w)
                    <tr class="group hover:bg-primary-50/30 dark:hover:bg-primary-900/10 transition-all duration-300">
                        <td class="px-10 py-6">
                            <span class="flex items-center justify-center w-10 h-10 rounded-2xl bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-white font-black text-sm group-hover:bg-primary-500 group-hover:text-white transition-all shadow-sm group-hover:shadow-primary-500/30">
                                {{ $w['position'] }}
                            </span>
                        </td>
                        <td class="px-10 py-6">
                            <div class="flex flex-col">
                                <span class="font-mono text-sm font-bold text-gray-400 group-hover:text-primary-500 transition-colors uppercase tracking-widest">{{ $w['coupon_code'] }}</span>
                                <span class="text-[8px] font-black uppercase tracking-tighter opacity-0 group-hover:opacity-100 transition-all text-primary-400 mt-1">VERIFIED COUPON</span>
                            </div>
                        </td>
                        <td class="px-10 py-6 font-black text-gray-800 dark:text-gray-100 text-lg tracking-tight">{{ $w['owner_name'] }}</td>
                        <td class="px-10 py-6">
                            @php
                                $badgeSet = [
                                    'pending'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 border-amber-200 dark:border-amber-800',
                                    'confirmed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
                                    'cancelled' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 border-rose-200 dark:border-rose-800'
                                ][$w['status']] ?? 'bg-gray-100 text-gray-600 border-gray-200';
                            @endphp
                            <span class="inline-flex items-center px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] border {{ $badgeSet }}">
                                {{ $w['status'] }}
                            </span>
                        </td>
                        <td class="px-10 py-6 text-right">
                            <span class="text-xs font-bold text-gray-400 block">{{ \Carbon\Carbon::parse($w['drawn_at'])->format('d M Y') }}</span>
                            <span class="text-[10px] font-mono text-gray-300 dark:text-gray-600 block mt-1">{{ \Carbon\Carbon::parse($w['drawn_at'])->format('H:i:s') }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="py-24 text-center">
            <div class="text-8xl mb-6 grayscale opacity-20 filter drop-shadow-2xl">🎰</div>
            <h5 class="text-gray-500 dark:text-gray-400 font-black uppercase tracking-[0.5em] text-xs">Menunggu Pemenang Pertama</h5>
            <p class="text-gray-400 dark:text-gray-600 text-[10px] mt-4 max-w-xs mx-auto font-medium">Data pengundian akan muncul secara otomatis di sini setelah tombol "UNDI" ditekan.</p>
        </div>
        @endif
    </div>

    <style>
        .bg-pos-0 { background-position: 0% 0%; }
        .bg-pos-100 { background-position: 100% 0%; }
        
        .pulse-soft { animation: pulseSoft 2.5s infinite; }
        @keyframes pulseSoft { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.8; transform: scale(0.98); } }
        
        .god-rays {
            position: absolute; top: -100%; left: -100%; width: 300%; height: 300%;
            background: conic-gradient(
                from 0deg at 50% 50%, 
                transparent 0deg, 
                rgba(255,255,255,0.08) 15deg, 
                transparent 30deg,
                rgba(255,255,255,0.05) 45deg,
                transparent 60deg
            );
            animation: spinRays 40s linear infinite;
        }
        @keyframes spinRays { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        
        .winner-trophy { animation: trophyBounce 3s ease-in-out infinite; }
        @keyframes trophyBounce { 0%, 100% { transform: translateY(0) rotate(-5deg); filter: brightness(1); } 50% { transform: translateY(-25px) rotate(5deg); filter: brightness(1.2) drop-shadow(0 0 80px rgba(250,204,21,0.8)); } }
    
        .border-gradient {
            backdrop-filter: blur(20px);
            box-shadow: 0 0 0 1px rgba(255,255,255,0.1), inset 0 0 0 1px rgba(255,255,255,0.05);
        }
    </style>

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('lotteryDraw', (names, showShuffleNames) => ({
            names,
            showShuffleNames,
            phase: 'idle', // idle | shuffling | stopping | locked | celebrating
            reel: [],
            reelOffset: 0,
            centeredIndex: 0,
            winners: [],
            countdown: 10,
            _audioCtx: null,
            theme: 'dark',

            init() {
                // Initial theme detection
                this.updateTheme();
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => this.updateTheme());
                // Also check Filament's state if possible, though manual works
                document.addEventListener('dark-mode-toggled', e => this.theme = e.detail);
                
                this.resetReel();
                
                // Track backend changes to showShuffleNames
                this.$watch('$wire.showShuffleNames', value => {
                    this.showShuffleNames = value;
                    if (this.phase === 'idle') this.resetReel();
                });
            },

            updateTheme() {
                // Check if document has .dark class (standard Filament / Tailwind)
                this.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            },

            resetReel() {
                this.reel = [];
                const placeholder = '✦ ✦ ✦ ✦ ✦ ✦';
                for (let i = 0; i < 20; i++) {
                    const name = this.showShuffleNames 
                        ? (this.names[Math.floor(Math.random() * this.names.length)] || placeholder)
                        : placeholder;
                    this.reel.push(name);
                }
                this.reelOffset = - (10 * 60);
                this.centeredIndex = 10;
            },

            startShuffle() {
                if (this.phase !== 'idle' && this.phase !== 'locked') return;
                
                this.phase = 'shuffling';
                this.initAudio();
                this._runRolling();
            },

            _runRolling() {
                if (this.phase !== 'shuffling') return;

                this.centeredIndex++;
                
                const nextName = this.showShuffleNames 
                    ? (this.names[Math.floor(Math.random() * this.names.length)] || '✦ ✦ ✦')
                    : '✦ ✦ ✦ ✦ ✦ ✦';
                    
                this.reel.push(nextName);
                this.reelOffset = - (this.centeredIndex * 60);
                
                this.playTick();

                let delay = 60; // Keep it fast while waiting for server
                if (this.centeredIndex < 15) delay = 150;
                else if (this.centeredIndex < 25) delay = 80;

                setTimeout(() => this._runRolling(), delay);
            },

            initAudio() {
                if (!this._audioCtx) {
                    this._audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                }
            },

            playTick() {
                if (!this._audioCtx) return;
                const osc = this._audioCtx.createOscillator();
                const gain = this._audioCtx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(900, this._audioCtx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(80, this._audioCtx.currentTime + 0.04);
                gain.gain.setValueAtTime(0.08, this._audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, this._audioCtx.currentTime + 0.04);
                osc.connect(gain);
                gain.connect(this._audioCtx.destination);
                osc.start();
                osc.stop(this._audioCtx.currentTime + 0.04);
            },

            onWinnersRevealed(detail) {
                // If the detail wrapper contains a winners array, use it; otherwise fallback to array wrap
                this.winners = Array.isArray(detail.winners) ? detail.winners : (Array.isArray(detail) ? detail : [detail]);
                
                // Force stopping logic
                const stoppingSteps = 8;
                const targetIndex = this.centeredIndex + stoppingSteps;
                
                // If only 1 winner, stop exactly at their name. 
                // If multiple, show a collective text so they are all revealed on the celebration screen.
                let targetText = "🎉 PARA PEMENANG! 🎉";
                if (this.winners.length === 1 && this.winners[0]) {
                    targetText = this.winners[0].owner_name;
                }
                
                this.reel[targetIndex] = targetText;
                
                this.phase = 'stopping';
                this.stopAt(targetIndex);
            },

            stopAt(targetIndex) {
                if (this.centeredIndex >= targetIndex) {
                    this.phase = 'locked';
                    this.playWinnerSound();
                    setTimeout(() => this.startCelebration(), 1800);
                    return;
                }

                this.centeredIndex++;
                this.reelOffset = - (this.centeredIndex * 60);
                this.playTick();
                
                // Deceleration curve
                const remaining = targetIndex - this.centeredIndex;
                const progress = (8 - remaining) / 8; // 0 to 1
                const delay = 60 + (progress * progress * 800); 
                
                setTimeout(() => this.stopAt(targetIndex), delay);
            },

            startCelebration() {
                this.phase = 'celebrating';
                this.countdown = 10;
                this.playFanfare();
                this._fireConfetti();
                
                const timer = setInterval(() => {
                    this.countdown--;
                    if (this.countdown <= 0) {
                        clearInterval(timer);
                        if (this.phase === 'celebrating') this.closeCelebration();
                    }
                }, 1000);
                this._celebrationTimer = timer;
            },

            closeCelebration() {
                clearInterval(this._celebrationTimer);
                this.phase = 'idle';
                this.resetReel();
                if (window.Livewire) Livewire.dispatch('$refresh');
            },

            reDraw() {
                clearInterval(this._celebrationTimer);
                this.phase = 'idle';
                this.resetReel();
                setTimeout(() => {
                    const btn = document.querySelector('button[wire\\:click="drawWinner"]');
                    if (btn) btn.click();
                }, 150);
            },

            playWinnerSound() {
                const now = this._audioCtx.currentTime;
                [0, 0.1, 0.25].forEach((d, i) => {
                    const osc = this._audioCtx.createOscillator();
                    const g = this._audioCtx.createGain();
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(1000 + (i*400), now + d);
                    g.gain.setValueAtTime(0, now + d);
                    g.gain.linearRampToValueAtTime(0.1, now + d + 0.02);
                    g.gain.exponentialRampToValueAtTime(0.001, now + d + 0.2);
                    osc.connect(g); g.connect(this._audioCtx.destination);
                    osc.start(now + d); osc.stop(now + d + 0.25);
                });
            },

            playFanfare() {
                const now = this._audioCtx.currentTime;
                // Major chords fanfare
                const notes = [
                    {f: 523.25, d: 0.15, s: 0}, 
                    {f: 659.25, d: 0.15, s: 0},
                    {f: 783.99, d: 0.15, s: 0},
                    {f: 1046.5, d: 0.8,  s: 0.15},
                    {f: 783.99, d: 0.8,  s: 0.15},
                    {f: 659.25, d: 0.8,  s: 0.15},
                ];
                notes.forEach(n => {
                    const o = this._audioCtx.createOscillator();
                    const g = this._audioCtx.createGain();
                    o.type = 'sawtooth';
                    o.frequency.setValueAtTime(n.f, now + n.s);
                    g.gain.setValueAtTime(0, now + n.s);
                    g.gain.linearRampToValueAtTime(0.1, now + n.s + 0.05);
                    g.gain.exponentialRampToValueAtTime(0.001, now + n.s + n.d);
                    o.connect(g); g.connect(this._audioCtx.destination);
                    o.start(now + n.s); o.stop(now + n.s + n.d + 0.1);
                });
            },

            _fireConfetti() {
                const canvas = document.getElementById('lottery-confetti');
                if (!canvas) return;
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
                const ctx = canvas.getContext('2d');
                const particles = Array.from({ length: 250 }, () => ({
                    x: Math.random() * canvas.width,
                    y: -50,
                    vx: (Math.random() - 0.5) * 15,
                    vy: 5 + Math.random() * 15,
                    r: Math.random() * 8 + 6,
                    color: `hsl(${Math.random() * 360}, 100%, 65%)`,
                    rot: Math.random() * Math.PI * 2,
                    rotV: (Math.random() - 0.5) * 0.3
                }));

                const loop = () => {
                    if (this.phase !== 'celebrating') {
                        ctx.clearRect(0,0, canvas.width, canvas.height);
                        return;
                    }
                    ctx.clearRect(0,0, canvas.width, canvas.height);
                    particles.forEach(p => {
                        p.x += p.vx; p.y += p.vy; p.rot += p.rotV; p.vy += 0.25;
                        if (p.y > canvas.height + 50) { 
                            p.y = -50; p.x = Math.random() * canvas.width; p.vy = 5 + Math.random() * 10; 
                        }
                        ctx.save();
                        ctx.translate(p.x, p.y);
                        ctx.rotate(p.rot);
                        ctx.fillStyle = p.color;
                        ctx.fillRect(-p.r, -p.r/2, p.r*2, p.r);
                        ctx.restore();
                    });
                    requestAnimationFrame(loop);
                };
                loop();
            }
        }));
    });
    </script>
</div>