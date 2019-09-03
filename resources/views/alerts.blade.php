@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
            <h1 class="pull-left">Alerts</h1>
            <div class="pull-right">
                <a href="/create-alert" class="btn create-alert">Create Alert</a>
            </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <h2>Untriggered</h2>
                <div class="table-responsive">
                    <table class="table-alerts table table-bordered table-hover table-condensed">
                        <thead>
                        </thead>
                        <tbody>
                        <?php $iterator = 1;?>
                        @foreach($alerts as $alert)
                            @if($alert->active == 0)
                                <tr>
                                    <td><?=($iterator)?></td>
                                    <td>{!! $alert->message_not_done !!}</td>
                                    <td id="alert{{$alert->id}}" class="alert-delete"><i class="fas fa-trash"></i></td>
                                </tr>
                                <?php $iterator++;?>
                            @endif
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <h2>Triggered</h2>
                <div class="table-responsive">
                    <table class="table-alerts table table-striped table-bordered table-hover table-condensed">
                        <thead>
                        </thead>
                        <tbody>
                        <?php $iterator = 1;?>
                        @foreach($alerts as $alert)
                            @if($alert->active == 1)
                                <tr>
                                    <td><?=($iterator)?></td>
                                    <td>{!! $alert->message !!}</td>
                                    <td id="alert{{$alert->id}}" class="alert-delete"><i class="fas fa-trash"></i></td>
                                </tr>
                                <?php $iterator++;?>
                            @endif
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <input id="success" type="hidden" value="{{$success}}">
    <script src="/public/js/alerts.js"></script>
@endsection
