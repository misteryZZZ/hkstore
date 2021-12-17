@extends('back.master')

@section('title', $title)


@section('content')
<form class="ui large form" method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
	@csrf

	<div class="field">
		<button class="ui icon labeled purple large circular button" type="submit">
		  <i class="save outline icon"></i>
		  {{ __('Update') }}
		</button>
	</div>
	
	@if($errors->any())
      @foreach ($errors->all() as $error)
         <div class="ui negative fluid small message">
         	<i class="times icon close"></i>
         	{{ $error }}
         </div>
      @endforeach
	@endif

	<div class="ui fluid divider"></div>

	<div class="one column grid" id="page">
		<div class="column">
			<div class="field">
				<img class="ui tiny image circular cursor-pointer" src="{{ asset_('storage/avatars/'.($user->avatar ?? 'default.jpg')) }}?v={{ time() }}" onclick="this.nextElementSibling.click()">
				<input type="file" class="d-none" name="avatar" accept="image/*">
			</div>

			<div class="field">
				<label>{{ __('Firstname') }}</label>
				<input type="text" name="firstname" placeholder="..." value="{{ old('firstname', $user->firstname) }}">
			</div>
			<div class="field">
				<label>{{ __('Lastname') }}</label>
				<input type="text" name="lastname" placeholder="..." value="{{ old('lastname', $user->lastname) }}">
			</div>
			<div class="field">
				<label>{{ __('Username') }}</label>
				<input type="text" name="name" placeholder="..." value="{{ old('name', $user->name) }}">
			</div>
			<div class="field">
				<label>{{ __('Email') }}</label>
				<input type="text" name="email" placeholder="..." value="{{ old('email', $user->email) }}" required>
			</div>
			<div class="field">
				<label>{{ __('Password') }}</label>
				<input type="text" name="password" placeholder="..." value="{{ old('password') }}">
			</div>

		</div>
	</div>
</form>

@endsection