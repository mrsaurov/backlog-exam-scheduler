@extends('layouts.exam')

@section('title', 'Mail tracker')
@section('tab', 'mail')

@section('exam-content')
<div class="toolbar">
    <div class="segmented" aria-label="Mail views">
        <a href="/mail/{{$exam->id}}">Send mail</a>
        <a href="/mail/{{$exam->id}}/log" class="is-active" aria-current="page">Tracker</a>
    </div>
</div>

<div class="panel panel-flush">
    <div class="panel-head">
        <div>
            <h2 class="panel-title">Mail tracker</h2>
            <p class="section-note">Every email attempt for this exam, newest first. "Sent" means the mail server accepted the email.</p>
        </div>
        <div class="segmented" aria-label="Filter by status">
            <a href="/mail/{{$exam->id}}/log" class="{{$status === 'all' ? 'is-active' : ''}}">All <span class="seg-count">{{$sentCount + $failedCount}}</span></a>
            <a href="/mail/{{$exam->id}}/log?status=sent" class="{{$status === 'sent' ? 'is-active' : ''}}">Sent <span class="seg-count">{{$sentCount}}</span></a>
            <a href="/mail/{{$exam->id}}/log?status=failed" class="{{$status === 'failed' ? 'is-active' : ''}}">Failed <span class="seg-count">{{$failedCount}}</span></a>
        </div>
    </div>

    @if($logs->count() > 0)
        <div class="table-responsive">
            <table class="table table-clean table-xwide">
                <thead>
                    <tr>
                        <th>Date and time</th>
                        <th>Recipient</th>
                        <th style="width: 34%;">Email</th>
                        <th style="width: 26%;">Status</th>
                        <th class="cell-actions"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    @php
                        $retriedStatus = $retriedIds[$log->id] ?? null;
                    @endphp
                    <tr>
                        <td class="num text-nowrap cell-top">{{$log->created_at->timezone('Asia/Dhaka')->format('j M Y, g:i A')}}</td>
                        <td class="cell-top">
                            <span class="cell-main">{{$log->recipient_name}}</span>
                            <span class="cell-sub">{{$log->recipient_email}}</span>
                        </td>
                        <td class="cell-top">
                            {{$log->subject}}
                            <span class="cell-sub">
                                {{ucfirst($log->mail_type)}}{{ $log->template_name ? ', ' . $log->template_name : '' }}{{ $log->resent_from_id ? ', resend' : '' }}
                            </span>
                            @if($log->attachment_names)
                                <span class="cell-sub"><i class="bi bi-paperclip"></i> {{implode(', ', $log->attachment_names)}}</span>
                            @endif
                        </td>
                        <td class="cell-top">
                            @if($log->status === 'sent')
                                <span class="status status-success">Sent</span>
                            @else
                                <span class="status status-danger">Failed</span>
                                <span class="cell-sub">{{\Illuminate\Support\Str::limit($log->error_message, 110)}}</span>
                                @if($retriedStatus)
                                    <span class="cell-sub">Resent later: {{$retriedStatus === 'sent' ? 'sent' : 'failed again'}}</span>
                                @endif
                            @endif
                        </td>
                        <td class="cell-actions cell-top">
                            @if($log->status === 'failed' && $retriedStatus !== 'sent')
                                <form method="POST" action="/mail/{{$exam->id}}/log/{{$log->id}}/resend" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-quiet btn-sm">
                                        <i class="bi bi-arrow-repeat"></i> Resend
                                    </button>
                                </form>
                            @endif
                            <button type="button" class="btn-icon view-mail" data-log="{{$log->id}}" title="View email" aria-label="View email">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="panel-body border-top">
                {{ $logs->links('pagination::bootstrap-5') }}
            </div>
        @endif
    @else
        <div class="empty-state">
            <h3>{{$status === 'all' ? 'No emails yet' : 'No emails with this status'}}</h3>
            <p>{{$status === 'all' ? 'Emails you send for this exam will be listed here with their result.' : 'Choose another filter to see the other attempts.'}}</p>
        </div>
    @endif
</div>

<!-- View Mail Modal -->
<div class="modal fade" id="viewMailModal" tabindex="-1" aria-labelledby="viewMailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewMailModalLabel">Email details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <dl class="facts row g-0 mb-3">
                    <div class="col-md-6">
                        <dt>To</dt>
                        <dd id="viewMailTo"></dd>
                    </div>
                    <div class="col-md-6">
                        <dt>Date and time</dt>
                        <dd id="viewMailDate" class="num"></dd>
                    </div>
                    <div class="col-12">
                        <dt>Subject</dt>
                        <dd id="viewMailSubject"></dd>
                    </div>
                    <div class="col-12">
                        <dt>Status</dt>
                        <dd class="mb-0">
                            <span class="status" id="viewMailStatus"></span>
                            <span class="d-block fw-normal small text-danger mt-1" id="viewMailError" hidden></span>
                        </dd>
                    </div>
                </dl>
                <iframe id="viewMailBody" class="mail-frame" sandbox="" title="Email body"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-quiet" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function() {
    const mailLogs = @json($mailData);
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('viewMailModal'));

    document.querySelectorAll('.view-mail').forEach(function(button) {
        button.addEventListener('click', function() {
            const log = mailLogs[this.dataset.log];
            if (!log) {
                return;
            }

            const status = document.getElementById('viewMailStatus');
            const error = document.getElementById('viewMailError');

            document.getElementById('viewMailTo').textContent = log.to;
            document.getElementById('viewMailSubject').textContent = log.subject;
            document.getElementById('viewMailDate').textContent = log.date;
            status.textContent = log.status;
            status.className = 'status ' + (log.status === 'Sent' ? 'status-success' : 'status-danger');
            error.textContent = log.error || '';
            error.hidden = !log.error;
            document.getElementById('viewMailBody').srcdoc = '<body style="font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; margin: 16px;">' + log.content + '</body>';

            modal.show();
        });
    });
})();
</script>
@endsection
