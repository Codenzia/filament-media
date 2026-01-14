@extends(BaseHelper::getAdminMasterLayoutTemplate())

@push('header')
    {!! FilamentMedia::renderHeader() !!}
@endpush

@section('content')
    {!! FilamentMedia::renderContent() !!}
@endsection

@push('footer')
    {!! FilamentMedia::renderFooter() !!}
@endpush
