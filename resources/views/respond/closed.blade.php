<x-public-layout>
    <x-slot name="title">Survey Closed — SurveyApp</x-slot>

    <div class="card text-center py-12">
        <h1 class="text-2xl font-bold text-gray-800 mb-3">{{ $survey->title }}</h1>
        <p class="text-gray-500">This survey is no longer accepting responses.</p>
        <a href="{{ route('home') }}" class="btn-secondary mt-6 inline-flex">← Back to Home</a>
    </div>
</x-public-layout>
