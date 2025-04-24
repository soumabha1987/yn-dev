<div>
    <div class="card my-8">
        <x-account-profile.timeline :$steps :$currentStep />

        <livewire:dynamic-component :is="$currentStep" :key="$currentStep"/>
    </div>
</div>
