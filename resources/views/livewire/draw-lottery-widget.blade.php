<div>
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Kupon</div>
                    <div class="text-3xl font-bold text-primary-600">{{ $stats['total_coupons'] ?? 0 }}</div>
                </div>
                <div class="p-3 bg-primary-100 rounded-full dark:bg-primary-900">
                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Target Pemenang</div>
                    <div class="text-3xl font-bold text-success-600">{{ $stats['total_winners_setting'] ?? 0 }}</div>
                </div>
                <div class="p-3 bg-success-100 rounded-full dark:bg-success-900">
                    <svg class="w-6 h-6 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Terpilih</div>
                    <div class="text-3xl font-bold text-warning-600">{{ $stats['active_winners'] ?? 0 }}</div>
                </div>
                <div class="p-3 bg-warning-100 rounded-full dark:bg-warning-900">
                    <svg class="w-6 h-6 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Slot Tersisa</div>
                    <div class="text-3xl font-bold text-info-600">{{ $stats['remaining_slots'] ?? 0 }}</div>
                </div>
                <div class="p-3 bg-info-100 rounded-full dark:bg-info-900">
                    <svg class="w-6 h-6 text-info-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Draw Button -->
    <div class="text-center mb-6">
        <button 
            wire:click="drawWinner"
            wire:loading.attr="disabled"
            @disabled(!($stats['can_draw'] ?? false))
            class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-primary-500 to-primary-700 text-white font-bold text-xl rounded-lg shadow-lg hover:from-primary-600 hover:to-primary-800 disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-105 transition-all duration-200"
        >
            <svg wire:loading.remove class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
            </svg>
            <svg wire:loading class="w-6 h-6 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span wire:loading.remove>UNDI PEMENANG</span>
            <span wire:loading>MENGUNDI...</span>
        </button>
        
        @if(!($stats['can_draw'] ?? false))
        <div class="mt-2 text-sm text-gray-500">
            @if(($stats['remaining_slots'] ?? 0) <= 0)
                Semua pemenang sudah terpilih
            @elseif(($stats['total_coupons'] ?? 0) <= 0)
                Tidak ada kupon tersedia
            @else
                Tidak dapat mengundi saat ini
            @endif
        </div>
        @endif
    </div>
    
    <!-- Confetti Effect -->
    @if($showConfetti)
        <div 
            x-data="{ show: true }" 
            x-show="show"
            x-init="setTimeout(() => show = false, 3000)"
            class="fixed inset-0 pointer-events-none z-50"
        >
            <!-- Confetti will be added via JavaScript -->
        </div>
        
        <script>
            // Simple confetti effect
            document.addEventListener('DOMContentLoaded', function() {
                @this.on('winner-drawn', () => {
                    // You can add a proper confetti library here
                    // For now, we'll just show the confetti container
                    console.log('🎉 Winner drawn! Add confetti effect here');
                });
            });
        </script>
    @endif
    
    <!-- Latest Winners -->
    @if(count($latestWinners) > 0)
    <div class="bg-white rounded-lg shadow dark:bg-gray-800">
        <div class="p-6 border-b dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-xl font-bold">Pemenang Terbaru</h3>
            <button 
                wire:click="refreshData"
                class="text-primary-600 hover:text-primary-700 transition-colors"
                title="Refresh data"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Posisi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($latestWinners as $winner)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary-100 text-primary-800 font-bold text-sm">
                                {{ $winner['position'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-mono font-bold text-sm">
                            {{ $winner['coupon_code'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-medium">
                            {{ $winner['owner_name'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusConfig = [
                                    'pending' => ['bg-yellow-100 text-yellow-800', 'Menunggu'],
                                    'confirmed' => ['bg-green-100 text-green-800', 'Dikonfirmasi'],
                                    'cancelled' => ['bg-red-100 text-red-800', 'Dibatalkan'],
                                ];
                                [$bgClass, $label] = $statusConfig[$winner['status']] ?? ['bg-gray-100 text-gray-800', 'Unknown'];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $bgClass }}">
                                {{ $label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ \Carbon\Carbon::parse($winner['drawn_at'])->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="bg-white rounded-lg shadow dark:bg-gray-800 p-8 text-center">
        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Belum Ada Pemenang</h3>
        <p class="text-gray-500 dark:text-gray-400">Mulai undian untuk memilih pemenang pertama</p>
    </div>
    @endif
</div>