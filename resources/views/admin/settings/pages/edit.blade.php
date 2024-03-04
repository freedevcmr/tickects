@extends('admin.layouts.form')
@section('section', admin_lang('Settings'))
@section('title', $page->title)
@section('back', route('admin.settings.pages.index'))
@section('content')
    <div class="mb-3">
        <a class="btn btn-outline-secondary" href="{{ route('page', $page->slug) }}" target="_blank"><i
                class="fa fa-eye me-2"></i>{{ admin_lang('Preview') }}</a>
    </div>
    <form id="vironeer-submited-form" action="{{ route('admin.settings.pages.update', $page->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card p-2 mb-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">{{ admin_lang('Title') }} </label>
                            <input type="text" name="title" class="form-control" required
                                value="{{ $page->title }}" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ admin_lang('Slug') }} </label>
                            <div class="input-group vironeer-input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">{{ url('/') }}/</span>
                                </div>
                                <input type="text" name="slug" class="form-control" required
                                    value="{{ $page->slug }}" />
                            </div>
                            <small class="text-muted">{{ admin_lang('To create a contact page set slug as') }}
                                <strong>{{ admin_lang('contact-us') }}</strong></small>
                        </div>
                        <div class="ckeditor-lg mb-3">
                            <label class="form-label">{{ admin_lang('Body') }} </label>
                            <textarea name="body" rows="10" class="form-control ckeditor">{{ $page->body }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">{{ admin_lang('Short description') }} </label>
                            <textarea name="short_description" rows="6" class="form-control"
                                placeholder="{{ admin_lang('50 to 200 character at most') }}">{{ $page->short_description }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @push('styles_libs')
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap/select/bootstrap-select.min.css') }}">
    @endpush
    @push('scripts_libs')
        <script src="{{ asset('assets/vendor/libs/ckeditor/ckeditor.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/ckeditor/plugins/uploadAdapterPlugin.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/bootstrap/select/bootstrap-select.min.js') }}"></script>
    @endpush
@endsection
