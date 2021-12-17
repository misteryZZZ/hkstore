{{-- TENDRA --}}

@extends(view_path('master'))

@section('body')

<div class="ui one column shadowless celled grid my-0" id="folder">

	<div class="column">
		<div class="ui header title">{{ $title }}</div>
		
		<div class="ui four doubling cards">
			@foreach($files_list as $file)
			<div class="card fluid center aligned">
				<div class="content name" title="{{ $file->name }}">
					{{ mb_ucfirst(mb_strtolower($file->name)) }}
				</div>
				<div class="content icon">
					<i class="file huge outline icon mx-0"></i>
				</div>
				<a class="content link" @click="downloadFile('{{ $file->id }}', '{{ $file->name }}', '#download-file-form')">
					{{ __('Download') }}
				</a>
			</div>
			@endforeach
		</div>
	</div>
	

	<form action="{{ route('home.product_folder_sync_download', ['id' => $product->id, 'slug' => $product->slug]) }}" target="_blank" id="download-file-form" class="d-none" method="post">
		@csrf
		<input type="hidden" name="slug" value="{{ $product->slug }}">
		<input type="hidden" name="id" value="{{ $product->id }}">
		<input type="hidden" name="file_name" v-model="folderFileName">
		<input type="hidden" name="file_client_name" v-model="folderClientFileName">
	</form>

</div>

@endsection