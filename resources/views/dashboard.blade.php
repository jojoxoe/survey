<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">My Surveys</h2>
            <a href="{{ route('surveys.create') }}" class="btn-primary btn-sm">+ New Survey</a>
        </div>
    </x-slot>

    <div x-data="{ modalOpen: {{ session('delete_error') ? 'true' : 'false' }}, modalAction: '{{ session('delete_survey_url', '') }}' }">

    @if($surveys->isEmpty())
        <div class="card text-center py-12">
            <p class="text-gray-400 mb-4">You haven't created any surveys yet.</p>
            <a href="{{ route('surveys.create') }}" class="btn-primary">Create Your First Survey</a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($surveys as $survey)
                <div class="card">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-1">
                                <h3 class="text-lg font-semibold text-gray-800 truncate">{{ $survey->title }}</h3>
                                <span class="badge-{{ $survey->status }}">{{ ucfirst($survey->status) }}</span>
                            </div>
                            @if($survey->description)
                                <p class="text-sm text-gray-500 truncate">{{ $survey->description }}</p>
                            @endif
                            <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                                <span>{{ $survey->questions()->count() }} questions</span>
                                <span>{{ $survey->responses()->count() }} responses</span>
                                <span>Created {{ $survey->created_at->diffForHumans() }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-shrink-0">
                            <!-- Share Info -->
                            @if($survey->isPublished())
                                <div x-data="{ showShare: false }" class="relative">
                                    <button @click="showShare = !showShare" class="btn-secondary btn-sm" title="Share">
                                        Share
                                    </button>
                                    <div x-show="showShare" @click.away="showShare = false" x-transition
                                         class="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-lg border border-gray-100 p-4 z-10">
                                        <p class="text-xs text-gray-500 mb-2">Access Code</p>
                                        <div class="flex items-center gap-2 mb-3">
                                            <code class="flex-1 bg-accent-100 text-gray-800 px-3 py-1.5 rounded-lg text-sm font-mono tracking-widest text-center">{{ $survey->access_code }}</code>
                                            <button @click="navigator.clipboard.writeText('{{ $survey->access_code }}')" class="text-xs text-primary-500 hover:text-primary-600">Copy</button>
                                        </div>
                                        <p class="text-xs text-gray-500 mb-2">Share Link</p>
                                        <div class="flex items-center gap-2">
                                            <input type="text" value="{{ $survey->share_url }}" readonly class="flex-1 text-xs bg-gray-50 border border-gray-200 rounded-lg px-2 py-1.5 truncate">
                                            <button @click="navigator.clipboard.writeText('{{ $survey->share_url }}')" class="text-xs text-primary-500 hover:text-primary-600">Copy</button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Actions -->
                            @if($survey->status === 'draft')
                                <a href="{{ route('surveys.edit', $survey) }}" class="btn-secondary btn-sm">Edit</a>
                                <form action="{{ route('surveys.publish', $survey) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn-primary btn-sm">Publish</button>
                                </form>
                            @endif

                            @if($survey->status === 'published')
                                <a href="{{ route('surveys.results', $survey) }}" class="btn-secondary btn-sm">Results</a>
                                <form action="{{ route('surveys.close', $survey) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn-sm inline-flex items-center px-3 py-1.5 text-xs font-semibold text-gray-500 hover:text-gray-700 transition">Close</button>
                                </form>
                            @endif

                            @if($survey->status === 'closed')
                                <a href="{{ route('surveys.results', $survey) }}" class="btn-secondary btn-sm">Results</a>
                                <form action="{{ route('surveys.reopen', $survey) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn-sm inline-flex items-center px-3 py-1.5 text-xs font-semibold text-primary-500 hover:text-primary-700 transition">Reopen</button>
                                </form>
                                <button type="button"
                                        @click="modalAction = '{{ route('surveys.destroy', $survey) }}'; modalOpen = true"
                                        class="btn-sm inline-flex items-center px-3 py-1.5 text-xs font-semibold text-red-400 hover:text-red-600 transition">
                                    Delete
                                </button>
                            @endif

                            <form action="{{ route('surveys.duplicate', $survey) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-sm inline-flex items-center px-3 py-1.5 text-xs font-semibold text-gray-400 hover:text-gray-600 transition" title="Duplicate">
                                    Clone
                                </button>
                            </form>

                            @if($survey->status === 'draft')
                                <form action="{{ route('surveys.destroy', $survey) }}" method="POST" onsubmit="return confirm('Delete this survey?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-sm inline-flex items-center px-3 py-1.5 text-xs font-semibold text-red-400 hover:text-red-600 transition">Delete</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Password confirmation modal for deleting closed surveys --}}
    <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" style="display:none">
        <div class="relative bg-white rounded-xl shadow-xl p-6 w-full max-w-sm mx-4" @click.stop>
            <h3 class="text-lg font-semibold text-gray-800 mb-1">Delete Survey</h3>
            <p class="text-sm text-gray-500 mb-4">This action is <strong>permanent</strong> and cannot be undone. Enter your password to confirm.</p>
            <form :action="modalAction" method="POST">
                @csrf
                @method('DELETE')
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Your Password</label>
                    <input type="password" name="password" class="input-field w-full" placeholder="Enter your password" required>
                    @if(session('delete_error'))
                        <p class="text-red-500 text-xs mt-1">{{ session('delete_error') }}</p>
                    @endif
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" @click="modalOpen = false" class="btn-secondary btn-sm">Cancel</button>
                    <button type="submit" class="btn-danger btn-sm">Delete Forever</button>
                </div>
            </form>
        </div>
    </div>

    </div>{{-- end x-data wrapper --}}
</x-app-layout>
