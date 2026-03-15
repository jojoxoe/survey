<x-public-layout>
    <x-slot name="title">Thank You — SurveyApp</x-slot>

    <div class="card text-center py-12">
        <div class="text-4xl mb-4">🎉</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-3">Thank You!</h1>
        <p class="text-gray-500">Your response to <strong>{{ $survey->title }}</strong> has been recorded.</p>
        <a href="{{ route('home') }}" class="btn-secondary mt-6 inline-flex">← Back to Home</a>
    </div>
</x-public-layout>
