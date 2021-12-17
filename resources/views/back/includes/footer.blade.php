<div class="ui secondary unstackable menu m-0">
	<span class="item header">{{ config('app.name') }} © {{ date('Y') }} {{ __('All right reserved') }}</span>
</div>						

<form action="{{ route('set_locale') }}" method="post" class="d-none" id="set-locale">
	<input type="hidden" name="redirect" value="{{ url()->full() }}">
	<input type="hidden" name="locale">
</form>