@extends('admin.app')
@section('title') {{ $pageTitle }} @endsection
@section('content')
    <div class="app-title">
        <div>
            <h1><i class="fa fa-tags"></i> {{ $pageTitle }}</h1>
            <p>{{ $subTitle }}</p>
        </div>
        <a href="{{ route('admin.slider.create') }}" class="btn btn-primary pull-right">Добавить слайд</a>
    </div>
    @include('admin.partials.flash')
    <div class="row">
        <div class="col-md-12">
            <div class="tile">
                <div class="tile-body">
                    <table class="table table-hover table-bordered" id="sampleTable">
                        <thead>
                        <tr>
                            <th class="d-none d-sm-table-cell"> # </th>
                            <th> Имя </th>
                            <th class=" d-none d-sm-table-cell"> Slug </th>
                            <th class="text-center "> Статус </th>
                            <th style="width:100px; min-width:100px;" class="text-center text-danger"><i class="fa fa-bolt"> </i></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($records as $record)
                            <tr>
                                <td class="d-none d-sm-table-cell">{{ $record->id }}</td>
                                <td>{{ $record->name }}</td>
                                <td class="d-none d-sm-table-cell">{{ $record->slug }}</td>

                                <td class="text-center">
                                    <div class="toggle-flip">
                                        <label>
                                            <input type="checkbox" name="hidden" {{ $record->hidden == 0 ? 'checked' : '' }} data-table="{{ $record->getTable() }}" data-id="{{ $record->id }}">
                                            <span class="flip-indecator" data-toggle-on="Вкл" data-toggle-off="Выкл">

                                            </span>
                                        </label>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group" aria-label="Second group">
                                        <a href="{{ route('admin.slider.edit', $record->id) }}" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a>
                                        <a href="{{ route('admin.slider.delete', $record->id) }}" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')

@endpush
