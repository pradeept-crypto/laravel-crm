@for ($i = 0; $i < 10; $i++)
    <div class="row grid grid-cols-[280px_1fr_100px] items-center gap-4 border-b px-6 py-3.5 text-gray-600 dark:border-gray-800">
        <div class="flex items-center gap-3">
            <div class="shimmer h-6 w-6"></div>
            <div class="shimmer h-8 w-8 rounded-full"></div>
            <div class="shimmer h-[17px] w-[140px]"></div>
        </div>

        <div class="flex items-center gap-2">
            <div class="shimmer h-[17px] w-[450px]"></div>
        </div>

        <div class="flex justify-end">
            <div class="shimmer h-[17px] w-[60px]"></div>
        </div>
    </div>
@endfor