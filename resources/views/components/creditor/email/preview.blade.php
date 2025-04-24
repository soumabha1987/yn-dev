@props([
    'from' => [
        'email' => 'testing@testing.com',
        'name' => 'Kristian Trantow',
    ],
    'to' => [
        'email' => 'example@domain.com',
        'name' => 'Tony Stark',
    ],
    'subject' => '(No subject)',
    'content' => null,
])

<div class="rounded-t-lg shadow w-full">
    <div class="w-full h-11 rounded-t-lg bg-gray-200 flex justify-start items-center space-x-1.5 px-3">
        <span class="size-3 rounded-full bg-error"></span>
        <span class="size-3 rounded-full bg-warning"></span>
        <span class="size-3 rounded-full bg-success"></span>
    </div>
    <div class="bg-white border-t-0 w-full border border-gray-200 rounded-b">
        <div class="w-full flex flex-col">
            <div class="border-b border-gray-200 p-4 flex flex-col space-y-2">
                <div class="flex items-center justify-between space-x-2">
                    <div>From <span class="font-semibold">{{ $from['name'] ?? auth()->user()->name }} &lt;{{ $from['email'] ?? auth()->user()->email }}&gt;</span></div>
                </div>
                <div>To <span class="font-semibold">{{ $to['name'] }} &lt;{{ $to['email'] }}&gt;</span></div>
            </div>
            <div class="border-b border-gray-200 p-4">
                <div class="text-md font-semibold">{{ $subject ?? '(No subject)' }}</div>
            </div>
            <div class="p-4 flex flex-col space-y-4">
                <div class="prose text-black h-36 sm:h-48 lg:h-52 overflow-y-auto scroll-bar-visible">
                    {!! $content !!}
                </div>
            </div>
        </div>
    </div>
</div>