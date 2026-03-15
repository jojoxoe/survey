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
        <div class="card mb-6" x-data="locationPicker()">
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

            {{-- Location (PSGC cascading) --}}
            <div class="mb-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Location <span class="text-red-400">*</span>
                </label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {{-- Region --}}
                <div>
                    <select name="respondent_region" x-model="region" @change="onRegionChange()"
                            class="input-field w-full text-sm"
                            x-html="'<option value=\'\'>Select Region</option>' + regions.map(r => '<option value=\''+r.name+'\'>'+r.name+'</option>').join('')">
                    </select>
                    @error('respondent_region')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Province --}}
                <div>
                    <select name="respondent_province" x-model="province" @change="onProvinceChange()"
                            class="input-field w-full text-sm" :disabled="!provinces.length"
                            x-html="'<option value=\'\'>Select Province</option>' + provinces.map(p => '<option value=\''+p.name+'\'>'+p.name+'</option>').join('')">
                    </select>
                    @error('respondent_province')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- City / Municipality --}}
                <div>
                    <select name="respondent_city" x-model="city" @change="onCityChange()"
                            class="input-field w-full text-sm" :disabled="!cities.length"
                            x-html="'<option value=\'\'>Select City/Municipality</option>' + cities.map(c => '<option value=\''+c.name+'\'>'+c.name+'</option>').join('')">
                    </select>
                    @error('respondent_city')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Barangay --}}
                <div>
                    <select name="respondent_barangay" x-model="barangay"
                            class="input-field w-full text-sm" :disabled="!barangays.length"
                            x-html="'<option value=\'\'>Select Barangay</option>' + barangays.map(b => '<option value=\''+b.name+'\'>'+b.name+'</option>').join('')">
                    </select>
                    @error('respondent_barangay')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <p x-show="loading" class="text-xs text-gray-400 mt-2">Loading locations...</p>
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
        function locationPicker() {
            return {
                regions: [],
                provinces: [],
                cities: [],
                barangays: [],
                region: '{{ old('respondent_region', '') }}',
                province: '{{ old('respondent_province', '') }}',
                city: '{{ old('respondent_city', '') }}',
                barangay: '{{ old('respondent_barangay', '') }}',
                loading: false,
                regionMap: {},
                provinceMap: {},
                cityMap: {},

                async init() {
                    this.loading = true;
                    try {
                        const res = await fetch('/api/psgc/regions');
                        const data = await res.json();
                        this.regions = data;
                        data.forEach(r => { this.regionMap[r.name] = r.code; });

                        if (this.region) {
                            await this.loadProvinces();
                            if (this.province) {
                                await this.loadCities();
                                if (this.city) {
                                    await this.loadBarangays();
                                }
                            }
                        }
                    } catch (e) { console.error('Failed to load regions', e); }
                    this.loading = false;
                },

                async onRegionChange() {
                    this.provinces = []; this.cities = []; this.barangays = [];
                    this.province = ''; this.city = ''; this.barangay = '';
                    if (this.region) await this.loadProvinces();
                },

                async onProvinceChange() {
                    this.cities = []; this.barangays = [];
                    this.city = ''; this.barangay = '';
                    if (this.province) await this.loadCities();
                },

                async onCityChange() {
                    this.barangays = [];
                    this.barangay = '';
                    if (this.city) await this.loadBarangays();
                },

                async loadProvinces() {
                    this.loading = true;
                    const code = this.regionMap[this.region];
                    if (!code) { this.loading = false; return; }
                    try {
                        const res = await fetch('/api/psgc/regions/' + code + '/provinces');
                        const data = await res.json();
                        this.provinces = data;
                        this.provinceMap = {};
                        data.forEach(p => { this.provinceMap[p.name] = p.code; });
                    } catch (e) { console.error('Failed to load provinces', e); }
                    this.loading = false;
                },

                async loadCities() {
                    this.loading = true;
                    const code = this.provinceMap[this.province];
                    if (!code) { this.loading = false; return; }
                    try {
                        const res = await fetch('/api/psgc/provinces/' + code + '/cities');
                        const data = await res.json();
                        this.cities = data;
                        this.cityMap = {};
                        data.forEach(c => { this.cityMap[c.name] = c.code; });
                    } catch (e) { console.error('Failed to load cities', e); }
                    this.loading = false;
                },

                async loadBarangays() {
                    this.loading = true;
                    const code = this.cityMap[this.city];
                    if (!code) { this.loading = false; return; }
                    try {
                        const res = await fetch('/api/psgc/cities/' + code + '/barangays');
                        this.barangays = await res.json();
                    } catch (e) { console.error('Failed to load barangays', e); }
                    this.loading = false;
                },
            };
        }

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
    </script>
    @endpush
</x-public-layout>
