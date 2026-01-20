@php($sorts = FilamentMedia::getSorts())

<div>
    @include('filament-media::header')
    @include('filament-media::content')


    @include('filament-media::footer')
    @include('filament-media::config')
</div>
