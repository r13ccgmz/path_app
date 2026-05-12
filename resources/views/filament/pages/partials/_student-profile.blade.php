{{-- Student Profile Section (Name, Demographics, Contact, Social) --}}
<x-filament::section icon="heroicon-o-user-circle" collapsible>
    <x-slot name="heading">Student Profile</x-slot>
    <x-slot name="headerEnd">
        {{ $this->getAction('editStudentInfo') }}
    </x-slot>

    <div class="space-y-8">
        {{-- Name + Status Badge --}}
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Name:</p>
                <p class="font-semibold text-2xl">{{ $studentInfo['name'] ?? '-' }}</p>
            </div>
            @if ($studentInfo['student_status'] ?? null)
                @php
                    $statusColors = [
                        'active' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                        'on-leave' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                        'leave-of-absence-approved' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
                        'absent-without-official-leave' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                        'graduated' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                        'dismissed' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                        'dropped' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                        'withdrawn' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400',
                        'inactive' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400',
                    ];
                    $statusLabel = ucwords(str_replace('-', ' ', $studentInfo['student_status']));
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusColors[$studentInfo['student_status']] ?? 'bg-gray-100 text-gray-700' }}">
                    {{ $statusLabel }}
                </span>
            @endif
        </div>

        {{-- Name History --}}
        @php $nameHistory = $this->getNameHistory(); @endphp
        @if (!empty($nameHistory))
            <div class="rounded-lg border border-amber-200 dark:border-amber-700 bg-amber-50/50 dark:bg-amber-900/10 px-4 py-3">
                <p class="text-xs font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wider mb-2 flex items-center gap-1">
                    <x-heroicon-o-clock class="w-3.5 h-3.5" />
                    Name History
                </p>
                <div class="space-y-1">
                    @foreach ($nameHistory as $entry)
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $entry['name'] }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Term {{ $entry['first_term'] }}{{ $entry['first_term'] !== $entry['last_term'] ? ' – ' . $entry['last_term'] : '' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Core Info Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-x-12 gap-y-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Student Number:</p>
                <p class="font-semibold text-lg">{{ $studentInfo['student_number'] ? \App\Models\Enrollee::formatStudentNumber($studentInfo['student_number']) : '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Terms Enrolled:</p>
                <p class="font-semibold text-lg">{{ $studentInfo['total_terms'] ?? '0' }}</p>
            </div>
        </div>

        {{-- Demographics --}}
        <div class="grid grid-cols-2 md:grid-cols-3 gap-x-12 gap-y-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Sex:</p>
                <p class="font-medium">{{ empty($studentInfo['sex']) ? '-' : ucfirst($studentInfo['sex']) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Birthdate:</p>
                <p class="font-medium">{{ empty($studentInfo['birthdate']) ? '-' : $studentInfo['birthdate'] }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Nationality:</p>
                <p class="font-medium">
                    @if(!empty($studentInfo['nationality']) && is_array($studentInfo['nationality']))
                        {{ implode(', ', $studentInfo['nationality']) }}
                    @else
                        {{ $studentInfo['nationality'] ?? '-' }}
                    @endif
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Marital Status:</p>
                <p class="font-medium">{{ empty($studentInfo['marital_status']) ? '-' : ucfirst($studentInfo['marital_status']) }}</p>
            </div>
            @if ($studentInfo['country_of_origin'] ?? null)
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Country of Origin:</p>
                    <p class="font-medium">{{ $studentInfo['country_of_origin'] }}</p>
                </div>
            @endif
            @if (!empty($studentInfo['address']))
                <div class="md:col-span-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Address(es):</p>
                    @if(is_array($studentInfo['address']))
                        @foreach($studentInfo['address'] as $index => $addr)
                            <p class="font-medium mb-1"><span class="font-semibold text-xs text-gray-400 mr-1">Address {{ $index + 1 }}:</span> {{ $addr }}</p>
                        @endforeach
                    @else
                        <p class="font-medium">{{ $studentInfo['address'] }}</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Contact Details --}}
        <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Contact Details</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">UP Email:</p>
                    <p class="font-medium">{{ $studentInfo['up_email'] ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Primary Email:</p>
                    <p class="font-medium">{{ $studentInfo['email'] ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Primary Contact Number:</p>
                    <p class="font-medium">{{ $studentInfo['primary_contact_number'] ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Alternative Contact Number(s):</p>
                    <p class="font-medium">
                        @if(!empty($studentInfo['alternative_contact_number']) && is_array($studentInfo['alternative_contact_number']))
                            {{ implode(', ', $studentInfo['alternative_contact_number']) }}
                        @else
                            -
                        @endif
                    </p>
                </div>
                <div class="col-span-1 md:col-span-2">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Alternative Email(s):</p>
                    <p class="font-medium">
                        @if(!empty($studentInfo['alternative_email']) && is_array($studentInfo['alternative_email']))
                            {{ implode(', ', $studentInfo['alternative_email']) }}
                        @else
                            -
                        @endif
                    </p>
                </div>
            </div>

            {{-- Social Media & Affiliation --}}
            @if (($studentInfo['social_facebook'] ?? null) || ($studentInfo['social_linkedin'] ?? null) || ($studentInfo['social_other'] ?? null) || ($studentInfo['institution_affiliated'] ?? null))
                @php
                    $formatSocialValue = function($value) {
                        $url = trim($value);
                        if (preg_match("~^(?:f|ht)tps?://~i", $url) || preg_match('/^(www\.)?[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(\/.*)?$/', $url)) {
                            $href = !preg_match("~^(?:f|ht)tps?://~i", $url) ? "https://" . $url : $url;
                            return '<a href="'.e($href).'" target="_blank" rel="noopener noreferrer" class="text-primary-600 dark:text-primary-400 font-semibold hover:underline break-all inline-flex items-center gap-1">'.e($value).'<svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 00-.75.75v8.5c0 .414.336.75.75.75h8.5a.75.75 0 00.75-.75v-4a.75.75 0 011.5 0v4A2.25 2.25 0 0112.75 17h-8.5A2.25 2.25 0 012 14.75v-8.5A2.25 2.25 0 014.25 4h5a.75.75 0 010 1.5h-5z" clip-rule="evenodd" /><path fill-rule="evenodd" d="M6.194 12.753a.75.75 0 001.06.053L16.5 4.44v2.81a.75.75 0 001.5 0v-4.5a.75.75 0 00-.75-.75h-4.5a.75.75 0 000 1.5h2.553l-9.056 8.194a.75.75 0 00-.053 1.06z" clip-rule="evenodd" /></svg></a>';
                        }
                        return e($value);
                    };
                @endphp
                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Social Media & Affiliation</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-6">
                        @if ($studentInfo['social_facebook'] ?? null)
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Facebook:</p>
                                <div class="font-medium text-gray-800 dark:text-gray-200">{!! $formatSocialValue($studentInfo['social_facebook']) !!}</div>
                            </div>
                        @endif
                        @if ($studentInfo['social_linkedin'] ?? null)
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">LinkedIn:</p>
                                <div class="font-medium text-gray-800 dark:text-gray-200">{!! $formatSocialValue($studentInfo['social_linkedin']) !!}</div>
                            </div>
                        @endif
                        @if ($studentInfo['institution_affiliated'] ?? null)
                            <div class="col-span-1 md:col-span-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Office / School / Institution Affiliated:</p>
                                <div class="font-medium text-sm">
                                    @if(is_array($studentInfo['institution_affiliated']))
                                        <ul class="space-y-2">
                                            @foreach($studentInfo['institution_affiliated'] as $affil)
                                                <li class="flex items-start gap-2 bg-gray-50 dark:bg-gray-800/50 p-2.5 rounded-lg border border-gray-100 dark:border-gray-800">
                                                    <x-heroicon-m-building-office-2 class="w-5 h-5 text-primary-500 shrink-0" />
                                                    <span class="text-gray-700 dark:text-gray-300 mt-0.5">{{ $affil }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <div class="flex items-start gap-2 bg-gray-50 dark:bg-gray-800/50 p-2.5 rounded-lg border border-gray-100 dark:border-gray-800">
                                            <x-heroicon-m-building-office-2 class="w-5 h-5 text-primary-500 shrink-0" />
                                            <span class="text-gray-700 dark:text-gray-300 mt-0.5">{{ $studentInfo['institution_affiliated'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if ($studentInfo['social_other'] ?? null)
                            <div class="col-span-1 md:col-span-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Other Social Media / Contact Handles:</p>
                                <div class="font-medium text-sm">
                                    @if(is_array($studentInfo['social_other']))
                                        <ul class="space-y-2">
                                            @foreach($studentInfo['social_other'] as $social)
                                                <li class="flex items-start gap-2 bg-gray-50 dark:bg-gray-800/50 p-2.5 rounded-lg border border-gray-100 dark:border-gray-800">
                                                    <x-heroicon-m-link class="w-5 h-5 text-primary-500 shrink-0" />
                                                    <span class="text-gray-700 dark:text-gray-300 mt-0.5">{!! $formatSocialValue($social) !!}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <div class="flex items-start gap-2 bg-gray-50 dark:bg-gray-800/50 p-2.5 rounded-lg border border-gray-100 dark:border-gray-800">
                                            <x-heroicon-m-link class="w-5 h-5 text-primary-500 shrink-0" />
                                            <span class="text-gray-700 dark:text-gray-300 mt-0.5">{!! $formatSocialValue($studentInfo['social_other']) !!}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-filament::section>
