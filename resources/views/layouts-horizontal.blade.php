@extends('layouts.master-layouts')

@section('title')
    @lang('translation.Horizontal')
@endsection
@section('body')

    <body data-layout="horizontal">
    @endsection

    <!-- Begin page -->
    @section('content')
        @component('components.breadcrumb')
            @slot('li_1')
                Layouts
            @endslot
            @slot('title')
                Horizontal
            @endslot
        @endcomponent

        
    @endsection
