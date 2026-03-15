<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">{{ $survey->title }} — Results</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $survey->responses->count() }} total responses</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('surveys.export', $survey) }}" class="btn-secondary btn-sm">Export CSV</a>
                <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700 transition">← Back</a>
            </div>
        </div>
    </x-slot>

    @if($survey->responses->isEmpty())
        <div class="card text-center py-12">
            <p class="text-gray-400">No responses yet. Share your survey to start collecting data.</p>
        </div>
    @else
        <div class="space-y-6">
            <div class="card">
                <div class="mb-4">
                    <h3 class="font-semibold text-gray-800">Respondent Details</h3>
                    <p class="text-xs text-gray-400 mt-1">Name (or Anonymous), gender, and location of each response</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                                <th class="px-3 py-2 font-semibold">Respondent</th>
                                <th class="px-3 py-2 font-semibold">Gender</th>
                                <th class="px-3 py-2 font-semibold">Location</th>
                                <th class="px-3 py-2 font-semibold">Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($demographicsRows as $row)
                                <tr class="border-b border-gray-100 last:border-b-0">
                                    <td class="px-3 py-2 text-gray-700">{{ $row['respondent'] }}</td>
                                    <td class="px-3 py-2 text-gray-700">{{ $row['gender'] }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $row['location'] }}</td>
                                    <td class="px-3 py-2 text-gray-500">{{ $row['submitted_at'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @foreach($analytics as $data)
                <div class="card">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="font-semibold text-gray-800">{{ $data['question']->title }}</h3>
                            <span class="text-xs text-gray-400">{{ $data['question']->getTypeLabel() }} · {{ $data['total_answers'] }} answers</span>
                        </div>
                    </div>

                    @switch($data['question']->type)
                        @case('true_false')
                            <div class="flex items-center gap-6">
                                <canvas id="chart-{{ $data['question']->id }}" width="200" height="200" class="max-w-[200px]"></canvas>
                                <div class="text-sm space-y-1">
                                    <p><span class="inline-block w-3 h-3 rounded-full bg-primary-400 mr-2"></span>True: {{ $data['chart']['values'][0] }}</p>
                                    <p><span class="inline-block w-3 h-3 rounded-full bg-accent-300 mr-2"></span>False: {{ $data['chart']['values'][1] }}</p>
                                </div>
                            </div>
                            @break

                        @case('multiple_choice')
                            <canvas id="chart-{{ $data['question']->id }}" height="120"></canvas>
                            @break

                        @case('ranking')
                            <ol class="space-y-2">
                                @foreach($data['rankings'] as $rank)
                                    <li class="flex items-center gap-3 text-sm">
                                        <span class="w-8 h-8 flex items-center justify-center rounded-full bg-primary-100 text-primary-700 font-semibold text-xs">
                                            {{ $rank['avg_rank'] ? '#' . $loop->iteration : '-' }}
                                        </span>
                                        <span class="text-gray-700">{{ $rank['label'] }}</span>
                                        @if($rank['avg_rank'])
                                            <span class="text-xs text-gray-400">avg rank: {{ $rank['avg_rank'] }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                            @break

                        @case('rating')
                            <div class="mb-3">
                                <span class="text-3xl font-bold text-primary-600">{{ $data['average'] }}</span>
                                <span class="text-sm text-gray-400">/ {{ $data['question']->settings['max_rating'] ?? 5 }}</span>
                            </div>
                            <canvas id="chart-{{ $data['question']->id }}" height="80"></canvas>
                            @break

                        @case('open_ended')
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                @forelse($data['text_responses'] as $text)
                                    <div class="bg-gray-50 rounded-lg px-4 py-2 text-sm text-gray-700">{{ $text }}</div>
                                @empty
                                    <p class="text-sm text-gray-400">No text responses.</p>
                                @endforelse
                            </div>
                            @break
                    @endswitch
                </div>
            @endforeach
        </div>
    @endif

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const analytics = @json($analytics);
            const primaryColor = '#3b82f6';
            const accentColor = '#fde047';
            const colors = ['#3b82f6', '#60a5fa', '#93c5fd', '#bfdbfe', '#dbeafe', '#fde047', '#fef08a', '#fef9c3'];

            analytics.forEach(function(data) {
                const qId = data.question.id;
                const canvas = document.getElementById('chart-' + qId);
                if (!canvas) return;

                if (data.question.type === 'true_false') {
                    new Chart(canvas, {
                        type: 'doughnut',
                        data: {
                            labels: data.chart.labels,
                            datasets: [{
                                data: data.chart.values,
                                backgroundColor: [primaryColor, accentColor],
                                borderWidth: 0,
                            }]
                        },
                        options: {
                            responsive: false,
                            plugins: { legend: { display: false } }
                        }
                    });
                }

                if (data.question.type === 'multiple_choice') {
                    new Chart(canvas, {
                        type: 'bar',
                        data: {
                            labels: data.chart.labels,
                            datasets: [{
                                data: data.chart.values,
                                backgroundColor: colors.slice(0, data.chart.labels.length),
                                borderWidth: 0,
                                borderRadius: 6,
                            }]
                        },
                        options: {
                            responsive: true,
                            indexAxis: 'y',
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { beginAtZero: true, ticks: { stepSize: 1 } },
                                y: { grid: { display: false } }
                            }
                        }
                    });
                }

                if (data.question.type === 'rating') {
                    new Chart(canvas, {
                        type: 'bar',
                        data: {
                            labels: data.chart.labels,
                            datasets: [{
                                data: data.chart.values,
                                backgroundColor: primaryColor,
                                borderWidth: 0,
                                borderRadius: 6,
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { display: false } },
                                x: { grid: { display: false } }
                            }
                        }
                    });
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
