<x-public-layout>
    <x-slot name="title">{{ $survey->title }} — SurveyApp</x-slot>

    <div class="card mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">{{ $survey->title }}</h1>
        @if($survey->description)
            <p class="text-gray-500 text-sm">{{ $survey->description }}</p>
        @endif
    </div>

    @if($errors->has('survey'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-6">
            {{ $errors->first('survey') }}
        </div>
    @endif

    <form action="{{ route('survey.submit', $survey->slug) }}" method="POST" id="response-form">
        @csrf

        {{-- Respondent Demographics --}}
        <div class="card mb-6">
            <h3 class="font-semibold text-gray-800 mb-4">About You</h3>

            {{-- Name (optional) --}}
            <div class="mb-4">
                <label for="respondent_name" class="block text-sm font-medium text-gray-700 mb-1">
                    Name <span class="text-gray-400">(optional)</span>
                </label>
                <input type="text" name="respondent_name" id="respondent_name" value="{{ old('respondent_name') }}"
                       class="input-field w-full" placeholder="Your name">
                @error('respondent_name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Gender --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Gender <span class="text-red-400">*</span>
                </label>
                <div class="flex flex-wrap gap-4">
                    @foreach(['Male', 'Female', 'Prefer not to say'] as $g)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="respondent_gender" value="{{ $g }}"
                                   class="text-primary-500 focus:ring-primary-300"
                                   {{ old('respondent_gender') === $g ? 'checked' : '' }}>
                            <span class="text-sm text-gray-700">{{ $g }}</span>
                        </label>
                    @endforeach
                </div>
                @error('respondent_gender')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="location-selector">
                <div>
                    <label for="region_code" class="block text-sm font-medium text-gray-700 mb-1">
                        Region <span class="text-red-400">*</span>
                    </label>
                    <select
                        id="region_code"
                        name="region_code"
                        class="input-field w-full"
                        data-old="{{ old('region_code') }}"
                    >
                        <option value="">Select a region</option>
                    </select>
                    @error('region_code')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="province_code" class="block text-sm font-medium text-gray-700 mb-1">
                        Province <span class="text-red-400">*</span>
                    </label>
                    <select
                        id="province_code"
                        name="province_code"
                        class="input-field w-full"
                        data-old="{{ old('province_code') }}"
                        disabled
                    >
                        <option value="">Select a province</option>
                    </select>
                    @error('province_code')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="city_municipality_code" class="block text-sm font-medium text-gray-700 mb-1">
                        City / Municipality <span class="text-red-400">*</span>
                    </label>
                    <select
                        id="city_municipality_code"
                        name="city_municipality_code"
                        class="input-field w-full"
                        data-old="{{ old('city_municipality_code') }}"
                        disabled
                    >
                        <option value="">Select a city / municipality</option>
                    </select>
                    @error('city_municipality_code')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="barangay_code" class="block text-sm font-medium text-gray-700 mb-1">
                        Barangay <span class="text-red-400">*</span>
                    </label>
                    <select
                        id="barangay_code"
                        name="barangay_code"
                        class="input-field w-full"
                        data-old="{{ old('barangay_code') }}"
                        disabled
                    >
                        <option value="">Select a barangay</option>
                    </select>
                    @error('barangay_code')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <input type="hidden" id="region_name" name="region_name" value="{{ old('region_name') }}">
            <input type="hidden" id="province_name" name="province_name" value="{{ old('province_name') }}">
            <input type="hidden" id="city_municipality_name" name="city_municipality_name" value="{{ old('city_municipality_name') }}">
            <input type="hidden" id="barangay_name" name="barangay_name" value="{{ old('barangay_name') }}">

            @if($errors->has('region_name') || $errors->has('province_name') || $errors->has('city_municipality_name') || $errors->has('barangay_name'))
                <div class="mt-3 bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg text-xs">
                    {{ $errors->first('region_name') ?? $errors->first('province_name') ?? $errors->first('city_municipality_name') ?? $errors->first('barangay_name') }}
                </div>
            @endif

            <p id="location-feedback" class="mt-2 text-xs text-gray-500"></p>

        </div>

        @foreach($survey->questions as $question)
            <div class="card mb-4">
                <div class="mb-3">
                    <h3 class="font-semibold text-gray-800">
                        {{ $loop->iteration }}. {{ $question->title }}
                        @if($question->is_required)
                            <span class="text-red-400">*</span>
                        @endif
                    </h3>
                    @if($question->description)
                        <p class="text-xs text-gray-400 mt-1">{{ $question->description }}</p>
                    @endif
                </div>

                @switch($question->type)
                    @case('true_false')
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="answers[{{ $question->id }}]" value="true" class="text-primary-500 focus:ring-primary-300" {{ old("answers.{$question->id}") === 'true' ? 'checked' : '' }}>
                                <span class="text-sm text-gray-700">True</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="answers[{{ $question->id }}]" value="false" class="text-primary-500 focus:ring-primary-300" {{ old("answers.{$question->id}") === 'false' ? 'checked' : '' }}>
                                <span class="text-sm text-gray-700">False</span>
                            </label>
                        </div>
                        @break

                    @case('multiple_choice')
                        @php $allowMultiple = $question->settings['allow_multiple'] ?? false; @endphp
                        <div class="space-y-2">
                            @foreach($question->options as $option)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    @if($allowMultiple)
                                        <input type="checkbox" name="answers[{{ $question->id }}][]" value="{{ $option->id }}" class="rounded text-primary-500 focus:ring-primary-300">
                                    @else
                                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}" class="text-primary-500 focus:ring-primary-300">
                                    @endif
                                    <span class="text-sm text-gray-700">{{ $option->label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @break

                    @case('ranking')
                        <div class="space-y-2" x-data="rankingComponent({{ $question->id }}, {{ json_encode($question->options->map(fn($o) => ['id' => $o->id, 'label' => $o->label])) }})" x-init="init()">
                            <p class="text-xs text-gray-400 mb-2">Drag to reorder (1 = highest rank)</p>
                            <template x-for="(item, idx) in items" :key="item.id">
                                <div class="flex items-center gap-3 bg-gray-50 rounded-lg px-4 py-2 cursor-move"
                                     draggable="true"
                                     @dragstart="dragStart(idx)"
                                     @dragover.prevent="dragOver(idx)"
                                     @drop="drop(idx)">
                                    <span class="text-xs font-semibold text-primary-500 w-6" x-text="idx + 1"></span>
                                    <span class="text-sm text-gray-700" x-text="item.label"></span>
                                    <input type="hidden" :name="'answers[{{ $question->id }}][]'" :value="item.id">
                                </div>
                            </template>
                        </div>
                        @break

                    @case('rating')
                        @php $maxRating = $question->settings['max_rating'] ?? 5; @endphp
                        <div class="flex gap-2" x-data="{ rating: {{ (int) old("answers.{$question->id}", 0) }} }">
                            @for($i = 1; $i <= $maxRating; $i++)
                                <button type="button" @click="rating = {{ $i }}"
                                        :class="rating >= {{ $i }} ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-400 hover:bg-primary-100'"
                                        class="w-10 h-10 rounded-lg font-semibold text-sm transition">
                                    {{ $i }}
                                </button>
                            @endfor
                            <input type="hidden" name="answers[{{ $question->id }}]" :value="rating">
                        </div>
                        @break

                    @case('open_ended')
                        <textarea name="answers[{{ $question->id }}]" rows="3" class="input-field w-full" placeholder="Type your answer...">{{ old("answers.{$question->id}") }}</textarea>
                        @break
                @endswitch

                @error("answers.{$question->id}")
                    <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">Submit Response</button>
        </div>
    </form>

    @push('scripts')
    <script>
        function rankingComponent(questionId, options) {
            return {
                items: options,
                dragIdx: null,
                init() {},
                dragStart(idx) { this.dragIdx = idx; },
                dragOver(idx) {},
                drop(idx) {
                    const item = this.items.splice(this.dragIdx, 1)[0];
                    this.items.splice(idx, 0, item);
                    this.dragIdx = null;
                }
            };
        }

        async function initializePsgcLocationSelector() {
            const regionSelect = document.getElementById('region_code');
            const provinceSelect = document.getElementById('province_code');
            const citySelect = document.getElementById('city_municipality_code');
            const barangaySelect = document.getElementById('barangay_code');

            if (!regionSelect || !provinceSelect || !citySelect || !barangaySelect) {
                return;
            }

            const regionNameInput = document.getElementById('region_name');
            const provinceNameInput = document.getElementById('province_name');
            const cityNameInput = document.getElementById('city_municipality_name');
            const barangayNameInput = document.getElementById('barangay_name');
            const feedback = document.getElementById('location-feedback');

            const endpoints = {
                regions: @json(route('locations.regions')),
                provinces: (regionCode) => @json(url('/locations/regions')).concat('/', encodeURIComponent(regionCode), '/provinces'),
                cities: (provinceCode) => @json(url('/locations/provinces')).concat('/', encodeURIComponent(provinceCode), '/cities-municipalities'),
                barangays: (cityCode) => @json(url('/locations/cities-municipalities')).concat('/', encodeURIComponent(cityCode), '/barangays'),
            };

            const oldRegionCode = regionSelect.dataset.old || '';
            const oldProvinceCode = provinceSelect.dataset.old || '';
            const oldCityCode = citySelect.dataset.old || '';
            const oldBarangayCode = barangaySelect.dataset.old || '';

            const resetSelect = (select, placeholder, keepDisabled = true) => {
                select.innerHTML = '';
                const option = document.createElement('option');
                option.value = '';
                option.textContent = placeholder;
                select.appendChild(option);
                select.value = '';
                select.disabled = keepDisabled;
            };

            const setLoadingState = (select, message) => {
                select.innerHTML = '';
                const option = document.createElement('option');
                option.value = '';
                option.textContent = message;
                select.appendChild(option);
                select.value = '';
                select.disabled = true;
            };

            const setFeedback = (message, isError = false) => {
                feedback.textContent = message;
                feedback.className = isError
                    ? 'mt-2 text-xs text-red-600'
                    : 'mt-2 text-xs text-gray-500';
            };

            const setHiddenName = (select, hiddenInput) => {
                const selectedOption = select.options[select.selectedIndex];
                hiddenInput.value = selectedOption && selectedOption.value ? selectedOption.text : '';
            };

            const populateSelect = (select, items, placeholder, selectedCode = '') => {
                resetSelect(select, placeholder, false);

                if (!items.length) {
                    select.disabled = true;
                    const option = select.options[0];
                    option.text = placeholder.replace('Select', 'No available');

                    return false;
                }

                items.forEach((item) => {
                    const option = document.createElement('option');
                    option.value = item.code;
                    option.textContent = item.name;
                    select.appendChild(option);
                });

                if (selectedCode) {
                    select.value = selectedCode;
                }

                return true;
            };

            const fetchLocations = async (url) => {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                const payload = await response.json();
                if (!response.ok || payload.success !== true) {
                    throw new Error(payload.message || 'Unable to load locations.');
                }

                return Array.isArray(payload.data) ? payload.data : [];
            };

            const resetDownstreamFromRegion = () => {
                resetSelect(provinceSelect, 'Select a province');
                resetSelect(citySelect, 'Select a city / municipality');
                resetSelect(barangaySelect, 'Select a barangay');
                provinceNameInput.value = '';
                cityNameInput.value = '';
                barangayNameInput.value = '';
            };

            const resetDownstreamFromProvince = () => {
                resetSelect(citySelect, 'Select a city / municipality');
                resetSelect(barangaySelect, 'Select a barangay');
                cityNameInput.value = '';
                barangayNameInput.value = '';
            };

            const resetDownstreamFromCity = () => {
                resetSelect(barangaySelect, 'Select a barangay');
                barangayNameInput.value = '';
            };

            const loadRegions = async (selectedCode = '') => {
                setLoadingState(regionSelect, 'Loading regions...');
                const regions = await fetchLocations(endpoints.regions);
                populateSelect(regionSelect, regions, 'Select a region', selectedCode);
                setHiddenName(regionSelect, regionNameInput);
            };

            const loadProvinces = async (regionCode, selectedCode = '') => {
                if (!regionCode) {
                    resetDownstreamFromRegion();

                    return;
                }

                setLoadingState(provinceSelect, 'Loading provinces...');
                const provinces = await fetchLocations(endpoints.provinces(regionCode));
                const hasItems = populateSelect(provinceSelect, provinces, 'Select a province', selectedCode);
                setHiddenName(provinceSelect, provinceNameInput);

                if (!hasItems) {
                    setFeedback('No provinces found for the selected region. Please choose another region.');
                }
            };

            const loadCitiesMunicipalities = async (provinceCode, selectedCode = '') => {
                if (!provinceCode) {
                    resetDownstreamFromProvince();

                    return;
                }

                setLoadingState(citySelect, 'Loading cities / municipalities...');
                const cities = await fetchLocations(endpoints.cities(provinceCode));
                const hasItems = populateSelect(citySelect, cities, 'Select a city / municipality', selectedCode);
                setHiddenName(citySelect, cityNameInput);

                if (!hasItems) {
                    setFeedback('No cities or municipalities found for the selected province.', true);
                }
            };

            const loadBarangays = async (cityCode, selectedCode = '') => {
                if (!cityCode) {
                    resetDownstreamFromCity();

                    return;
                }

                setLoadingState(barangaySelect, 'Loading barangays...');
                const barangays = await fetchLocations(endpoints.barangays(cityCode));
                const hasItems = populateSelect(barangaySelect, barangays, 'Select a barangay', selectedCode);
                setHiddenName(barangaySelect, barangayNameInput);

                if (!hasItems) {
                    setFeedback('No barangays found for the selected city or municipality.', true);
                }
            };

            regionSelect.addEventListener('change', async () => {
                setHiddenName(regionSelect, regionNameInput);
                resetDownstreamFromRegion();
                setFeedback('');

                try {
                    await loadProvinces(regionSelect.value);
                } catch (error) {
                    setFeedback(error.message, true);
                    resetDownstreamFromRegion();
                }
            });

            provinceSelect.addEventListener('change', async () => {
                setHiddenName(provinceSelect, provinceNameInput);
                resetDownstreamFromProvince();
                setFeedback('');

                try {
                    await loadCitiesMunicipalities(provinceSelect.value);
                } catch (error) {
                    setFeedback(error.message, true);
                    resetDownstreamFromProvince();
                }
            });

            citySelect.addEventListener('change', async () => {
                setHiddenName(citySelect, cityNameInput);
                resetDownstreamFromCity();
                setFeedback('');

                try {
                    await loadBarangays(citySelect.value);
                } catch (error) {
                    setFeedback(error.message, true);
                    resetDownstreamFromCity();
                }
            });

            barangaySelect.addEventListener('change', () => {
                setHiddenName(barangaySelect, barangayNameInput);
                setFeedback('');
            });

            try {
                setFeedback('Loading location options...');
                await loadRegions(oldRegionCode);

                if (oldRegionCode && regionSelect.value) {
                    await loadProvinces(oldRegionCode, oldProvinceCode);
                }

                if (oldProvinceCode && provinceSelect.value) {
                    await loadCitiesMunicipalities(oldProvinceCode, oldCityCode);
                }

                if (oldCityCode && citySelect.value) {
                    await loadBarangays(oldCityCode, oldBarangayCode);
                }

                setFeedback('');
            } catch (error) {
                setFeedback(error.message || 'Location options are temporarily unavailable.', true);
                resetSelect(regionSelect, 'Unable to load regions');
                resetDownstreamFromRegion();
            }
        }

        document.addEventListener('DOMContentLoaded', initializePsgcLocationSelector);
    </script>
    @endpush
</x-public-layout>
