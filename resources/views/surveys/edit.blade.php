<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Edit Survey</h2>
    </x-slot>

    <form action="{{ route('surveys.update', $survey) }}" method="POST" id="survey-form">
        @csrf
        @method('PUT')

        <!-- Survey Details -->
        <div class="card mb-6">
            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Survey Title</label>
                <input type="text" name="title" id="title" value="{{ old('title', $survey->title) }}" class="input-field w-full" required>
                @error('title')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-gray-400">(optional)</span></label>
                <textarea name="description" id="description" rows="2" class="input-field w-full">{{ old('description', $survey->description) }}</textarea>
            </div>
        </div>

        <!-- Questions Section -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Questions</h3>
            </div>

            <div id="questions-container">
                <!-- Questions loaded from existing data by JS -->
            </div>

            @error('questions')
                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
            @enderror

            <!-- Add Question Buttons -->
            <div class="card border-dashed border-2 border-gray-200 bg-accent-50 text-center py-4">
                <p class="text-sm text-gray-500 mb-3">Add a question</p>
                <div class="flex flex-wrap justify-center gap-2">
                    <button type="button" onclick="addQuestion('true_false')" class="btn-secondary btn-sm">True/False</button>
                    <button type="button" onclick="addQuestion('multiple_choice')" class="btn-secondary btn-sm">Multiple Choice</button>
                    <button type="button" onclick="addQuestion('ranking')" class="btn-secondary btn-sm">Ranking</button>
                    <button type="button" onclick="addQuestion('rating')" class="btn-secondary btn-sm">Rating</button>
                    <button type="button" onclick="addQuestion('open_ended')" class="btn-secondary btn-sm">Open Ended</button>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700 transition">Cancel</a>
            <button type="submit" class="btn-primary">Update Survey</button>
        </div>
    </form>

    @push('scripts')
    <script>
        window.existingQuestions = @json($existingQuestions);
    </script>
    @endpush
</x-app-layout>
