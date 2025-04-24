<div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
        @foreach (range(1, 7) as $range)
            <div class="card shadow-lg">
                <div class="relative group">
                    <img
                        class="w-full h-48 object-cover rounded-t-lg"
                        src="{{ asset('images/video-thumbnails/donna.jpg') }}"
                        alt="#thumbnail"
                    >

                    <button class="absolute inset-0 flex items-center justify-center transition duration-300 group-hover:bg-black/50 rounded-t-lg">
                        <x-lucide-play class="size-14 fill-white transition-transform duration-300 group-hover:scale-125 opacity-80 group-hover:opacity-100"/>
                    </button>
                </div>

                <div class="p-4">
                    <h3 class="text-lg font-semibold text-gray-900">Introduction Of YouNegotiate</h3>
                    <p class="text-sm text-gray-600 mt-1">Get a quick introduction to YouNegotiate – a powerful platform designed to simplify negotiations.</p>
                </div>
            </div>
        @endforeach
    </div>
</div>
