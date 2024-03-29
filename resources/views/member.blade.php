<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Member') }}
        </h2>
    </x-slot>
    
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex items-center gap-4 mt-10 ml-10 text-l">
                        @include('messages.status-msg-display')
                        @include('messages.error-msg-display')
                    </div>
                    <div class="text-l relative overflow-x-auto">
                    <table class="w-full text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-left text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-4">
                                        Member Name
                                    </th>
                                    <th class="px-6 py-4">
                                        Phone Number
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="text-left">
                            @foreach($members as $member)
                                <tr>
                                    <td class="px-6 py-4">{{ $member->full_name }}</td>
                                    <td class="px-6 py-4">{{ $member->phone }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        @include('member.member-create-form')
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>