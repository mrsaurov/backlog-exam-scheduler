@extends('layouts.master')
 
@section('title', 'Notices')
 
@section('content')
<div class="row mb-4">
    <div class="col-md-12">
        <h2>Notices for {{$exam->exam_name}}</h2>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Exam Information</h5>
                <div class="row">
                    <div class="col-lg-4 col-md-6 mb-2">
                        <strong>Department:</strong><br>
                        <span class="text-muted">{{$exam->department}}</span>
                    </div>
                    <div class="col-lg-2 col-md-3 col-6 mb-2">
                        <strong>Series:</strong><br>
                        <span class="text-muted">{{$exam->series}}</span>
                    </div>
                    <div class="col-lg-3 col-md-3 col-6 mb-2">
                        <strong>Registration Deadline:</strong><br>
                        <span class="text-danger">{{$exam->deadline}}</span>
                    </div>
                    <div class="col-lg-3 col-md-12 mb-2">
                        <a href="/register/{{$exam->id}}" class="btn btn-primary btn-sm w-100">Register for this Exam</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(count($notices) > 0)
    <div class="row">
        <div class="col-md-12">
            <h4 class="mb-3">Important Notices</h4>
        </div>
        @foreach($notices as $notice)
        <div class="col-md-12 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 text-primary">
                        <i class="fas fa-bullhorn text-primary"></i>
                        {{$notice->title}}
                    </h5>
                    <small class="text-muted">Published: {{$notice->created_at->format('d M Y, h:i A')}}</small>
                </div>
                <div class="card-body">
                    <div class="notice-content">
                        {!! nl2br(strip_tags($notice->content, '<b><i><br>')) !!}
                    </div>
                    @if($notice->updated_at != $notice->created_at)
                        <hr>
                        <small class="text-muted">Last updated: {{$notice->updated_at->format('d M Y, h:i A')}}</small>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
@else
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-info">
                <h4><i class="fas fa-info-circle"></i> No notices available</h4>
                <p>There are currently no notices published for this exam. Please check back later for updates.</p>
            </div>
        </div>
    </div>
@endif

<div class="row mt-4">
    <div class="col-md-12">
        <a href="/" class="btn btn-secondary">Back to Available Exams</a>
    </div>
</div>

@endsection

@section('styles')
<style>
.notice-content {
    font-size: 1rem;
    line-height: 1.6;
}

.card-header h5 {
    color: #333;
}

.card-header i {
    margin-right: 8px;
}

.alert-info {
    border-left: 4px solid #17a2b8;
}
</style>
@endsection
