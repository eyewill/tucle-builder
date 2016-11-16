<?php echo '<?php' ?>

@foreach ($uses as $use)
use {!! $use !!};
@endforeach

Route::model('{{ $module }}', 'App\{{ $module->studly() }}');

@foreach ($routes as $route)
{!! $route  !!}

@endforeach
