@extends('layouts.citizen')

@section('title', __('Signaler un problème') . ' - ' . config('app.name'))

@section('content')
<livewire:report-form />
@endsection
